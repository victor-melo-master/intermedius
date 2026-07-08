<?php

namespace App\Services\Configuracion;

use App\Models\Moneda;
use App\Models\TasaDiaria;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Service for managing daily exchange rates (TasaDiaria).
 * Handles publication, lookup of vigent/last rates, rate validation against minimums,
 * direction resolution per operation type, and currency pair identification from movements.
 */
class TasaDiariaService
{
    /**
     * Publica una nueva tasa diaria para un par de monedas.
     *
     * Cierra la tasa actualmente vigente (vigente_hasta = now()) y crea la nueva.
     * Valida que tasa_venta >= tasa_compra, a menos que notas lo justifique.
     */
    public function publicar(array $payload, User $admin): TasaDiaria
    {
        $tasaVenta  = (float) $payload['tasa_venta'];
        $tasaCompra = (float) $payload['tasa_compra'];
        $notas      = $payload['notas'] ?? null;

        if ($tasaVenta < $tasaCompra) {
            if (empty($notas) || mb_strlen(trim($notas)) < 10) {
                throw ValidationException::withMessages([
                    'tasa_venta' => 'La tasa de venta debe ser >= tasa de compra. Si hay una excepción justificada, explícala en el campo notas (mínimo 10 caracteres).',
                ]);
            }
        }

        return DB::transaction(function () use ($payload, $admin, $tasaVenta, $tasaCompra) {
            $ahora = now();

            // Cerrar tasa vigente si existe
            TasaDiaria::where('moneda_base_id', $payload['moneda_base_id'])
                ->where('moneda_cotizada_id', $payload['moneda_cotizada_id'])
                ->whereNull('vigente_hasta')
                ->update(['vigente_hasta' => $ahora]);

            return TasaDiaria::create([
                'fecha'              => $payload['fecha'] ?? $ahora->toDateString(),
                'moneda_base_id'     => $payload['moneda_base_id'],
                'moneda_cotizada_id' => $payload['moneda_cotizada_id'],
                'tasa_compra'        => $tasaCompra,
                'tasa_compra_minima' => $payload['tasa_compra_minima'] ?? null,
                'tasa_venta'         => $tasaVenta,
                'tasa_venta_minima'  => $payload['tasa_venta_minima'] ?? null,
                'definida_por_id'    => $admin->id,
                'notas'              => $payload['notas'] ?? null,
                'vigente_desde'      => $ahora,
                'vigente_hasta'      => null,
            ]);
        });
    }

