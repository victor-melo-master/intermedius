<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BancoController;
use App\Http\Controllers\CategoriaGastoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\MonedaController;
use App\Http\Controllers\TitularController;
use App\Http\Controllers\Api\V1\GastoController;
use App\Http\Controllers\Api\V1\OperacionController;
use App\Http\Controllers\Api\V1\PoolController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ReporteComisionesController;
use App\Http\Controllers\Api\V1\Configuracion\TasaDiariaController;
use App\Http\Controllers\Api\V1\Configuracion\ComisionCuentaController;
use App\Http\Controllers\Api\V1\Configuracion\ComisionOperadorController;
use App\Http\Controllers\Api\V1\Configuracion\ComisionMetodoPagoController;
use App\Http\Controllers\Api\V1\Configuracion\ComisionOperacionController;
use App\Http\Controllers\TasasController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ─────────────────────────────────────────────────────────────────
    // Autenticación (públicas)
    // ─────────────────────────────────────────────────────────────────
    Route::post('auth/login', [AuthController::class, 'login']);

    // ─────────────────────────────────────────────────────────────────
    // Rutas protegidas con Sanctum
    // ─────────────────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me',     [AuthController::class, 'me']);

        // ── Catálogos ────────────────────────────────────────────────
        Route::apiResource('titulares',        TitularController::class);
        Route::apiResource('bancos',           BancoController::class);
        Route::apiResource('monedas',          MonedaController::class);
        Route::apiResource('cuentas',          CuentaController::class);
        Route::apiResource('clientes',         ClienteController::class);
        Route::get('clientes/{cliente}/cuentas', [ClienteController::class, 'cuentas']);
        Route::apiResource('categorias-gasto', CategoriaGastoController::class)
            ->parameters(['categorias-gasto' => 'categoria_gasto']);

        // ── Tasas de mercado ─────────────────────────────────────────
        Route::get('tasas/actuales',  [TasasController::class, 'actuales']);
        Route::get('tasas/historico', [TasasController::class, 'historico']);

        // ── Comisiones aplicadas por operación (DEBE IR ANTES del apiResource de operaciones) ──
        Route::prefix('operaciones/{operacion}/comisiones')->group(function () {
            Route::get('/',          [ComisionOperacionController::class, 'index'])
                ->middleware('role:admin|super_admin|contador');
            Route::patch('{comision}', [ComisionOperacionController::class, 'update'])
                ->middleware('role:admin|super_admin');
        });

        // ── Operaciones (ledger contable) ────────────────────────────
        Route::apiResource('operaciones', OperacionController::class)
            ->parameters(['operaciones' => 'operacion']);
        Route::patch('operaciones/{operacion}/verificar', [OperacionController::class, 'verificar']);
        Route::delete('operaciones/{operacion}', [OperacionController::class, 'destroy']);

        // ── Pool de pagadores ────────────────────────────────────────
        Route::prefix('pool')->group(function () {
            Route::get('/', [PoolController::class, 'index'])
                ->middleware('role:pagador|admin|super_admin');
            Route::get('mis-ordenes', [PoolController::class, 'misOrdenes'])
                ->middleware('role:pagador|admin|super_admin');
            Route::post('{operacion}/tomar', [PoolController::class, 'tomar'])
                ->middleware('role:pagador|admin|super_admin');
            Route::post('{operacion}/soltar', [PoolController::class, 'soltar'])
                ->middleware('role:pagador|admin|super_admin');
            Route::post('{operacion}/pagar', [PoolController::class, 'marcarPagada'])
                ->middleware('role:pagador|admin|super_admin');
            Route::post('{operacion}/cancelar', [PoolController::class, 'cancelar'])
                ->middleware('role:admin|super_admin');
        });

        // ── Gastos (subtipo de operaciones) ──────────────────────────
        Route::get('gastos',                [GastoController::class, 'index']);
        Route::post('gastos',               [GastoController::class, 'store']);
        Route::get('gastos/{operacion}',    [GastoController::class, 'show']);

        // ── Configuración: tasas vigentes (lectura para todos) ───────
        Route::prefix('configuracion')->group(function () {
            Route::get('tasas-vigentes', [TasaDiariaController::class, 'vigentes']);
            Route::get('tasas-diarias',  [TasaDiariaController::class, 'index']);
            Route::get('tasas-diarias/historial/{base}/{cotizada}', [TasaDiariaController::class, 'historial']);

            // ── Escritura solo admin ─────────────────────────────────
            Route::middleware('role:admin|super_admin')->group(function () {
                Route::post('tasas-diarias',  [TasaDiariaController::class, 'store']);
                Route::apiResource('comisiones-cuenta',        ComisionCuentaController::class)
                    ->parameters(['comisiones-cuenta' => 'comisionCuenta']);
                Route::apiResource('comisiones-operador',      ComisionOperadorController::class)
                    ->parameters(['comisiones-operador' => 'comisionOperador']);
                Route::apiResource('comisiones-metodo-pago',   ComisionMetodoPagoController::class)
                    ->parameters(['comisiones-metodo-pago' => 'comisionMetodoPago']);
            });
        });

        // ── Reportes de comisiones ───────────────────────────────────
        Route::middleware('role:admin|super_admin|contador')->group(function () {
            Route::get('reportes/comisiones-operadores',              [ReporteComisionesController::class, 'index']);
            Route::post('reportes/comisiones-operadores/exportar',    [ReporteComisionesController::class, 'exportar']);
            Route::get('reportes/comisiones-operadores/historico',    [ReporteComisionesController::class, 'historico']);
        });

        // ── Gestión de usuarios (solo admin|super_admin) ─────────────
        Route::middleware('role:admin|super_admin')->group(function () {
            Route::apiResource('usuarios', UserController::class);
        });

        // ── Bitácora (solo super_admin) ──────────────────────────────
        Route::middleware('role:super_admin')->group(function () {
            Route::get('admin/bitacora', function (\Illuminate\Http\Request $request) {
                $query = \Spatie\Activitylog\Models\Activity::query()
                    ->orderByDesc('created_at');

                if ($request->filled('modelo')) {
                    $query->where('subject_type', 'like', '%' . $request->input('modelo') . '%');
                }
                if ($request->filled('user_id')) {
                    $query->where('causer_id', $request->integer('user_id'));
                }
                if ($request->filled('desde')) {
                    $query->whereDate('created_at', '>=', $request->input('desde'));
                }
                if ($request->filled('hasta')) {
                    $query->whereDate('created_at', '<=', $request->input('hasta'));
                }

                return response()->json($query->paginate(50));
            });
        });

        // ── Dashboard general ────────────────────────────────────────
        Route::get('dashboard/general', [DashboardController::class, 'general']);
        Route::get('dashboard/tasas-referencia', [DashboardController::class, 'tasasReferencia']);
        Route::get('dashboard/resumen', [DashboardController::class, 'resumen']);
    });
});
