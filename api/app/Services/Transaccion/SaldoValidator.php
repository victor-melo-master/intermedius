<?php

namespace App\Services\Transaccion;

use App\Models\Cuenta;
use App\Models\Movimiento;
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
        $saldo  = $this->obtenerSaldoDisponible($cuenta, $monedaId);

        if ($saldo < $monto) {
            throw ValidationException::withMessages([
                'monto' => "Saldo insuficiente en {$cuenta->alias}. Disponible: {$saldo}, requerido: {$monto}.",
            ]);
        }
    }

    public function obtenerSaldoDisponible(Cuenta $cuenta, int $monedaId): float
    {
        if ($cuenta->saldo_cache_at !== null && $cuenta->saldo_cache_at->diffInMinutes(now()) < 5) {
            return (float) $cuenta->saldo_cache;
        }

        $movimientos = Movimiento::where('cuenta_id', $cuenta->id)
            ->where('moneda_id', $monedaId)
            ->sum(DB::raw('monto'));

        $transaccionesOrigen = Transaccion::where('cuenta_origen_id', $cuenta->id)
            ->where('moneda_id', $monedaId)
            ->whereIn('estado', ['pendiente', 'validada'])
            ->sum(DB::raw('monto'));

        $transaccionesDestino = Transaccion::where('cuenta_destino_id', $cuenta->id)
            ->where('moneda_id', $monedaId)
            ->where('estado', 'validada')
            ->sum(DB::raw('monto'));

        return (float) ($movimientos - $transaccionesOrigen + $transaccionesDestino);
    }
}
