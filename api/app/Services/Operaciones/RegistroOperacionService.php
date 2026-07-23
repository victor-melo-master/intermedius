<?php

namespace App\Services\Operaciones;

use App\Jobs\ProcesarFifoOperacionJob;
use App\Jobs\RecalcularSaldoCuentaJob;
use App\Models\Cuenta;
use App\Models\Moneda;
use App\Models\Operacion;
use App\Models\TipoOperacion;
use App\Models\Transaccion;
use App\Services\Configuracion\CalculadorComisionesService;
use App\Services\Configuracion\TasaDiariaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Service for registering and updating business operations with their accounting movements.
 * Handles double-entry validation, daily rate resolution, gross profit calculation,
 * automatic commission application, and FIFO/saldo cache job dispatching.
 */
class RegistroOperacionService
{
    /**
     * Tolerancia en USD para la invariante de partida doble.
     * Cubre imprecisión de tasas decimales (e.g. 1/36.50 no es exacto).
     */
    const TOLERANCIA_USD = 0.01;

    public function __construct(
        private readonly TasaDiariaService $tasaService,
        private readonly CalculadorComisionesService $comisionesService,
        private readonly CierreOperacionService $cierreService,
    ) {}

    /**
     * Registra una operación de negocio con sus movimientos contables.
     *
     * Flujo:
     * 1. Identifica tipo de operación.
     * 2. Valida movimientos (cuadre, cuentas activas, etc.).
     * 3. Resuelve tasa sugerida del día y valida la tasa efectiva del operador.
     * 4. Crea operación + movimientos en transacción.
     * 5. Calcula ganancia bruta.
     * 6. Aplica comisiones automáticamente.
     * 7. Despacha jobs de recálculo de saldos y FIFO.
     *
     * @param array $payload Ver estructura en docblock de validarMovimientos.
     */
    public function registrar(array $payload): Operacion
    {
        $tipo = TipoOperacion::where('codigo', $payload['tipo_codigo'])->firstOrFail();

        // Si es intermediada, no se usa tasa diaria
        if ($tipo->codigo === 'intermediada') {
            return $this->registrarIntermediada($payload, $tipo);
        }

        // Auto-calcular tasa_a_usd si no viene en el payload
        $tasaAplicada = (float) ($payload['tasa_aplicada'] ?? 1);
        $payload['movimientos'] = array_map(function ($mov) use ($tasaAplicada) {
            if (!empty($mov['tasa_a_usd'])) {
                return $mov;
            }
            $cuenta = Cuenta::with('moneda')->find($mov['cuenta_id']);
            $codigo = $cuenta?->moneda?->codigo;
            $mov['tasa_a_usd'] = match ($codigo) {
                'USD', 'USDT' => 1.0,
                default       => $tasaAplicada > 0 ? round(1 / $tasaAplicada, 8) : 1.0,
            };
            return $mov;
        }, $payload['movimientos']);

        $this->validarMovimientos($payload['movimientos'], $tipo);

        // ── Resolver tasa diaria y validar tasa efectiva ──────────────────────
        [$tasaDiaria, $tasaSugerida, $tasaEfectiva, $sinTasaReferencia] =
            $this->resolverTasa($payload, $tipo);

        return DB::transaction(function () use ($payload, $tipo, $tasaDiaria, $tasaSugerida, $tasaEfectiva, $sinTasaReferencia) {
            $operacion = Operacion::create([
                'fecha'                  => $payload['fecha'],
                'tipo_operacion_id'      => $tipo->id,
                'moneda_operacion_id'    => $payload['moneda_operacion_id'] ?? null,
                'cliente_id'             => $payload['cliente_id'] ?? null,
                'categoria_gasto_id'     => $payload['categoria_gasto_id'] ?? null,
                'operador_id'            => $payload['operador_id'],
                'tasa_aplicada'          => $tasaEfectiva,
                'genera_comision'        => $payload['genera_comision'] ?? false,
                'monto_comision'         => $payload['monto_comision'] ?? 0,
                'tipo_comision'          => $payload['tipo_comision'] ?? null,
                'tasa_sugerida'          => $tasaSugerida,
                'tasa_diaria_id'         => $tasaDiaria?->id,
                'sin_tasa_referencia'    => $sinTasaReferencia,
                'tasa_mercado_snapshot'  => $payload['tasa_mercado_snapshot'] ?? null,
                'fuente_tasa_mercado'    => $payload['fuente_tasa_mercado'] ?? null,
                'ganancia_bruta_usd'     => 0,
                'ganancia_bruta_ves'     => 0,
                'referencia'             => $payload['referencia'] ?? null,
                'descripcion'            => $payload['descripcion'] ?? null,
                'estatus'                => 'sin_verificar',
                'estado_pool'            => 'pendiente',
                'origen'                 => $payload['origen'] ?? 'manual',
                'origen_referencia'      => $payload['origen_referencia'] ?? null,
            ]);

            foreach ($payload['movimientos'] as $index => $movData) {
                // moneda_id viene de la cuenta, NO del payload, para garantizar coherencia.
                $cuenta = Cuenta::findOrFail($movData['cuenta_id']);

                $operacion->movimientos()->create([
                    'cuenta_id'             => $cuenta->id,
                    'moneda_id'             => $cuenta->moneda_id,
                    'monto'                 => $movData['monto'],
                    'tasa_a_usd'            => $movData['tasa_a_usd'],
                    'monto_usd_equivalente' => round($movData['monto'] * $movData['tasa_a_usd'], 2),
                    'orden'                 => $index + 1,
                ]);
            }

            if ($tipo->genera_ganancia) {
                $operacion->setRelation('tipoOperacion', $tipo);
                $operacion->load('movimientos.moneda');

                $ganancia = $this->cierreService->calcularGanancia($operacion);

                $operacion->update([
                    'ganancia_bruta_usd' => $ganancia['usd'],
                    'ganancia_bruta_ves' => $ganancia['ves'],
                ]);
            }

            // Aplicar comisiones automáticamente (idempotente, incluye recálculo de netas)
            $operacion->load(['movimientos.cuenta.banco', 'movimientos.moneda', 'operador.titular', 'tipoOperacion']);
            $this->comisionesService->aplicarAOperacion($operacion);

            // Actualizar saldo de cuentas afectadas automáticamente
            $cuentaIdsAfectadas = [];
            foreach ($payload['movimientos'] as $movData) {
                $cuentaIdsAfectadas[] = $movData['cuenta_id'];
                $cuenta = Cuenta::find($movData['cuenta_id']);
                if ($cuenta && $cuenta->saldo_cache_at) {
                    $nuevoSaldo = bcadd($cuenta->saldo_cache, $movData['monto'], 2);
                    $cuenta->update(['saldo_cache' => $nuevoSaldo]);
                }
            }

            RecalcularSaldoCuentaJob::dispatch(array_unique($cuentaIdsAfectadas));

            if ($tipo->afecta_fifo) {
                ProcesarFifoOperacionJob::dispatch($operacion->id);
            }

            return $operacion->fresh(['movimientos.cuenta', 'tipoOperacion']);
        });
    }

