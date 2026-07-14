<?php

/**
 * API routes for version 1 (prefix: /api/v1).
 *
 * Public: auth/login.
 * Protected (auth:sanctum): CRUD for titulares, bancos, monedas, cuentas, clientes,
 * categorias-gasto, operaciones, pool de pagadores, gastos, configuración de tasas
 * y comisiones, reportes, usuarios, bitácora y dashboard.
 */

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
use App\Http\Controllers\Api\V1\DocumentoController;

Route::prefix('v1')->group(function () {

    // ─────────────────────────────────────────────────────────────────
    // Autenticación (públicas)
    // ─────────────────────────────────────────────────────────────────
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');
    Route::post('auth/verificar-email', [AuthController::class, 'verificarEmail'])
        ->middleware('throttle:10,1');
    Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->name('verification.verify')
        ->middleware('signed');

    // ─────────────────────────────────────────────────────────────────
    // Documentos (públicos, autenticados por token en query param)
    // ─────────────────────────────────────────────────────────────────
    Route::get('documentos/{documento}/preview', [DocumentoController::class, 'preview']);
    Route::get('documentos/{documento}/download', [DocumentoController::class, 'download']);

    // ─────────────────────────────────────────────────────────────────
    // Rutas protegidas con Sanctum
    // ─────────────────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('auth/logout', [AuthController::class, 'logout'])
            ->middleware('throttle:10,1');
        Route::get('auth/me',     [AuthController::class, 'me']);

        // Documentos del cliente
        Route::get('clientes/{cliente}/documentos', [DocumentoController::class, 'index']);
        Route::post('clientes/{cliente}/documentos', [DocumentoController::class, 'store']);
        Route::delete('documentos/{documento}', [DocumentoController::class, 'destroy']);

        // ── Catálogos ────────────────────────────────────────────────
        Route::middleware('throttle:60,1')->group(function () {
            Route::apiResource('titulares',        TitularController::class)
                ->parameters(['titulares' => 'titular']);
            Route::apiResource('bancos',           BancoController::class)
                ->parameters(['bancos' => 'banco']);
            Route::apiResource('monedas',          MonedaController::class)
                ->parameters(['monedas' => 'moneda']);
            Route::apiResource('cuentas',          CuentaController::class);
            Route::post('cuentas/{cuenta}/saldo', [CuentaController::class, 'cargarSaldo'])
                ->middleware('role:admin|super_admin');
            Route::apiResource('clientes',         ClienteController::class);
            Route::get('clientes/{cliente}/cuentas', [ClienteController::class, 'cuentas']);
            Route::get('clientes/{cliente}/operaciones', [ClienteController::class, 'operaciones']);
            Route::post('clientes/{cliente}/operaciones/exportar', [ClienteController::class, 'exportarOperaciones']);
            Route::post('clientes/{cliente}/restaurar', [ClienteController::class, 'restaurar'])
                ->middleware('role:admin|super_admin');
            Route::apiResource('categorias-gasto', CategoriaGastoController::class)
                ->parameters(['categorias-gasto' => 'categoria_gasto']);

            // ── Tasas de mercado ─────────────────────────────────────────
            Route::get('tasas/actuales',  [TasasController::class, 'actuales']);
            Route::get('tasas/historico', [TasasController::class, 'historico']);
        });

        // ── Comisiones aplicadas por operación (DEBE IR ANTES del apiResource de operaciones) ──
        Route::prefix('operaciones/{operacion}/comisiones')->group(function () {
            Route::get('/',          [ComisionOperacionController::class, 'index'])
                ->middleware('role:admin|super_admin|contador');
            Route::patch('{comision}', [ComisionOperacionController::class, 'update'])
                ->middleware('role:admin|super_admin');
        });

        // ── Operaciones (ledger contable) ────────────────────────────
        Route::apiResource('operaciones', OperacionController::class)
            ->parameters(['operaciones' => 'operacion'])
            ->middleware('throttle:30,1');
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
        Route::get('gastos',                [GastoController::class, 'index'])->middleware('throttle:30,1');
        Route::post('gastos',               [GastoController::class, 'store'])->middleware('throttle:30,1');
        Route::get('gastos/{operacion}',    [GastoController::class, 'show'])->middleware('throttle:30,1');

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

        // ── Gestión de usuarios (solo admin|super_admin, con rate limiting) ──
        Route::middleware('role:admin|super_admin')->group(function () {
            Route::apiResource('usuarios', UserController::class)
                ->middleware('throttle:10,1');
        });

        // ── Bitácora (solo super_admin) ──────────────────────────────
        Route::middleware('role:super_admin')->group(function () {
            Route::get('admin/bitacora', function (\Illuminate\Http\Request $request) {
                $query = \Spatie\Activitylog\Models\Activity::query()
                    ->orderByDesc('created_at');

                if ($request->filled('modelo')) {
                    $safeModelo = addcslashes($request->input('modelo'), '%_');
                    $query->where('subject_type', 'like', '%' . $safeModelo . '%');
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
