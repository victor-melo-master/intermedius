<?php

namespace App\Services\Transaccion;

use App\Models\Cuenta;
use App\Models\Operacion;
use App\Models\Transaccion;
use App\Models\User;
use App\Services\Transaccion\SaldoValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates, updates, and manages transactions for a given operation.
 * Handles account changes, validation state, and comprobante attachments.
 */
class TransaccionService
{
    public function __construct(
        private readonly SaldoValidator $saldoValidator,
    ) {}

    /**
     * Creates a batch of transactions for an operation.
     *
     * @param  Operacion                   $operacion
     * @param  array<int, array<string, mixed>>  $transaccionesData
     * @return \Illuminate\Support\Collection<int, Transaccion>
     */
    public function crearTransacciones(Operacion $operacion, array $transaccionesData): \Illuminate\Support\Collection
    {
        $transacciones = collect();

        DB::transaction(function () use ($operacion, $transaccionesData, &$transacciones) {
            foreach ($transaccionesData as $i => $data) {
                $this->saldoValidator->assertSaldoSuficiente(
                    $data['cuenta_origen_id'],
                    $data['moneda_id'],
                    $data['monto'],
                );

                $transaccion = $operacion->transacciones()->create([
                    'cuenta_origen_id'  => $data['cuenta_origen_id'],
                    'cuenta_destino_id' => $data['cuenta_destino_id'],
                    'moneda_id'         => $data['moneda_id'],
                    'monto'             => $data['monto'],
                    'estado'            => 'pendiente',
                    'orden'             => $i + 1,
                ]);

                $transacciones->push($transaccion);
            }
        });

        return $transacciones;
    }

    /**
     * Validates a transaction (marks as validated).
     *
     * @param  Transaccion  $transaccion
     * @param  User         $validador
     * @return Transaccion
     */
    public function validarTransaccion(Transaccion $transaccion, User $validador): Transaccion
    {
        if ($transaccion->estado !== 'pendiente') {
            throw ValidationException::withMessages([
                'transaccion_id' => 'Solo se pueden validar transacciones en estado "pendiente".',
            ]);
        }

        $transaccion->update([
            'estado'         => 'validada',
            'validada_en'    => now(),
            'validada_por_id' => $validador->id,
        ]);

        return $transaccion->fresh();
    }

    /**
     * Rejects a transaction with a reason.
     *
     * @param  Transaccion  $transaccion
     * @param  string       $motivo
     * @return Transaccion
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
     * Changes the destination account of a pending transaction.
     * Validates that the new account exists and matches the transaction currency.
     *
     * @param  Transaccion  $transaccion
     * @param  int          $nuevaCuentaDestinoId
     * @return Transaccion
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
     * Changes the origin account of a pending transaction (e.g., when client payment fails).
     * Re-validates that the new origin account has sufficient funds.
     *
     * @param  Transaccion  $transaccion
     * @param  int          $nuevaCuentaOrigenId
     * @return Transaccion
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
     * Cancels a pending transaction (e.g., when the whole operation is cancelled).
     *
     * @param  Transaccion  $transaccion
     * @return Transaccion
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
     * Attaches a comprobante file to a transaction.
     *
     * @param  Transaccion  $transaccion
     * @param  string       $rutaComprobante
     * @return Transaccion
     */
    public function adjuntarComprobante(Transaccion $transaccion, string $rutaComprobante): Transaccion
    {
        $transaccion->update([
            'comprobante' => $rutaComprobante,
        ]);

        return $transaccion->fresh();
    }
}