    /**
     * Resuelve tasa sugerida, efectiva, diaria_id y flag sin_tasa_referencia.
     *
     * Reglas:
     * - Para tipos con dirección (venta/compra): busca tasa diaria vigente.
     *   Si no existe, usa la última publicada y marca sin_tasa_referencia=true.
     *   Si ninguna existe, lanza ValidationException.
     * - Valida que la tasa efectiva enviada por el operador no sea desfavorable a la casa.
     *
     * @return array{0: ?TasaDiaria, 1: ?float, 2: ?float, 3: bool}
     */
    private function resolverTasa(array $payload, TipoOperacion $tipo): array
    {
        $direccion = $this->tasaService->direccionDeTipo($tipo->codigo);

        // Sin dirección → no aplica tasa diaria
        if ($direccion === null) {
            $tasaEfectiva = $payload['tasa_aplicada'] ?? null;
            return [null, null, $tasaEfectiva, false];
        }

        // Enriquecer movimientos con moneda_id desde la cuenta para identificarPar
        $movsConMoneda = array_map(function ($mov) {
            $cuenta = Cuenta::find($mov['cuenta_id']);
            return array_merge($mov, ['moneda_id' => $cuenta?->moneda_id]);
        }, $payload['movimientos']);

        $par = $this->tasaService->identificarPar($movsConMoneda);

        $sinTasaReferencia = false;
        $tasaDiaria = $this->tasaService->obtenerVigente(
            $par['moneda_base_id'],
            $par['moneda_cotizada_id'],
        );

        if ($tasaDiaria === null) {
            $tasaDiaria = $this->tasaService->obtenerUltimaPublicada(
                $par['moneda_base_id'],
                $par['moneda_cotizada_id'],
            );

            if ($tasaDiaria === null) {
                $base      = Moneda::find($par['moneda_base_id'])?->codigo ?? "ID:{$par['moneda_base_id']}";
                $cotizada  = Moneda::find($par['moneda_cotizada_id'])?->codigo ?? "ID:{$par['moneda_cotizada_id']}";
                throw ValidationException::withMessages([
                    'tasa' => "No existe tasa configurada para el par {$base}/{$cotizada}. Solicite al admin que la publique.",
                ]);
            }

            $sinTasaReferencia = true;
        }

        $tasaSugerida = $direccion === 'venta'
            ? (float) $tasaDiaria->tasa_venta
            : (float) $tasaDiaria->tasa_compra;

        $tasaEfectiva = isset($payload['tasa_aplicada'])
            ? (float) $payload['tasa_aplicada']
            : $tasaSugerida;

        // Tasa desfavorable respecto al mínimo configurado: requiere justificación.
        // La justificación se toma del campo descripcion (las operaciones no tienen campo notas dedicado).
        if ($tasaDiaria->esDesfavorableParaLaCasa($tasaEfectiva, $direccion)) {
            $justificacion = trim((string) ($payload['descripcion'] ?? ''));
            if ($justificacion === '') {
                throw ValidationException::withMessages([
                    'tasa_aplicada' => 'Esta tasa es desfavorable para la casa. Debe agregar una justificación en el campo notas.',
                ]);
            }
        }

        return [$tasaDiaria, $tasaSugerida, $tasaEfectiva, $sinTasaReferencia];
    }

    /**
     * Valida los movimientos según las reglas del tipo de operación.
     *
     * Reglas por tipo:
     * - ajuste_apertura : exactamente 1 movimiento (saldo inicial).
     * - gasto / comision / ajuste : mínimo 1, sin invariante de cuadre.
     * - venta_usd / compra_usd / cambio / traslado : mínimo 2 y Σ(monto×tasa_a_usd) ≈ 0.
     *
     * Siempre: ninguna cuenta_id puede corresponder a una cuenta inactiva.
     */
    private function validarMovimientos(array $movs, TipoOperacion $tipo): void
    {
        $cuentaIds = array_column($movs, 'cuenta_id');
        $inactivas = Cuenta::whereIn('id', $cuentaIds)->where('activa', false)->get();

        if ($inactivas->isNotEmpty()) {
            $aliases = $inactivas->pluck('alias')->join(', ');
            throw ValidationException::withMessages([
                'movimientos' => "Las siguientes cuentas están inactivas: {$aliases}.",
            ]);
        }

        if ($tipo->codigo === 'ajuste_apertura') {
            if (count($movs) !== 1) {
                throw ValidationException::withMessages([
                    'movimientos' => 'El tipo ajuste_apertura permite exactamente 1 movimiento.',
                ]);
            }
            return;
        }

        if (in_array($tipo->codigo, ['gasto', 'comision', 'ajuste'])) {
            return;
        }

        if (in_array($tipo->codigo, ['venta_usd', 'compra_usd', 'cambio', 'traslado'])) {
            if (count($movs) < 2) {
                throw ValidationException::withMessages([
                    'movimientos' => "El tipo {$tipo->codigo} requiere mínimo 2 movimientos.",
                ]);
            }

            $sumaUsd = array_sum(
                array_map(fn($m) => (float) $m['monto'] * (float) $m['tasa_a_usd'], $movs)
            );

            if (abs($sumaUsd) > self::TOLERANCIA_USD) {
                throw ValidationException::withMessages([
                    'movimientos' => sprintf(
                        'Los movimientos no cuadran en partida doble. Diferencia: %s USD (tolerancia permitida: %s USD).',
                        number_format($sumaUsd, 6),
                        self::TOLERANCIA_USD
                    ),
                ]);
            }
        }
    }

