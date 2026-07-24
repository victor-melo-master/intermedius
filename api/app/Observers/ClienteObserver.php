<?php

namespace App\Observers;

use App\Models\Cliente;
use App\Models\Cuenta;
use App\Models\Moneda;
use App\Models\RegistroPagoCliente;

class ClienteObserver
{
    public function created(Cliente $cliente): void
    {
        $monedas = Moneda::whereIn('codigo', ['VES', 'USD', 'EUR', 'COP'])->get();

        foreach ($monedas as $moneda) {
            Cuenta::create([
                'cliente_id'  => $cliente->id,
                'moneda_id'   => $moneda->id,
                'alias'       => "{$cliente->alias} - Efectivo {$moneda->codigo}",
                'tipo'        => 'efectivo',
                'saldo_cache' => 0,
                'activa'      => true,
            ]);
        }

        $metodosBs = [
            'efectivo'      => "{$cliente->alias} - Efectivo Bs",
            'pagomovil'     => "{$cliente->alias} - Pago móvil Bs",
            'transferencia' => "{$cliente->alias} - Transferencia Bs",
        ];

        foreach ($metodosBs as $metodo => $alias) {
            RegistroPagoCliente::create([
                'cliente_id'  => $cliente->id,
                'metodo_pago' => $metodo,
                'alias'       => $alias,
                'activa'      => true,
            ]);
        }
    }
}
