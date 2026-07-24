<?php

use App\Models\Cliente;
use App\Models\RegistroPagoCliente;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $clientes = Cliente::all();
        $metodosBs = ['efectivo', 'pagomovil', 'transferencia'];

        foreach ($clientes as $cliente) {
            foreach ($metodosBs as $metodo) {
                RegistroPagoCliente::firstOrCreate(
                    ['cliente_id' => $cliente->id, 'metodo_pago' => $metodo],
                    [
                        'alias'  => "{$cliente->alias} - " . match ($metodo) {
                            'efectivo'      => 'Efectivo Bs',
                            'pagomovil'     => 'Pago móvil Bs',
                            'transferencia' => 'Transferencia Bs',
                        },
                        'activa' => true,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        RegistroPagoCliente::truncate();
    }
};