    /**
     * Actualiza una operación existente.
 *
 * Flujo:
 * 1. Valida que la operación sea editable (no verificada, no cancelada).
 * 2. Si cambian los movimientos, elimina los anteriores y crea los nuevos.
 * 3. Si cambia la tasa o los montos, recalcula ganancia bruta.
 * 4. Re-aplica comisiones automáticamente.
 * 5. Registra en bitácora quién editó y qué cambió.
 * 6. Despacha jobs de recálculo de saldos y FIFO.
 *
 * @param Operacion $operacion
 * @param array $payload
 * @param \App\Models\User $editor
 * @return Operacion
 */
public function actualizar(Operacion $operacion, array $payload, \App\Models\User $editor): Operacion
{
    // La validación de editable (no verificada, no cancelada) ya está en el FormRequest.

    return DB::transaction(function () use ($operacion, $payload, $editor) {
        $cambios = [];

        // ── 1. Actualizar campos básicos ────────────────────────────────
        $camposBasicos = [
            'fecha',
            'cliente_id',
            'categoria_gasto_id',
            'operador_id',
            'tasa_aplicada',
            'genera_comision',
            'monto_comision',
            'tipo_comision',
            'tasa_mercado_snapshot',
            'fuente_tasa_mercado',
            'referencia',
            'descripcion',
        ];

        foreach ($camposBasicos as $campo) {
            if (array_key_exists($campo, $payload)) {
                $valorAnterior = $operacion->$campo;
                $operacion->$campo = $payload[$campo];
                if ($valorAnterior != $payload[$campo]) {
                    $cambios[$campo] = ['anterior' => $valorAnterior, 'nuevo' => $payload[$campo]];
                }
            }
        }

        // ── 2. Si vienen movimientos nuevos, reemplazar ────────────────
        if (!empty($payload['movimientos'])) {
            $movimientosAnteriores = $operacion->movimientos->map(function ($m) {
                return [
                    'cuenta_id'  => $m->cuenta_id,
                    'monto'      => $m->monto,
                    'tasa_a_usd' => $m->tasa_a_usd,
                    'moneda_id'  => $m->moneda_id,
                ];
            })->toArray();

            // Auto-calcular tasa_a_usd si no viene en el payload
            $tasaAplicada = (float) ($payload['tasa_aplicada'] ?? $operacion->tasa_aplicada);
            $payload['movimientos'] = array_map(function ($mov) use ($tasaAplicada) {
                if (!empty($mov['tasa_a_usd'])) {
                    return $mov;
                }
                $cuenta = Cuenta::with('moneda')->find($mov['cuenta_id']);
                $codigo = $cuenta?->moneda?->codigo;
                $mov['tasa_a_usd'] = match ($codigo) {
                    'USD', 'USDT' => 1.0,
                    default       => $tasaAplicada > 0 ? round(1 / $tasaAplicada, 8) : 1.0,
                };
                return $mov;
            }, $payload['movimientos']);

            // Validar los nuevos movimientos
            $tipo = $operacion->tipoOperacion;
            $this->validarMovimientos($payload['movimientos'], $tipo);

            // Eliminar movimientos anteriores
            $operacion->movimientos()->delete();

            // Crear nuevos movimientos
            foreach ($payload['movimientos'] as $index => $movData) {
                $cuenta = Cuenta::findOrFail($movData['cuenta_id']);

                $operacion->movimientos()->create([
                    'cuenta_id'             => $cuenta->id,
                    'moneda_id'             => $cuenta->moneda_id,
                    'monto'                 => $movData['monto'],
                    'tasa_a_usd'            => $movData['tasa_a_usd'],
                    'monto_usd_equivalente' => round($movData['monto'] * $movData['tasa_a_usd'], 2),
                    'orden'                 => $index + 1,
                ]);
            }

            $cambios['movimientos'] = [
                'anterior' => $movimientosAnteriores,
                'nuevo'    => $payload['movimientos'],
            ];
        }

        // ── 3. Guardar cambios básicos ──────────────────────────────────
        $operacion->save();

        // ── 4. Recalcular ganancia bruta ────────────────────────────────
        $tipo = $operacion->tipoOperacion;
        if ($tipo->genera_ganancia) {
            $operacion->load('movimientos.moneda');

            $ganancia = $this->cierreService->calcularGanancia($operacion);

            $operacion->update([
                'ganancia_bruta_usd' => $ganancia['usd'],
                'ganancia_bruta_ves' => $ganancia['ves'],
            ]);
        }

        // ── 5. Re-aplicar comisiones ────────────────────────────────────
        $operacion->load(['movimientos.cuenta.banco', 'movimientos.moneda', 'operador.titular', 'tipoOperacion']);
        $this->comisionesService->aplicarAOperacion($operacion);

        // ── 6. Registrar en bitácora ─────────────────────────────────────
        if (!empty($cambios)) {
            activity()
                ->performedOn($operacion)
                ->causedBy($editor)
                ->withProperties([
                    'cambios'        => $cambios,
                    'motivo_edicion' => $payload['motivo_edicion'] ?? 'Sin motivo especificado',
                ])
                ->log('Operación editada');
        }

        // ── 7. Actualizar saldo de cuentas afectadas ───────────────────
        if (!empty($payload['movimientos'])) {
            $cuentaIdsAfectadas = [];
            foreach ($payload['movimientos'] as $movData) {
                $cuentaIdsAfectadas[] = $movData['cuenta_id'];
                $cuenta = Cuenta::find($movData['cuenta_id']);
                if ($cuenta && $cuenta->saldo_cache_at) {
                    $nuevoSaldo = bcadd($cuenta->saldo_cache, $movData['monto'], 2);
                    $cuenta->update(['saldo_cache' => $nuevoSaldo]);
                }
            }
            RecalcularSaldoCuentaJob::dispatch(array_unique($cuentaIdsAfectadas));
        }

        if ($tipo->afecta_fifo) {
            ProcesarFifoOperacionJob::dispatch($operacion->id);
        }

        return $operacion->fresh(['movimientos.cuenta', 'tipoOperacion']);
    });
}

