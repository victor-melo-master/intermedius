<?php

use App\Models\Cliente;
use App\Models\Cuenta;
use App\Models\Moneda;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $clientes = DB::table('clientes')->whereNull('deleted_at')->get();
        $monedas = DB::table('monedas')->whereIn('codigo', ['VES', 'USD', 'EUR', 'COP'])->get();

        foreach ($clientes as $cliente) {
            foreach ($monedas as $moneda) {
                $existe = DB::table('cuentas')
                    ->where('cliente_id', $cliente->id)
                    ->where('moneda_id', $moneda->id)
                    ->where('tipo', 'efectivo')
                    ->whereNull('deleted_at')
                    ->exists();

                if (!$existe) {
                    DB::table('cuentas')->insert([
                        'cliente_id'   => $cliente->id,
                        'moneda_id'    => $moneda->id,
                        'alias'        => "{$cliente->alias} - Efectivo {$moneda->codigo}",
                        'tipo'         => 'efectivo',
                        'saldo_cache'  => 0,
                        'activa'       => true,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('cuentas')
            ->where('tipo', 'efectivo')
            ->whereNotNull('cliente_id')
            ->whereNull('deleted_at')
            ->whereRaw("alias LIKE '%Efectivo%'")
            ->delete();
    }
};
