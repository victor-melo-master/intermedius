<?php

namespace App\Services;

use App\Models\Cuenta;
use App\Models\FlujoCuenta;
use App\Models\Moneda;
use App\Models\Operacion;
use App\Models\Transaccion;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FlujoCuentaService
{
    public function registrarEntrada(
        Cuenta $cuenta,
        float $monto,
        Moneda $moneda,
        ?string $descripcion = null,
        ?Operacion $operacion = null,
        ?Transaccion $transaccion = null,
        ?User $registradoPor = null,
    ): FlujoCuenta {
        return FlujoCuenta::create([
            'cuenta_id'         => $cuenta->id,
            'tipo'              => 'entrada',
            'monto'             => round($monto, 2),
            'moneda_id'         => $moneda->id,
            'descripcion'       => $descripcion,
            'operacion_id'      => $operacion?->id,
            'transaccion_id'    => $transaccion?->id,
            'registrado_por_id' => $registradoPor?->id,
        ]);
    }

    public function registrarSalida(
        Cuenta $cuenta,
        float $monto,
        Moneda $moneda,
        ?string $descripcion = null,
        ?Operacion $operacion = null,
        ?Transaccion $transaccion = null,
        ?User $registradoPor = null,
    ): FlujoCuenta {
        return FlujoCuenta::create([
            'cuenta_id'         => $cuenta->id,
            'tipo'              => 'salida',
            'monto'             => round($monto, 2),
            'moneda_id'         => $moneda->id,
            'descripcion'       => $descripcion,
            'operacion_id'      => $operacion?->id,
            'transaccion_id'    => $transaccion?->id,
            'registrado_por_id' => $registradoPor?->id,
        ]);
    }

    public function obtenerSaldo(Cuenta $cuenta): float
    {
        $entradas = (float) FlujoCuenta::where('cuenta_id', $cuenta->id)
            ->where('tipo', 'entrada')
            ->sum('monto');

        $salidas = (float) FlujoCuenta::where('cuenta_id', $cuenta->id)
            ->where('tipo', 'salida')
            ->sum('monto');

        return round($entradas - $salidas, 2);
    }

    public function eliminarPorTransaccion(Transaccion $transaccion): int
    {
        return FlujoCuenta::where('transaccion_id', $transaccion->id)->delete();
    }

    public function obtenerHistorial(Cuenta $cuenta, int $perPage = 20): LengthAwarePaginator
    {
        return FlujoCuenta::where('cuenta_id', $cuenta->id)
            ->with(['moneda', 'operacion', 'transaccion', 'registradoPor'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