    /**
     * Registra una operación intermediada (spread entre tasas de compra y venta).
     *
     * Flujo:
     * 1. Valida movimientos específicos de intermediada (mínimo 4).
     * 2. Crea operación con cliente_emisor_id y cliente_receptor_id.
     * 3. Crea movimientos sin validar cuadre estricto.
     * 4. Calcula ganancia como spread entre tasas.
     * 5. Aplica comisiones si corresponde.
     *
     * @param array $payload
     * @param TipoOperacion $tipo
     * @return Operacion
     */
    private function registrarIntermediada(array $payload, TipoOperacion $tipo): Operacion
    {
        $this->validarMovimientosIntermediada($payload['movimientos']);

        return DB::transaction(function () use ($payload, $tipo) {
            $operacion = Operacion::create([
                'fecha'                  => $payload['fecha'],
                'tipo_operacion_id'      => $tipo->id,
                'cliente_emisor_id'      => $payload['cliente_emisor_id'],
                'cliente_receptor_id'    => $payload['cliente_receptor_id'],
                'operador_id'            => $payload['operador_id'],
                'tasa_compra'            => $payload['tasa_compra'],
                'tasa_venta'             => $payload['tasa_venta'],
                'tasa_aplicada'          => null,
                'descripcion'            => $payload['descripcion'] ?? null,
                'estatus'                => 'sin_verificar',
                'estado_pool'            => 'pendiente',
                'origen'                 => $payload['origen'] ?? 'manual',
            ]);

            foreach ($payload['movimientos'] as $index => $movData) {
                $cuenta = Cuenta::findOrFail($movData['cuenta_id']);
                $operacion->movimientos()->create([
                    'cuenta_id'             => $cuenta->id,
                    'moneda_id'             => $cuenta->moneda_id,
                    'monto'                 => $movData['monto'],
                    'tasa_a_usd'            => $movData['tasa_a_usd'] ?? 1,
                    'monto_usd_equivalente' => round($movData['monto'] * ($movData['tasa_a_usd'] ?? 1), 2),
                    'orden'                 => $index + 1,
                ]);
            }

            // Calcular ganancia
            $operacion->load('movimientos.moneda');
            $ganancia = $this->calcularGananciaBrutaIntermediada($operacion);
            $operacion->update([
                'ganancia_bruta_usd' => $ganancia['usd'],
                'ganancia_bruta_ves' => $ganancia['ves'],
            ]);

            // Aplicar comisiones si corresponde
            $operacion->load(['movimientos.cuenta.banco', 'movimientos.moneda', 'operador.titular', 'tipoOperacion']);
            $this->comisionesService->aplicarAOperacion($operacion);

            // Actualizar saldo de cuentas afectadas automáticamente
            $cuentaIdsAfectadas = [];
            foreach ($payload['movimientos'] as $movData) {
                $cuentaIdsAfectadas[] = $movData['cuenta_id'];
                $cuenta = Cuenta::find($movData['cuenta_id']);
                if ($cuenta && $cuenta->saldo_cache_at) {
                    $nuevoSaldo = bcadd($cuenta->saldo_cache, $movData['monto'], 2);
                    $cuenta->update(['saldo_cache' => $nuevoSaldo]);
                }
            }
            RecalcularSaldoCuentaJob::dispatch(array_unique($cuentaIdsAfectadas));

            return $operacion->fresh(['movimientos.cuenta', 'tipoOperacion']);
        });
    }

    /**
     * Valida movimientos específicos para operaciones intermediadas.
     *
     * @param array $movs
     * @return void
     * @throws ValidationException
     */
    private function validarMovimientosIntermediada(array $movs): void
    {
        if (count($movs) < 4) {
            throw ValidationException::withMessages([
                'movimientos' => 'La operación intermediada requiere al menos 4 movimientos.',
            ]);
        }

        $cuentaIds = array_column($movs, 'cuenta_id');
        $inactivas = Cuenta::whereIn('id', $cuentaIds)->where('activa', false)->get();
        if ($inactivas->isNotEmpty()) {
            $aliases = $inactivas->pluck('alias')->join(', ');
            throw ValidationException::withMessages([
                'movimientos' => "Las siguientes cuentas están inactivas: {$aliases}.",
            ]);
        }
        // No validamos cuadre estricto porque la ganancia es la diferencia
    }

