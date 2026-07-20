<?php

namespace Database\Seeders;

use App\Models\Banco;
use App\Models\Moneda;
use App\Models\TipoOperacion;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CatalogosBaseSeeder extends Seeder
{
    public function run(): void
    {
        // Roles
        $roles = ['super_admin', 'admin', 'operador', 'contador', 'lectura', 'pagador'];
        foreach ($roles as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }

        // Permisos del pool
        $permisosPool = ['pool.tomar', 'pool.pagar', 'pool.cancelar'];
        foreach ($permisosPool as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        Role::where('name', 'pagador')->first()
            ->givePermissionTo(['pool.tomar', 'pool.pagar']);

        Role::where('name', 'admin')->first()
            ->givePermissionTo(['pool.tomar', 'pool.pagar', 'pool.cancelar']);

        Role::where('name', 'super_admin')->first()
            ->givePermissionTo(['pool.tomar', 'pool.pagar', 'pool.cancelar']);

        // Monedas
        $monedas = [
            ['codigo' => 'VES', 'nombre' => 'Bolívar Venezolano',   'simbolo' => 'Bs.',  'es_fiat' => true,  'es_cripto' => false, 'decimales' => 2],
            ['codigo' => 'USD', 'nombre' => 'Dólar Estadounidense', 'simbolo' => '$',    'es_fiat' => true,  'es_cripto' => false, 'decimales' => 2],
            ['codigo' => 'USDT','nombre' => 'Tether USD',           'simbolo' => '₮',    'es_fiat' => false, 'es_cripto' => true,  'decimales' => 6],
            ['codigo' => 'EUR', 'nombre' => 'Euro',                 'simbolo' => '€',    'es_fiat' => true,  'es_cripto' => false, 'decimales' => 2],
            ['codigo' => 'COP', 'nombre' => 'Peso Colombiano',      'simbolo' => '$',    'es_fiat' => true,  'es_cripto' => false, 'decimales' => 2],
        ];
        foreach ($monedas as $moneda) {
            Moneda::firstOrCreate(['codigo' => $moneda['codigo']], $moneda);
        }

        // Bancos
        $bancos = [
            ['nombre' => 'Banesco',            'codigo' => '0134', 'pais' => 'VE'],
            ['nombre' => 'Mercantil',          'codigo' => '0105', 'pais' => 'VE'],
            ['nombre' => 'Venezuela',          'codigo' => '0102', 'pais' => 'VE'],
            ['nombre' => 'Provincial',         'codigo' => '0108', 'pais' => 'VE'],
            ['nombre' => 'Bancamiga',          'codigo' => '0172', 'pais' => 'VE'],
            ['nombre' => 'Tesoro',             'codigo' => '0163', 'pais' => 'VE'],
            ['nombre' => 'Bancaribe',          'codigo' => '0114', 'pais' => 'VE'],
            ['nombre' => 'Banesco Panamá',     'codigo' => null,   'pais' => 'PA'],
            ['nombre' => 'Mercantil Panamá',   'codigo' => null,   'pais' => 'PA'],
            ['nombre' => 'Bancolombia',        'codigo' => null,   'pais' => 'CO'],
            ['nombre' => 'Truist Bank',        'codigo' => null,   'pais' => 'US'],
            ['nombre' => 'Bank of America',    'codigo' => null,   'pais' => 'US'],
            ['nombre' => 'Banco 53',           'codigo' => null,   'pais' => 'PA'],
        ];
        foreach ($bancos as $banco) {
            Banco::firstOrCreate(['nombre' => $banco['nombre']], $banco);
        }

        // Tipos de operación
        $tipos = [
            ['codigo' => 'venta_usd',       'nombre' => 'Venta de USD',        'afecta_cliente' => true,  'afecta_fifo' => true,  'genera_ganancia' => true],
            ['codigo' => 'compra_usd',      'nombre' => 'Compra de USD',       'afecta_cliente' => true,  'afecta_fifo' => true,  'genera_ganancia' => false],
            ['codigo' => 'cambio',          'nombre' => 'Cambio de moneda',    'afecta_cliente' => false, 'afecta_fifo' => false, 'genera_ganancia' => false],
            ['codigo' => 'intermediada',    'nombre' => 'Operación Intermediada', 'afecta_cliente' => true, 'afecta_fifo' => true, 'genera_ganancia' => true],
            ['codigo' => 'gasto',           'nombre' => 'Gasto operativo',     'afecta_cliente' => false, 'afecta_fifo' => false, 'genera_ganancia' => false],
            ['codigo' => 'comision',        'nombre' => 'Comisión',            'afecta_cliente' => true,  'afecta_fifo' => false, 'genera_ganancia' => true],
            ['codigo' => 'traslado',        'nombre' => 'Traslado interno',    'afecta_cliente' => false, 'afecta_fifo' => false, 'genera_ganancia' => false],
            ['codigo' => 'ajuste',          'nombre' => 'Ajuste contable',     'afecta_cliente' => false, 'afecta_fifo' => false, 'genera_ganancia' => false],
            ['codigo' => 'ajuste_apertura', 'nombre' => 'Ajuste de apertura',  'afecta_cliente' => false, 'afecta_fifo' => true,  'genera_ganancia' => false],
        ];
        foreach ($tipos as $tipo) {
            TipoOperacion::firstOrCreate(['codigo' => $tipo['codigo']], $tipo);
        }
    }
}
