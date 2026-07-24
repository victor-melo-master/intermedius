<?php

namespace App\Observers;

use App\Models\Cliente;
use App\Models\Cuenta;
use App\Models\Moneda;

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
    }
}