    /**
     * Calcula la ganancia bruta para operaciones intermediadas.
     *
     * La ganancia es el spread entre la tasa de venta y la tasa de compra,
     * aplicado al monto en divisa (USD) de la operación.
     *
     * @param Operacion $operacion
     * @return array{usd: float, ves: float}
     */
    private function calcularGananciaBrutaIntermediada(Operacion $operacion): array
    {
        $tasaCompra = (float) $operacion->tasa_compra;
        $tasaVenta  = (float) $operacion->tasa_venta;

        // Buscar el monto en divisa (USD) desde los movimientos
        $montoDivisa = $operacion->movimientos
            ->filter(fn ($m) => in_array($m->moneda->codigo, ['USD', 'USDT', 'EUR', 'COP']))
            ->sum(fn ($m) => abs((float) $m->monto)) / 2; // cada par (ida y vuelta) suma 2 veces

        $gananciaVes = $montoDivisa * ($tasaVenta - $tasaCompra);
        $gananciaUsd = $tasaVenta > 0 ? $gananciaVes / $tasaVenta : 0;

        return [
            'usd' => round($gananciaUsd, 2),
            'ves' => round($gananciaVes, 2),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    // FLUJO MULTI-PASO: solicitud → en_progreso → cerrada / cancelada
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Crea una solicitud de operación.
     * Si se incluyen transacciones en el payload, las crea en estado 'pendiente'.
     * La operación queda en estado 'solicitud' para que el operador pueda gestionar transacciones.
     *
     * @param array $payload Debe incluir: fecha, tipo_codigo, operador_id
     * @return Operacion
     */
    public function crearSolicitud(array $payload): Operacion
    {
        $tipo = TipoOperacion::where('codigo', $payload['tipo_codigo'])->firstOrFail();
        $moneda = Moneda::where('codigo', $payload['moneda_codigo'])->firstOrFail();

        $tasaAplicada = (float) ($payload['tasa_aplicada'] ?? 1);
        $payload['tasas_snapshot'] = $payload['tasas_snapshot'] ?? $this->capturarTasasSnapshot($payload, $tipo);

        return DB::transaction(function () use ($payload, $tipo, $moneda, $tasaAplicada) {
            $operacion = Operacion::create([
                'fecha'                  => $payload['fecha'],
                'tipo_operacion_id'      => $tipo->id,
                'moneda_operacion_id'    => $moneda->id,
                'cliente_id'             => $payload['cliente_id'] ?? null,
                'cliente_emisor_id'      => $payload['cliente_emisor_id'] ?? null,
                'cliente_receptor_id'    => $payload['cliente_receptor_id'] ?? null,
                'categoria_gasto_id'     => $payload['categoria_gasto_id'] ?? null,
                'operador_id'            => $payload['operador_id'],
                'tasa_aplicada'          => $tasaAplicada,
                'tasa_compra'            => $payload['tasa_compra'] ?? null,
                'tasa_venta'             => $payload['tasa_venta'] ?? null,
                'genera_comision'        => $payload['genera_comision'] ?? false,
                'monto_comision'         => $payload['monto_comision'] ?? 0,
                'tipo_comision'          => $payload['tipo_comision'] ?? null,
                'tasa_sugerida'          => $payload['tasa_sugerida'] ?? null,
                'sin_tasa_referencia'    => $payload['sin_tasa_referencia'] ?? false,
                'tasa_mercado_snapshot'  => $payload['tasa_mercado_snapshot'] ?? null,
                'fuente_tasa_mercado'    => $payload['fuente_tasa_mercado'] ?? null,
                'referencia'             => $payload['referencia'] ?? null,
                'descripcion'            => $payload['descripcion'] ?? null,
                'monto_solicitado'       => $payload['monto_solicitado'] ?? null,
                'tasas_snapshot'         => $payload['tasas_snapshot'],
                'estado'                 => 'solicitud',
                'estado_pool'            => 'pendiente',
                'origen'                 => $payload['origen'] ?? 'manual',
                'origen_referencia'      => $payload['origen_referencia'] ?? null,
            ]);

            if (!empty($payload['transacciones'])) {
                $this->validarTransaccionesSolicitud($payload['transacciones']);

                $orden = 1;
                foreach ($payload['transacciones'] as $tData) {
                    $tasaT = $tData['tasa_aplicada'] ?? $tasaAplicada;

                    $operacion->transacciones()->create([
                        'cuenta_origen_id'  => $tData['cuenta_origen_id'],
                        'cuenta_destino_id' => $tData['cuenta_destino_id'],
                        'moneda_id'         => $tData['moneda_id'],
                        'monto'             => $tData['monto'],
                        'tasa_aplicada'     => $tasaT,
                        'metodo_pago'       => $tData['metodo_pago'] ?? 'transferencia',
                        'comprobante'       => $tData['comprobante'] ?? null,
                        'estado'            => 'pendiente',
                        'orden'             => $orden++,
                    ]);
                }
            }

            return $operacion->fresh(['transacciones']);
        });
    }

    /**
     * Valida las transacciones enviadas en la solicitud.
     */
    private function validarTransaccionesSolicitud(array $transacciones): void
    {
        $cuentaIds = [];
        foreach ($transacciones as $t) {
            $cuentaIds[] = $t['cuenta_origen_id'];
            $cuentaIds[] = $t['cuenta_destino_id'];
        }

        $cuentaIds = array_unique($cuentaIds);
        $inactivas = Cuenta::whereIn('id', $cuentaIds)->where('activa', false)->get();

        if ($inactivas->isNotEmpty()) {
            $aliases = $inactivas->pluck('alias')->join(', ');
            throw ValidationException::withMessages([
                'transacciones' => "Las siguientes cuentas están inactivas: {$aliases}.",
            ]);
        }
    }

    /**
     * Captura snapshot de tasas vigentes.
     */
    private function capturarTasasSnapshot(array $payload, TipoOperacion $tipo): ?array
    {
        $snapshot = [];

        if (isset($payload['tasa_mercado_snapshot'])) {
            $snapshot['mercado'] = (float) $payload['tasa_mercado_snapshot'];
        }
        if (isset($payload['tasa_aplicada'])) {
            $snapshot['aplicada'] = (float) $payload['tasa_aplicada'];
        }
        if (isset($payload['tasa_compra'])) {
            $snapshot['compra'] = (float) $payload['tasa_compra'];
        }
        if (isset($payload['tasa_venta'])) {
            $snapshot['venta'] = (float) $payload['tasa_venta'];
        }

        return !empty($snapshot) ? $snapshot : null;
    }

    /**
     * Cierra una operación en estado 'en_progreso':
     * - Valida que tenga transacciones confirmadas
     * - Crea movimientos contables desde las transacciones confirmadas
     * - Calcula ganancia bruta
     * - Aplica comisiones
     * - Actualiza saldos y despacha jobs FIFO
     *
     * @throws ValidationException
     */
    public function cerrarOperacion(
        Operacion $operacion,
        \App\Models\User $cerrador,
        ?float $tasaMercadoSnapshot = null,
        ?string $fuenteTasaMercado = null,
    ): Operacion {
        if ($operacion->estado !== 'en_progreso') {
            throw ValidationException::withMessages([
                'estado' => 'Solo se pueden cerrar operaciones en estado "en_progreso".',
            ]);
        }

        // La tasa de mercado es obligatoria para calcular ganancia
        if ($tasaMercadoSnapshot === null || $tasaMercadoSnapshot <= 0) {
            throw ValidationException::withMessages([
                'tasa_mercado_snapshot' => 'La tasa de mercado es obligatoria para cerrar la operación.',
            ]);
        }

        $transaccionesConfirmadas = $operacion->transacciones()
            ->where('estado', 'confirmada')
            ->get();

        if ($transaccionesConfirmadas->isEmpty()) {
            throw ValidationException::withMessages([
                'transacciones' => 'Debe haber al menos una transacción confirmada para cerrar la operación.',
            ]);
        }

        // Validar comprobante obligatorio para métodos no efectivo
        $this->cierreService->validarComprobantes($transaccionesConfirmadas);

        // Validar que las transacciones estén balanceadas (divisa vs VES)
        $this->cierreService->validarBalance($operacion, $transaccionesConfirmadas);

        return DB::transaction(function () use ($operacion, $transaccionesConfirmadas, $cerrador, $tasaMercadoSnapshot, $fuenteTasaMercado) {
            // Crear movimientos contables desde las transacciones confirmadas
            $this->cierreService->generarMovimientos($operacion, $transaccionesConfirmadas);

            // Cerrar operación
            $operacion->update([
                'estado'       => 'cerrada',
                'estado_pool'  => 'completada',
                'verificado_at' => now(),
                'verificado_por_id' => $cerrador->id,
            ]);

            // Actualizar snapshot de tasa de mercado al momento del cierre
            $camposSnapshot = [];
            if ($tasaMercadoSnapshot !== null) {
                $camposSnapshot['tasa_mercado_snapshot'] = $tasaMercadoSnapshot;
            }
            if ($fuenteTasaMercado !== null) {
                $camposSnapshot['fuente_tasa_mercado'] = $fuenteTasaMercado;
            }
            if (!empty($camposSnapshot)) {
                $operacion->update($camposSnapshot);
            }

            // Calcular ganancia bruta
            if ($operacion->tipoOperacion->genera_ganancia) {
                $operacion->load('movimientos.moneda');

                $ganancia = $this->cierreService->calcularGanancia($operacion);
                $operacion->update([
                    'ganancia_bruta_usd' => $ganancia['usd'],
                    'ganancia_bruta_ves' => $ganancia['ves'],
                ]);
            }

            // Aplicar comisiones
            $operacion->load(['movimientos.cuenta.banco', 'movimientos.moneda', 'operador.titular', 'tipoOperacion']);
            $this->comisionesService->aplicarAOperacion($operacion);

            // Actualizar saldos y despachar jobs
            RecalcularSaldoCuentaJob::dispatch($this->cierreService->cuentasAfectadas($transaccionesConfirmadas));

            if ($operacion->tipoOperacion?->afecta_fifo) {
                ProcesarFifoOperacionJob::dispatch($operacion->id);
            }

            // Bitácora
            activity('operaciones')
                ->performedOn($operacion)
                ->causedBy($cerrador)
                ->withProperties([
                    'transacciones_confirmadas' => $transaccionesConfirmadas->count(),
                    'movimientos_creados'       => $operacion->movimientos()->count(),
                ])
                ->event('operacion_cerrada')
                ->log('Operación cerrada con ' . $transaccionesConfirmadas->count() . ' transacciones');

            return $operacion->fresh(['movimientos.cuenta', 'tipoOperacion', 'transacciones']);
        });
    }

    /**
     * Cancela una operación en estado solicitud o en_progreso.
     * Si tiene transacciones confirmadas, las revierte (reingresa saldos).
     * Marca todas las transacciones pendientes como canceladas.
     *
     * @throws ValidationException
     */
    public function cancelarOperacion(Operacion $operacion, \App\Models\User $cancelador, ?string $motivo = null): Operacion
    {
        if (! in_array($operacion->estado, ['solicitud', 'en_progreso'])) {
            throw ValidationException::withMessages([
                'estado' => 'Solo se pueden cancelar operaciones en estado "solicitud" o "en_progreso".',
            ]);
        }

        return DB::transaction(function () use ($operacion, $cancelador, $motivo) {
            // Revertir transacciones confirmadas
            $confirmadas = $operacion->transacciones()->where('estado', 'confirmada')->get();
            foreach ($confirmadas as $t) {
                $cuentaOrigen = Cuenta::findOrFail($t->cuenta_origen_id);
                // Solo re-incrementamos saldo para cuentas de Intermedius
                if ($cuentaOrigen->titular_id) {
                    $saldoAntes = $cuentaOrigen->saldo_cache;
                    $nuevoSaldo = bcadd($saldoAntes, $t->monto, 2);
                    $cuentaOrigen->update(['saldo_cache' => $nuevoSaldo]);
                }

                $t->update(['estado' => 'revertida', 'motivo_rechazo' => $motivo ?? 'Operación cancelada']);
            }

            // Cancelar transacciones pendientes
            $operacion->transacciones()->where('estado', 'pendiente')->update(['estado' => 'cancelada']);

            // Cancelar operación
            $operacion->update([
                'estado'              => 'cancelada',
                'cancelada_at'        => now(),
                'motivo_cancelacion'  => $motivo,
            ]);

            activity('operaciones')
                ->performedOn($operacion)
                ->causedBy($cancelador)
                ->withProperties([
                    'transacciones_revertidas' => $confirmadas->count(),
                    'motivo'                   => $motivo,
                ])
                ->event('operacion_cancelada')
                ->log('Operación cancelada');

            return $operacion->fresh(['transacciones']);
        });
    }

    /**
     * Pasa una operación de 'solicitud' a 'en_progreso'.
     *
     * @throws ValidationException
     */
    public function iniciarOperacion(Operacion $operacion): Operacion
    {
        if ($operacion->estado !== 'solicitud') {
            throw ValidationException::withMessages([
                'estado' => 'Solo se puede iniciar operaciones en estado "solicitud".',
            ]);
        }

        $operacion->update([
            'estado'        => 'en_progreso',
            'en_progreso_at' => now(),
        ]);

        return $operacion->fresh();
    }

    // ═══════════════════════════════════════════════════════════════════
    // FLUJO VENTA: creación atómica con cierre inmediato
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Crea una operación de venta y la cierra inmediatamente.
     *
     * Flujo atómico:
     * 1. Crea cabecera de operación en estado 'cerrada'
     * 2. Crea todas las transacciones como 'confirmada'
     * 3. Valida balance (suma divisa = monto_solicitado, suma VES = monto × tasa)
     * 4. Genera movimientos contables desde las transacciones
     * 5. Calcula ganancia bruta
     * 6. Aplica comisiones automáticas
     * 7. Despacha jobs de recálculo de saldos y FIFO
     *
     * @param array $payload Validado por VentaOperacionRequest
     * @return Operacion
     * @throws ValidationException
     */
    public function crearVenta(array $payload): Operacion
    {
        $tipo = TipoOperacion::where('codigo', 'venta_usd')->firstOrFail();
        $moneda = Moneda::where('codigo', $payload['moneda_codigo'])->firstOrFail();
        $tasaAplicada = (float) $payload['tasa_aplicada'];

        return DB::transaction(function () use ($payload, $tipo, $moneda, $tasaAplicada) {
            // ── 1. Crear cabecera de operación ─────────────────────────
            $operacion = Operacion::create([
                'fecha'                  => $payload['fecha'],
                'tipo_operacion_id'      => $tipo->id,
                'moneda_operacion_id'    => $moneda->id,
                'cliente_id'             => $payload['cliente_id'],
                'operador_id'            => $payload['operador_id'],
                'tasa_aplicada'          => $tasaAplicada,
                'monto_solicitado'       => $payload['monto_solicitado'],
                'tasa_mercado_snapshot'  => $payload['tasa_mercado_snapshot'] ?? null,
                'fuente_tasa_mercado'    => $payload['fuente_tasa_mercado'] ?? null,
                'descripcion'            => $payload['descripcion'] ?? null,
                'referencia'             => $payload['referencia'] ?? null,
                'origen'                 => $payload['origen'] ?? 'manual',
                'estado'                 => 'cerrada',
                'estado_pool'            => 'completada',
                'verificado_at'          => now(),
                'verificado_por_id'      => $payload['operador_id'],
            ]);

            // ── 2. Crear transacciones como 'confirmada' ──────────────
            $orden = 1;
            foreach ($payload['transacciones'] as $tData) {
                $operacion->transacciones()->create([
                    'cuenta_origen_id'    => $tData['cuenta_origen_id'] ?? null,
                    'cuenta_destino_id'   => $tData['cuenta_destino_id'] ?? null,
                    'moneda_id'           => $tData['moneda_id'],
                    'cliente_id'          => $tData['cliente_id'] ?? $payload['cliente_id'],
                    'monto'               => $tData['monto'],
                    'tasa_aplicada'       => $tasaAplicada,
                    'metodo_pago'         => $tData['metodo_pago'] ?? 'transferencia',
                    'comprobante'         => $tData['comprobante'] ?? null,
                    'estado'              => 'confirmada',
                    'confirmada_en'       => now(),
                    'confirmada_por_id'   => $payload['operador_id'],
                    'orden'               => $orden++,
                ]);
            }

            // ── 3. Validar balance ─────────────────────────────────────
            $transacciones = $operacion->transacciones()->get();
            $this->cierreService->validarBalance($operacion, $transacciones);

            // ── 4. Generar movimientos contables ───────────────────────
            $this->cierreService->generarMovimientos($operacion, $transacciones);

            // ── 5. Calcular ganancia bruta ─────────────────────────────
            if ($tipo->genera_ganancia) {
                $operacion->setRelation('tipoOperacion', $tipo);
                $operacion->load('movimientos.moneda');

                $ganancia = $this->cierreService->calcularGanancia($operacion);
                $operacion->update([
                    'ganancia_bruta_usd' => $ganancia['usd'],
                    'ganancia_bruta_ves' => $ganancia['ves'],
                ]);
            }

            // ── 6. Aplicar comisiones ──────────────────────────────────
            $operacion->load(['movimientos.cuenta.banco', 'movimientos.moneda', 'operador.titular', 'tipoOperacion']);
            $this->comisionesService->aplicarAOperacion($operacion);

            // ── 7. Actualizar saldos y despachar jobs ──────────────────
            RecalcularSaldoCuentaJob::dispatch($this->cierreService->cuentasAfectadas($transacciones));

            if ($tipo->afecta_fifo) {
                ProcesarFifoOperacionJob::dispatch($operacion->id);
            }

            // ── 8. Bitácora ────────────────────────────────────────────
            activity('operaciones')
                ->performedOn($operacion)
                ->causedBy($payload['usuario'] ?? \App\Models\User::find($payload['operador_id']))
                ->withProperties([
                    'transacciones_creadas' => $transacciones->count(),
                    'movimientos_creados'   => $operacion->movimientos()->count(),
                    'tipo'                  => 'venta_inmediata',
                ])
                ->event('venta_cerrada')
                ->log('Venta creada y cerrada con ' . $transacciones->count() . ' transacciones');

            return $operacion->fresh(['movimientos.cuenta', 'tipoOperacion', 'transacciones.moneda', 'transacciones.cliente']);
        });
    }

    // ═══════════════════════════════════════════════════════════════════
    // REVERSIÓN DE VENTAS: deshace una operación de venta cerrada
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Revierte una operación de venta ya cerrada.
     *
     * Crea movimientos inversos (egreso de Bs, ingreso de divisa)
     * y marca la operación con revertida_at.
     * Solo admins/super_admin pueden revertir.
     * No se permite revertir operaciones con más de 30 días de antigüedad.
     *
     * @throws ValidationException
     */
    public function revertirOperacion(Operacion $operacion, \App\Models\User $usuario, string $motivo): Operacion
    {
        if ($operacion->estado !== 'cerrada') {
            throw ValidationException::withMessages([
                'estado' => 'Solo se pueden revertir operaciones en estado "cerrada".',
            ]);
        }

        if ($operacion->revertida_at) {
            throw ValidationException::withMessages([
                'revertida_at' => 'Esta operación ya fue revertida.',
            ]);
        }

        if ($operacion->tipoOperacion->codigo !== 'venta_usd') {
            throw ValidationException::withMessages([
                'tipo_operacion_id' => 'Solo se pueden revertir operaciones de tipo venta.',
            ]);
        }

        if ($operacion->created_at->diffInDays(now()) > 30) {
            throw ValidationException::withMessages([
                'fecha' => 'No se pueden revertir operaciones con más de 30 días de antigüedad.',
            ]);
        }

        return DB::transaction(function () use ($operacion, $usuario, $motivo) {
            $transaccionesOriginales = $operacion->transacciones()
                ->where('estado', 'confirmada')
                ->get();

            // Crear transacciones inversas (confirmadas directamente)
            $orden = $operacion->transacciones()->max('orden') + 1;
            foreach ($transaccionesOriginales as $t) {
                $operacion->transacciones()->create([
                    'cuenta_origen_id'    => $t->cuenta_destino_id,
                    'cuenta_destino_id'   => $t->cuenta_origen_id,
                    'moneda_id'           => $t->moneda_id,
                    'cliente_id'          => $t->cliente_id,
                    'monto'               => $t->monto,
                    'tasa_aplicada'       => $t->tasa_aplicada,
                    'metodo_pago'         => $t->metodo_pago,
                    'comprobante'         => null,
                    'estado'              => 'confirmada',
                    'confirmada_en'       => now(),
                    'confirmada_por_id'   => $usuario->id,
                    'orden'               => $orden++,
                ]);
            }

            // Crear movimientos inversos
            $transaccionesInversas = $operacion->transacciones()
                ->where('orden', '>=', $orden - $transaccionesOriginales->count())
                ->get();

            foreach ($transaccionesInversas as $i => $t) {
                $esFiat = in_array($t->moneda->codigo ?? '', ['USD', 'USDT']);
                $tasaUsd = $esFiat ? 1.0 : ($t->tasa_aplicada ? round(1 / $t->tasa_aplicada, 8) : 1.0);
                $movOrden = $operacion->movimientos()->max('orden') + 1;

                if ($t->cuenta_origen_id) {
                    $operacion->movimientos()->create([
                        'cuenta_id'             => $t->cuenta_origen_id,
                        'moneda_id'             => $t->moneda_id,
                        'monto'                 => -$t->monto,
                        'tasa_a_usd'            => $tasaUsd,
                        'monto_usd_equivalente' => round($t->monto * $tasaUsd, 2),
                        'orden'                 => $movOrden++,
                    ]);
                }

                if ($t->cuenta_destino_id) {
                    $operacion->movimientos()->create([
                        'cuenta_id'             => $t->cuenta_destino_id,
                        'moneda_id'             => $t->moneda_id,
                        'monto'                 => $t->monto,
                        'tasa_a_usd'            => $tasaUsd,
                        'monto_usd_equivalente' => round($t->monto * $tasaUsd, 2),
                        'orden'                 => $movOrden,
                    ]);
                }
            }

            // Marcar operación como revertida
            $operacion->update([
                'revertida_at' => now(),
            ]);

            // Bitácora
            activity('operaciones')
                ->performedOn($operacion)
                ->causedBy($usuario)
                ->withProperties([
                    'transacciones_inversas' => $transaccionesInversas->count(),
                    'motivo'                 => $motivo,
                ])
                ->event('operacion_revertida')
                ->log('Venta revertida con ' . $transaccionesInversas->count() . ' transacciones inversas');

            return $operacion->fresh(['transacciones', 'movimientos']);
        });
    }

    /**
     * Calcula la ganancia estimada de una operación SIN persistir.
     * Usa las transacciones confirmadas + la tasa de mercado actual.
     * Para uso en preview antes de cerrar.
     *
     * @return array{bruta_usd: float, bruta_ves: float, neta_usd: float, neta_ves: float}
     */
    public function calcularGananciaEstimada(Operacion $operacion, ?float $tasaMercado = null): array
    {
        $tipo = $operacion->tipoOperacion;

        if (!$tipo->genera_ganancia) {
            return ['bruta_usd' => 0.0, 'bruta_ves' => 0.0, 'neta_usd' => 0.0, 'neta_ves' => 0.0];
        }

        $codigoDivisa = $operacion->monedaOperacion?->codigo ?? 'USD';
        $tasaMercado = $tasaMercado ?? (float) $operacion->tasa_mercado_snapshot;
        $tasaAplicada = (float) $operacion->tasa_aplicada;

        if ($tasaMercado <= 0 || $tasaAplicada <= 0) {
            return ['bruta_usd' => 0.0, 'bruta_ves' => 0.0, 'neta_usd' => 0.0, 'neta_ves' => 0.0];
        }

        $transacciones = $operacion->transacciones()
            ->where('estado', 'confirmada')
            ->get();

        if ($transacciones->isEmpty()) {
            $totalDivisa = (float) $operacion->monto_solicitado;
            $totalVes = $totalDivisa * $tasaAplicada;
        } else {
            $totalDivisa = $transacciones
                ->filter(fn ($t) => ($t->moneda->codigo ?? '') === $codigoDivisa)
                ->sum(fn ($t) => (float) $t->monto);

            $totalVes = $transacciones
                ->filter(fn ($t) => ($t->moneda->codigo ?? '') === 'VES')
                ->sum(fn ($t) => (float) $t->monto);
        }

        $gananciaVes = 0.0;
        $gananciaUsd = 0.0;

        switch ($tipo->codigo) {
            case 'venta_usd':
                // La casa vende divisa: movimiento negativo en divisa, positivo en VES
                $montoDivisaVendido = abs($totalDivisa);
                $gananciaVes = $montoDivisaVendido * ($tasaAplicada - $tasaMercado);
                $gananciaUsd = $gananciaVes / $tasaAplicada;
                break;

            case 'compra_usd':
                // La casa compra divisa: movimiento positivo en divisa, negativo en VES
                $montoDivisaComprado = abs($totalDivisa);
                $gananciaVes = $montoDivisaComprado * ($tasaMercado - $tasaAplicada);
                $gananciaUsd = $gananciaVes / $tasaMercado;
                break;

            case 'intermediada':
                $montoDivisa = abs($totalDivisa) / 2;
                $tasaCompra = (float) $operacion->tasa_compra;
                $tasaVenta = (float) $operacion->tasa_venta;
                $gananciaVes = $montoDivisa * ($tasaVenta - $tasaCompra);
                $gananciaUsd = $tasaVenta > 0 ? $gananciaVes / $tasaVenta : 0;
                break;

            case 'comision':
                $movIngreso = $transacciones->first(fn ($t) => (float) $t->monto > 0);
                if ($movIngreso) {
                    $gananciaUsd = (float) ($movIngreso->monto_usd_equivalente ?? 0);
                    $gananciaVes = ($movIngreso->moneda->codigo ?? '') === 'VES'
                        ? (float) $movIngreso->monto
                        : ($tasaMercado > 0 ? round($gananciaUsd * $tasaMercado, 2) : 0.0);
                }
                break;
        }

        $brutaUsd = round($gananciaUsd, 2);
        $brutaVes = round($gananciaVes, 2);

        // Calcular netas restando comisiones existentes
        $comisiones = $operacion->comisiones()->get();
        $totalComisionesUsd = $comisiones->sum(fn ($c) => (float) ($c->monto_usd_equivalente ?? 0));
        $totalComisionesVes = $comisiones->sum(fn ($c) => ($c->moneda->codigo ?? '') === 'VES'
            ? (float) $c->monto
            : (float) ($c->monto_usd_equivalente ?? 0) * $tasaAplicada
        );

        return [
            'bruta_usd' => $brutaUsd,
            'bruta_ves' => $brutaVes,
            'neta_usd'  => round($brutaUsd - $totalComisionesUsd, 2),
            'neta_ves'  => round($brutaVes - $totalComisionesVes, 2),
        ];
    }
}
