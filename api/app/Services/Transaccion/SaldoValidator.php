<?php

namespace App\Services\Transaccion;

use App\Models\Cuenta;
use App\Models\Transaccion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Validates account balance sufficiency before creating or confirming transactions.
 * Uses saldo_cache when available, otherwise computes the running balance.
 */
class SaldoValidator
{
    public function assertSaldoSuficiente(int $cuentaId, int $monedaId, float $monto): void
    {
        if ($monto <= 0) {
            return;
        }

        $cuenta = Cuenta::findOrFail($cuentaId);

        // No validamos saldo para cuentas de clientes — desconocemos su saldo real
        if ($cuenta->cliente_id) {
            return;
        }

        $saldo  = $this->obtenerSaldoDisponible($cuenta, $monedaId);

        if ($saldo < $monto) {
            throw ValidationException::withMessages([
                'monto' => "Saldo insuficiente en {$cuenta->alias}. Disponible: {$saldo}, requerido: {$monto}.",
            ]);
        }
    }

    public function obtenerSaldoDisponible(Cuenta $cuenta, int $monedaId): float
    {
        $base = (float) $cuenta->saldo_cache;

        $transaccionesOrigen = Transaccion::where('cuenta_origen_id', $cuenta->id)
            ->where('moneda_id', $monedaId)
            ->whereIn('estado', ['pendiente', 'validada'])
            ->sum(DB::raw('monto'));

        $transaccionesDestino = Transaccion::where('cuenta_destino_id', $cuenta->id)
            ->where('moneda_id', $monedaId)
            ->where('estado', 'validada')
            ->sum(DB::raw('monto'));

        return round($base - $transaccionesOrigen + $transaccionesDestino, 2);
    }
}
