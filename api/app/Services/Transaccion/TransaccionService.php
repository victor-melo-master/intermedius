<?php

namespace App\Services\Transaccion;

use App\Models\Cuenta;
use App\Models\Operacion;
use App\Models\Transaccion;
use App\Models\User;
use App\Services\Transaccion\SaldoValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransaccionService
{
    public function __construct(
        private readonly SaldoValidator $saldoValidator,
    ) {}

    /**
     * Crea un batch de transacciones para una operación (solicitud o en_progreso).
     * NO valida saldo aquí — eso se hace al confirmar.
     */
    public function crearTransacciones(Operacion $operacion, array $transaccionesData): \Illuminate\Support\Collection
    {
        $transacciones = collect();

        DB::transaction(function () use ($operacion, $transaccionesData, &$transacciones) {
            foreach ($transaccionesData as $i => $data) {
                $transaccion = $operacion->transacciones()->create([
                    'cuenta_origen_id'  => $data['cuenta_origen_id'],
                    'cuenta_destino_id' => $data['cuenta_destino_id'],
                    'moneda_id'         => $data['moneda_id'],
                    'monto'             => $data['monto'],
                    'tasa_aplicada'     => $data['tasa_aplicada'] ?? null,
                    'tasas_snapshot'    => $data['tasas_snapshot'] ?? null,
                    'metodo_pago'       => $data['metodo_pago'] ?? null,
                    'comprobante'       => $data['comprobante'] ?? null,
                    'estado'            => 'pendiente',
                    'orden'             => $i + 1,
                ]);

                $transacciones->push($transaccion);
            }
        });

        return $transacciones;
    }

    /**
     * Confirma una transacción: valida saldo, marca como confirmada, descuenta saldo.
     *
     * @throws ValidationException
     */
    public function confirmarTransaccion(Transaccion $transaccion, User $usuario): Transaccion
    {
        if ($transaccion->estado !== 'pendiente') {
            throw ValidationException::withMessages([
                'transaccion_id' => 'Solo se pueden confirmar transacciones en estado "pendiente".',
            ]);
        }

        $this->saldoValidator->assertSaldoSuficiente(
            $transaccion->cuenta_origen_id,
            $transaccion->moneda_id,
            $transaccion->monto,
        );

        // Solo descontamos saldo para cuentas de Intermedius
        $cuentaOrigen = Cuenta::findOrFail($transaccion->cuenta_origen_id);
        $actualizarSaldo = (bool) $cuentaOrigen->titular_id;
        $saldoAntes = $actualizarSaldo ? $cuentaOrigen->saldo_cache : null;

        DB::transaction(function () use ($transaccion, $usuario, $cuentaOrigen, $saldoAntes, $actualizarSaldo) {
            if ($actualizarSaldo) {
                $nuevoSaldo = bcsub($saldoAntes, $transaccion->monto, 2);
                $cuentaOrigen->update(['saldo_cache' => $nuevoSaldo]);
            }

            $transaccion->update([
                'estado'           => 'confirmada',
                'confirmada_en'    => now(),
                'confirmada_por_id' => $usuario->id,
            ]);

            // Bitácora
            $props = [
                'operacion_id'     => $transaccion->operacion_id,
                'cuenta_origen_id' => $transaccion->cuenta_origen_id,
                'monto'            => $transaccion->monto,
            ];
            if ($actualizarSaldo) {
                $props['saldo_anterior'] = $saldoAntes;
                $props['saldo_nuevo'] = bcsub($saldoAntes, $transaccion->monto, 2);
            }
            activity('transacciones')
                ->performedOn($transaccion)
                ->causedBy($usuario)
                ->withProperties($props)
                ->event('transaccion_confirmada')
                ->log($actualizarSaldo ? 'Transacción confirmada - saldo descontado' : 'Transacción confirmada - cuenta de cliente, saldo no modificado');
        });

        return $transaccion->fresh();
    }

    /**
     * Revuelve una transacción confirmada: reingresa saldo y marca como pendiente.
     *
     * @throws ValidationException
     */
    public function revertirTransaccion(Transaccion $transaccion, User $usuario, ?string $motivo = null): Transaccion
    {
        if ($transaccion->estado !== 'confirmada') {
            throw ValidationException::withMessages([
                'transaccion_id' => 'Solo se pueden revertir transacciones en estado "confirmada".',
            ]);
        }

        $cuentaOrigen = Cuenta::findOrFail($transaccion->cuenta_origen_id);
        $actualizarSaldo = (bool) $cuentaOrigen->titular_id;
        $saldoAntes = $actualizarSaldo ? $cuentaOrigen->saldo_cache : null;

        DB::transaction(function () use ($transaccion, $usuario, $cuentaOrigen, $saldoAntes, $motivo, $actualizarSaldo) {
            if ($actualizarSaldo) {
                $nuevoSaldo = bcadd($saldoAntes, $transaccion->monto, 2);
                $cuentaOrigen->update(['saldo_cache' => $nuevoSaldo]);
            }

            $transaccion->update([
                'estado'        => 'revertida',
                'motivo_rechazo' => $motivo ?? 'Revertida manualmente',
            ]);

            $props = [
                'operacion_id'      => $transaccion->operacion_id,
                'cuenta_origen_id'  => $transaccion->cuenta_origen_id,
                'monto'             => $transaccion->monto,
                'motivo'            => $motivo,
            ];
            if ($actualizarSaldo) {
                $props['saldo_anterior'] = $saldoAntes;
                $props['saldo_nuevo'] = bcadd($saldoAntes, $transaccion->monto, 2);
            }
            activity('transacciones')
                ->performedOn($transaccion)
                ->causedBy($usuario)
                ->withProperties($props)
                ->event('transaccion_revertida')
                ->log($actualizarSaldo ? 'Transacción revertida - saldo reingresado' : 'Transacción revertida - cuenta de cliente, saldo no modificado');
        });

        return $transaccion->fresh();
    }

    /**
     * Valida una transacción (flujo legacy de verificación).
     */
    public function validarTransaccion(Transaccion $transaccion, User $usuario): Transaccion
    {
        if ($transaccion->estado !== 'pendiente') {
            throw ValidationException::withMessages([
                'transaccion_id' => 'Solo se pueden validar transacciones en estado "pendiente".',
            ]);
        }

        $transaccion->update([
            'estado'          => 'validada',
            'validada_por_id' => $usuario->id,
        ]);

        return $transaccion->fresh();
    }

    /**
     * Rechaza una transacción pendiente.
     */
    public function rechazarTransaccion(Transaccion $transaccion, string $motivo): Transaccion
    {
        if ($transaccion->estado !== 'pendiente') {
            throw ValidationException::withMessages([
                'transaccion_id' => 'Solo se pueden rechazar transacciones en estado "pendiente".',
            ]);
        }

        $transaccion->update([
            'estado'         => 'rechazada',
            'motivo_rechazo' => $motivo,
        ]);

        return $transaccion->fresh();
    }

    /**
     * Cambia la cuenta destino de una transacción pendiente.
     */
    public function cambiarCuentaDestino(Transaccion $transaccion, int $nuevaCuentaDestinoId): Transaccion
    {
        if ($transaccion->estado !== 'pendiente') {
            throw ValidationException::withMessages([
                'transaccion_id' => 'Solo se puede cambiar la cuenta destino de transacciones pendientes.',
            ]);
        }

        $cuentaDestino = Cuenta::find($nuevaCuentaDestinoId);
        if (! $cuentaDestino || $cuentaDestino->moneda_id !== $transaccion->moneda_id) {
            throw ValidationException::withMessages([
                'cuenta_destino_id' => 'La cuenta destino no existe o no coincide con la moneda de la transacción.',
            ]);
        }

        $transaccion->update([
            'cuenta_destino_id' => $nuevaCuentaDestinoId,
        ]);

        return $transaccion->fresh();
    }

    /**
     * Cambia la cuenta origen de una transacción pendiente.
     */
    public function cambiarCuentaOrigen(Transaccion $transaccion, int $nuevaCuentaOrigenId): Transaccion
    {
        if ($transaccion->estado !== 'pendiente') {
            throw ValidationException::withMessages([
                'transaccion_id' => 'Solo se puede cambiar la cuenta origen de transacciones pendientes.',
            ]);
        }

        $this->saldoValidator->assertSaldoSuficiente(
            $nuevaCuentaOrigenId,
            $transaccion->moneda_id,
            $transaccion->monto,
        );

        $transaccion->update([
            'cuenta_origen_id' => $nuevaCuentaOrigenId,
        ]);

        return $transaccion->fresh();
    }

    /**
     * Marca una transacción pendiente como fallida.
     * Requiere una razón del fallo (ej. "saldo insuficiente", "transferencia rechazada").
     *
     * @throws ValidationException
     */
    public function fallarTransaccion(Transaccion $transaccion, User $usuario, string $razon): Transaccion
    {
        if ($transaccion->estado !== 'pendiente') {
            throw ValidationException::withMessages([
                'transaccion_id' => 'Solo se pueden marcar como fallidas transacciones en estado "pendiente".',
            ]);
        }

        $transaccion->update([
            'estado'         => 'fallido',
            'motivo_rechazo' => $razon,
        ]);

        activity('transacciones')
            ->performedOn($transaccion)
            ->causedBy($usuario)
            ->withProperties([
                'operacion_id' => $transaccion->operacion_id,
                'razon'        => $razon,
            ])
            ->event('transaccion_fallida')
            ->log('Transacción marcada como fallida: ' . $razon);

        return $transaccion->fresh();
    }

    /**
     * Cancela una transacción pendiente.
     */
    public function cancelarTransaccion(Transaccion $transaccion): Transaccion
    {
        if ($transaccion->estado !== 'pendiente') {
            throw ValidationException::withMessages([
                'transaccion_id' => 'Solo se pueden cancelar transacciones pendientes.',
            ]);
        }

        $transaccion->update(['estado' => 'cancelada']);

        return $transaccion->fresh();
    }

    /**
     * Adjunta comprobante a una transacción.
     */
    public function adjuntarComprobante(Transaccion $transaccion, string $rutaComprobante): Transaccion
    {
        $transaccion->update([
            'comprobante' => $rutaComprobante,
        ]);

        return $transaccion->fresh();
    }
}
