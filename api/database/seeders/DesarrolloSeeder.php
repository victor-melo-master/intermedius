<?php

namespace Database\Seeders;

use App\Models\Banco;
use App\Models\Cliente;
use App\Models\Cuenta;
use App\Models\Moneda;
use App\Models\TasaDiaria;
use App\Models\Titular;
use App\Models\TipoOperacion;
use App\Models\Operacion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DesarrolloSeeder extends Seeder
{
    /**
     * Datos de desarrollo para pruebas: bancos, cuentas de Intermedius, tasas y clientes.
     *
     * Ejecutar con: php artisan db:seed --class=DesarrolloSeeder
     */
    public function run(): void
    {
        // 1. Obtener referencia de monedas (deben existir del seed inicial)
        $usd = Moneda::where('codigo', 'USD')->first();
        $ves = Moneda::where('codigo', 'VES')->first();
        $eur = Moneda::where('codigo', 'EUR')->first();
        $cop = Moneda::where('codigo', 'COP')->first();
        $usdt = Moneda::where('codigo', 'USDT')->first();

        if (!$usd || !$ves) {
            $this->command->warn('❌ Monedas base no encontradas. Ejecuta primero el seeder de monedas o asegura que existan.');
            return;
        }

        // 2. Bancos de Venezuela
        $bancosVzla = [
            ['nombre' => 'Banesco', 'codigo' => '0134', 'pais' => 'VE'],
            ['nombre' => 'Banco de Venezuela', 'codigo' => '0102', 'pais' => 'VE'],
            ['nombre' => 'Mercantil', 'codigo' => '0105', 'pais' => 'VE'],
            ['nombre' => 'Provincial', 'codigo' => '0108', 'pais' => 'VE'],
            ['nombre' => 'Bancaribe', 'codigo' => '0114', 'pais' => 'VE'],
            ['nombre' => 'Banco del Tesoro', 'codigo' => '0163', 'pais' => 'VE'],
            ['nombre' => 'Bancamiga', 'codigo' => '0171', 'pais' => 'VE'],
            ['nombre' => 'Banco Nacional de Crédito', 'codigo' => '0191', 'pais' => 'VE'],
            ['nombre' => 'Banco Plaza', 'codigo' => '0138', 'pais' => 'VE'],
            ['nombre' => 'Banco Exterior', 'codigo' => '0115', 'pais' => 'VE'],
        ];

        // Bancos de EE.UU.
        $bancosUsa = [
            ['nombre' => 'Bank of America', 'codigo' => 'BOA', 'pais' => 'US'],
            ['nombre' => 'Chase', 'codigo' => 'CHASE', 'pais' => 'US'],
            ['nombre' => 'Wells Fargo', 'codigo' => 'WF', 'pais' => 'US'],
        ];

        $bancos = array_merge($bancosVzla, $bancosUsa);

        foreach ($bancos as $b) {
            Banco::firstOrCreate(
                ['nombre' => $b['nombre']],
                [
                    'codigo' => $b['codigo'],
                    'pais'   => $b['pais'],
                    'activo' => true,
                ]
            );
        }

        $this->command->info('✅ Bancos creados/actualizados');

        // 3. Crear titular "Intermedius" (la empresa)
        $titularIntermedius = Titular::firstOrCreate(
            ['nombre' => 'Intermedius'],
            ['alias' => 'intermedius', 'activo' => true]
        );

        // También crear un usuario asociado al titular (opcional, para que el operador exista)
        $userIntermedius = User::firstOrCreate(
            ['email' => 'intermedius@test.com'],
            [
                'name'     => 'Intermedius',
                'password' => Hash::make('password123'),
                'activo'   => true,
            ]
        );
        // Asignar rol admin o super_admin si existe
        if ($userIntermedius->wasRecentlyCreated) {
            $userIntermedius->assignRole('super_admin');
        }

        // 4. Cliente "Intermedius" (representa a la empresa)
        $clienteIntermedius = Cliente::firstOrCreate(
            ['documento' => 'J-123456789'],
            [
                'nombre'   => 'Intermedius C.A.',
                'alias'    => 'Intermedius',
                'telefono' => '+58 212 555 0000',
                'email'    => 'info@intermedius.com',
                'activo'   => true,
            ]
        );

        $this->command->info('✅ Cliente y Titular Intermedius creados');

        // 5. Cuentas de Intermedius

        // 5a. Cuentas en bancos de USA (una por banco, en USD)
        $bancosUsaModel = Banco::where('pais', 'US')->get();
        foreach ($bancosUsaModel as $banco) {
            Cuenta::firstOrCreate(
                [
                    'titular_id' => $titularIntermedius->id,
                    'banco_id'   => $banco->id,
                    'alias'      => "Intermedius - {$banco->nombre} (USD)",
                ],
                [
                    'moneda_id'       => $usd->id,
                    'tipo'            => 'banco',
                    'numero_cuenta'   => '123456789' . rand(10, 99),
                    'saldo_cache'     => 1000.00,
                    'saldo_cache_at'  => now(),
                    'activa'          => true,
                ]
            );
        }

        // 5b. Cuentas en efectivo (cash) para cada moneda (USD, EUR, COP, USDT) — excepto VES
        $monedasEfectivo = [$usd, $eur, $cop, $usdt];
        foreach ($monedasEfectivo as $moneda) {
            if (!$moneda) continue;
            Cuenta::firstOrCreate(
                [
                    'titular_id' => $titularIntermedius->id,
                    'alias'      => "Intermedius - Efectivo {$moneda->codigo}",
                ],
                [
                    'banco_id'        => null,
                    'moneda_id'       => $moneda->id,
                    'tipo'            => 'efectivo',
                    'numero_cuenta'   => null,
                    'saldo_cache'     => $moneda->codigo === 'USD' ? 2000.00 : 1000.00,
                    'saldo_cache_at'  => now(),
                    'activa'          => true,
                ]
            );
        }

        // 5c. 3 cuentas en bancos de Venezuela (elige Banesco, Mercantil, Provincial)
        $bancosVzlaModel = Banco::where('pais', 'VE')->limit(3)->get();
        foreach ($bancosVzlaModel as $banco) {
            Cuenta::firstOrCreate(
                [
                    'titular_id' => $titularIntermedius->id,
                    'banco_id'   => $banco->id,
                    'alias'      => "Intermedius - {$banco->nombre} (VES)",
                ],
                [
                    'moneda_id'       => $ves->id,
                    'tipo'            => 'banco',
                    'numero_cuenta'   => '87654321' . rand(10, 99),
                    'saldo_cache'     => 1000000.00,
                    'saldo_cache_at'  => now(),
                    'activa'          => true,
                ]
            );
        }

        $this->command->info('✅ Cuentas de Intermedius creadas');

        // 6. Tasas de cambio del día (para hoy)
        $hoy = now()->toDateString();

        $pares = [
            ['base' => 'USD', 'cotizada' => 'VES', 'compra' => 40.50, 'venta' => 41.00],
            ['base' => 'EUR', 'cotizada' => 'VES', 'compra' => 44.00, 'venta' => 44.50],
            ['base' => 'COP', 'cotizada' => 'VES', 'compra' => 0.010, 'venta' => 0.011],
            ['base' => 'USDT', 'cotizada' => 'VES', 'compra' => 40.30, 'venta' => 40.80],
            ['base' => 'USD', 'cotizada' => 'EUR', 'compra' => 0.92, 'venta' => 0.93],
            ['base' => 'USD', 'cotizada' => 'COP', 'compra' => 3800, 'venta' => 3850],
        ];

        foreach ($pares as $par) {
            $monedaBase = Moneda::where('codigo', $par['base'])->first();
            $monedaCotizada = Moneda::where('codigo', $par['cotizada'])->first();
            if (!$monedaBase || !$monedaCotizada) {
                $this->command->warn("⚠️ Par {$par['base']}/{$par['cotizada']} no procesado: moneda no encontrada.");
                continue;
            }

            TasaDiaria::updateOrCreate(
                [
                    'fecha'              => $hoy,
                    'moneda_base_id'     => $monedaBase->id,
                    'moneda_cotizada_id' => $monedaCotizada->id,
                ],
                [
                    'tasa_compra'        => $par['compra'],
                    'tasa_compra_minima' => $par['compra'] * 0.98,
                    'tasa_venta'         => $par['venta'],
                    'tasa_venta_minima'  => $par['venta'] * 0.98,
                    'definida_por_id'    => $userIntermedius->id,
                    'notas'              => 'Tasas de referencia para desarrollo',
                    'vigente_desde'      => now(),
                    'vigente_hasta'      => null,
                ]
            );
        }

        $this->command->info('✅ Tasas diarias creadas');

        // 7. Clientes de prueba (2 clientes)
        $clientesPrueba = [
            [
                'nombre'    => 'María Pérez',
                'alias'     => 'mariaperez',
                'documento' => 'V-12345678',
                'telefono'  => '+58 414 123 4567',
                'email'     => 'maria@example.com',
            ],
            [
                'nombre'    => 'Carlos Gómez',
                'alias'     => 'carlosg',
                'documento' => 'V-87654321',
                'telefono'  => '+58 416 987 6543',
                'email'     => 'carlos@example.com',
            ],
        ];

        foreach ($clientesPrueba as $data) {
            $cliente = Cliente::firstOrCreate(
                ['documento' => $data['documento']],
                [
                    'nombre'   => $data['nombre'],
                    'alias'    => $data['alias'],
                    'telefono' => $data['telefono'],
                    'email'    => $data['email'],
                    'activo'   => true,
                ]
            );
            // Cuenta VES para el cliente (banco venezolano)
            $bancoAleatorio = Banco::where('pais', 'VE')->inRandomOrder()->first();
            if ($bancoAleatorio) {
                Cuenta::firstOrCreate(
                    [
                        'cliente_id' => $cliente->id,
                        'banco_id'   => $bancoAleatorio->id,
                        'alias'      => "Cuenta {$bancoAleatorio->nombre} - {$cliente->alias}",
                    ],
                    [
                        'moneda_id'       => $ves->id,
                        'tipo'            => 'banco',
                        'numero_cuenta'   => '111' . rand(10000, 99999),
                        'saldo_cache'     => 50000.00,
                        'saldo_cache_at'  => now(),
                        'activa'          => true,
                    ]
                );
            }

            // Cuenta USD para el cliente (banco de USA)
            $bancoUsa = Banco::where('pais', 'US')->inRandomOrder()->first();
            if ($bancoUsa) {
                Cuenta::firstOrCreate(
                    [
                        'cliente_id' => $cliente->id,
                        'banco_id'   => $bancoUsa->id,
                        'alias'      => "{$cliente->alias} - {$bancoUsa->nombre} (USD)",
                    ],
                    [
                        'moneda_id'       => $usd->id,
                        'tipo'            => 'banco',
                        'numero_cuenta'   => '999' . rand(10000, 99999),
                        'saldo_cache'     => 5000.00,
                        'saldo_cache_at'  => now(),
                        'activa'          => true,
                    ]
                );
            }
        }

        $this->command->info('✅ Clientes de prueba creados');

        // 8. Operación de prueba en verificación (venta de USD)
        $tipoVenta = TipoOperacion::where('codigo', 'venta_usd')->first();
        $operador = User::where('email', 'admin@test.com')->first();
        $clienteMaria = Cliente::where('documento', 'V-12345678')->first();

        $cuentaUsd = Cuenta::where('alias', 'like', '%Efectivo USD%')->first()
            ?? Cuenta::where('moneda_id', $usd->id)->first();
        $cuentaVes = Cuenta::where('alias', 'like', '%Banesco%VES%')->first()
            ?? Cuenta::where('moneda_id', $ves->id)->first();

        if ($tipoVenta && $operador && $cuentaUsd && $cuentaVes) {
            $operacion = Operacion::create([
                'fecha'             => now()->toDateString(),
                'tipo_operacion_id' => $tipoVenta->id,
                'cliente_id'        => $clienteMaria?->id,
                'operador_id'       => $operador->id,
                'tasa_aplicada'     => 41.00,
                'tasa_sugerida'     => 41.00,
                'estatus'           => 'en_verificacion',
                'estado'            => 'en_espera',
                'estado_pool'       => 'pendiente',
                'descripcion'       => 'Venta de USD a María - prueba verificación',
                'origen'            => 'manual',
            ]);

            $operacion->movimientos()->create([
                'cuenta_id'              => $cuentaUsd->id,
                'moneda_id'              => $usd->id,
                'monto'                  => -200,
                'tasa_a_usd'             => 1,
                'monto_usd_equivalente'  => -200,
                'orden'                  => 1,
                'estado'                 => 'pendiente',
            ]);

            $operacion->movimientos()->create([
                'cuenta_id'              => $cuentaVes->id,
                'moneda_id'              => $ves->id,
                'monto'                  => 8200,
                'tasa_a_usd'             => 0.02439,
                'monto_usd_equivalente'  => 200,
                'orden'                  => 2,
                'estado'                 => 'pendiente',
            ]);

            $operacion->movimientos()->create([
                'cuenta_id'              => $cuentaVes->id,
                'moneda_id'              => $ves->id,
                'monto'                  => -100,
                'tasa_a_usd'             => 0.02439,
                'monto_usd_equivalente'  => -2.44,
                'orden'                  => 3,
                'estado'                 => 'pendiente',
            ]);

            $this->command->info("✅ Operación #{$operacion->id} creada en verificación con 3 movimientos pendientes");
        } else {
            $this->command->warn('⚠️ No se pudo crear operación de prueba: faltan datos base.');
        }

        $this->command->info('🎉 Seeding completado exitosamente.');
    }
}