    /**
     * Retorna la tasa vigente para un par en el momento dado.
     * Criterio: vigente_desde <= $momento AND (vigente_hasta IS NULL OR vigente_hasta > $momento)
     */
    public function obtenerVigente(int $monedaBaseId, int $monedaCotizadaId, ?Carbon $momento = null): ?TasaDiaria
    {
        $momento ??= now();

        return TasaDiaria::where('moneda_base_id', $monedaBaseId)
            ->where('moneda_cotizada_id', $monedaCotizadaId)
            ->where('vigente_desde', '<=', $momento)
            ->where(fn ($q) => $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>', $momento))
            ->orderBy('vigente_desde', 'desc')
            ->first();
    }

    /**
     * Retorna la última tasa publicada para el par, independientemente de si está vigente hoy.
     * Usada como fallback cuando no hay tasa del día (opción C).
     */
    public function obtenerUltimaPublicada(int $monedaBaseId, int $monedaCotizadaId): ?TasaDiaria
    {
        return TasaDiaria::where('moneda_base_id', $monedaBaseId)
            ->where('moneda_cotizada_id', $monedaCotizadaId)
            ->orderByDesc('vigente_desde')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Evalúa la tasa efectiva del operador contra los mínimos configurados.
     *
     * Usa TasaDiaria::esDesfavorableParaLaCasa(). Si no hay mínimo configurado
     * para la dirección, la tasa siempre es válida y no requiere justificación.
     *
     * @param  string $direccion  'venta' | 'compra'
     * @return array{es_valida: bool, es_desfavorable: bool, requiere_justificacion: bool, mensaje: ?string}
     */
    public function validarTasaEfectiva(TasaDiaria $sugerida, float $tasaEfectiva, string $direccion): array
    {
        $esDesfavorable = $sugerida->esDesfavorableParaLaCasa($tasaEfectiva, $direccion);

        if (! $esDesfavorable) {
            return [
                'es_valida'              => true,
                'es_desfavorable'        => false,
                'requiere_justificacion' => false,
                'mensaje'                => null,
            ];
        }

        $mensaje = $direccion === 'venta'
            ? sprintf('La tasa está por debajo del mínimo de venta (%s). Debe agregar una justificación en notas.', $sugerida->tasa_venta_minima)
            : sprintf('La tasa está por encima del mínimo de compra (%s). Debe agregar una justificación en notas.', $sugerida->tasa_compra_minima);

        return [
            'es_valida'              => false,
            'es_desfavorable'        => true,
            'requiere_justificacion' => true,
            'mensaje'                => $mensaje,
        ];
    }

    /**
     * Determina la dirección (venta|compra) a partir del código del tipo de operación.
     */
    public function direccionDeTipo(string $codigoTipo): ?string
    {
        return match ($codigoTipo) {
            'venta_usd'  => 'venta',
            'compra_usd' => 'compra',
            default      => null,  // traslado, gasto, etc. no tienen dirección
        };
    }

    /**
     * Resuelve los IDs de moneda base y cotizada a partir del par de movimientos.
     * Implementa las Reglas A, B y C de identificación de par.
     *
     * REGLA A (1 movimiento)  : par = moneda_del_mov / USD
     * REGLA B (2 movimientos) : par = moneda_negativa / moneda_positiva
     * REGLA C (3+ movimientos): si 2 monedas distintas → Regla B; si 3+, lanzar excepción.
     *
     * @param  array<array{moneda_id: int, monto: float}> $movimientos
     * @return array{moneda_base_id: int, moneda_cotizada_id: int}
     */
    public function identificarPar(array $movimientos): array
    {
        $usdId = Moneda::where('codigo', 'USD')->value('id');

        $monedas = collect($movimientos)->pluck('moneda_id')->unique()->values();

        if ($monedas->count() === 1) {
            // REGLA A: operación de un solo tipo de moneda (gasto, ajuste, etc.)
            return [
                'moneda_base_id'     => $monedas->first(),
                'moneda_cotizada_id' => $usdId,
            ];
        }

        if ($monedas->count() === 2) {
            // REGLA B: par directo entre las dos monedas presentes.
            //
            // Si una de las monedas es la moneda local (VES), siempre va como cotizada
            // y la otra como base. Esto garantiza que venta_usd [-USD, +VES] y
            // compra_usd [+USD, -VES] resuelvan el mismo par (USD/VES), ya que la
            // TasaDiaria se almacena invariablemente como "cuántas VES por 1 extranjera".
            $vesId      = Moneda::where('codigo', config('sistema.moneda_local', 'VES'))->value('id');
            $otraMoneda = $monedas->first(fn ($id) => $id !== $vesId);

            if ($vesId && $monedas->contains($vesId) && $otraMoneda) {
                return [
                    'moneda_base_id'     => $otraMoneda,
                    'moneda_cotizada_id' => $vesId,
                ];
            }

            // Sin VES en el par: usar convención de signos (negativo = base, positivo = cotizada)
            $movNeg = collect($movimientos)->first(fn ($m) => (float) $m['monto'] < 0);
            $movPos = collect($movimientos)->first(fn ($m) => (float) $m['monto'] > 0);

            return [
                'moneda_base_id'     => $movNeg ? $movNeg['moneda_id'] : $monedas->first(),
                'moneda_cotizada_id' => $movPos ? $movPos['moneda_id'] : $monedas->last(),
            ];
        }

        // REGLA C: 3+ monedas distintas → no permitido
        throw ValidationException::withMessages([
            'movimientos' => 'Operación con 3+ monedas distintas no permitida. ' .
                'Desglosa en operaciones encadenadas: primero cambio A→B, luego cambio B→C. ' .
                'Si necesitas vincularlas, usa el campo "referencia" con un identificador común.',
        ]);
    }
}
