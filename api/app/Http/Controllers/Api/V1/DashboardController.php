<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Moneda;
use App\Models\Operacion;
use App\Models\TasaDiaria;
use App\Models\TasaMercado;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function general(): JsonResponse
    {
        // ── Tasas vigentes (operativas, definidas por admin) ──────────────────
        $tasasVigentes = TasaDiaria::with(['monedaBase', 'monedaCotizada', 'definidaPor'])
            ->whereNull('vigente_hasta')
            ->orderBy('moneda_base_id')
            ->get()
            ->map(fn (TasaDiaria $t) => [
                'id'             => $t->id,
                'par'            => "{$t->monedaBase->codigo}/{$t->monedaCotizada->codigo}",
                'tasa_compra'    => (float) $t->tasa_compra,
                'tasa_venta'     => (float) $t->tasa_venta,
                'definida_a_las' => $t->vigente_desde?->toIso8601String(),
                'definida_por'   => $t->definidaPor?->name,
            ]);

        // ── Referencia de mercado (BCV / Binance — solo informativa) ─────────
        $bcv        = Cache::get('tasa_actual:bcv');
        $binanceBuy = Cache::get('tasa_actual:binance_p2p_buy');
        $binanceSell= Cache::get('tasa_actual:binance_p2p_sell');

        if (!$bcv) {
            $bcv = TasaMercado::where('fuente', 'bcv')->latest('capturado_en')->first();
        }
        if (!$binanceBuy) {
            $binanceBuy  = TasaMercado::where('fuente', 'binance_p2p_buy')->latest('capturado_en')->first();
        }
        if (!$binanceSell) {
            $binanceSell = TasaMercado::where('fuente', 'binance_p2p_sell')->latest('capturado_en')->first();
        }

        $spreadBinance = ($binanceBuy && $binanceSell)
            ? round((float) $binanceSell['valor'] - (float) $binanceBuy['valor'], 4)
            : null;

        $tasaVigenteBcv = $tasasVigentes->firstWhere('par', 'USD/VES');
        $spreadVigenteVsBcvPct = ($tasaVigenteBcv && $bcv)
            ? round(((float) $tasaVigenteBcv['tasa_venta'] - (float) $bcv['valor']) / (float) $bcv['valor'] * 100, 4)
            : null;

        // ── Alertas ──────────────────────────────────────────────────────────
        $paresConfig  = config('sistema.pares_principales', ['USD/VES', 'USDT/VES']);
        $paresSinTasa = collect($paresConfig)->filter(function (string $par) use ($tasasVigentes) {
            return $tasasVigentes->firstWhere('par', $par) === null;
        })->values()->all();

        $opsSinTasaHoy = Operacion::whereDate('fecha', today())
            ->where('sin_tasa_referencia', true)
            ->count();

        return response()->json([
            'tasas_vigentes'    => $tasasVigentes,
            'referencia_mercado' => [
                'bcv'                => $bcv ? ['valor' => (float) $bcv['valor'], 'capturado_en' => $bcv['capturado_en']] : null,
                'binance_p2p_buy'    => $binanceBuy  ? ['valor' => (float) $binanceBuy['valor'],  'capturado_en' => $binanceBuy['capturado_en']]  : null,
                'binance_p2p_sell'   => $binanceSell ? ['valor' => (float) $binanceSell['valor'], 'capturado_en' => $binanceSell['capturado_en']] : null,
                'spread_binance'     => $spreadBinance,
                'spread_vigente_vs_bcv_pct' => $spreadVigenteVsBcvPct,
            ],
            'alertas' => [
                'operaciones_sin_tasa_referencia_hoy' => $opsSinTasaHoy,
                'pares_sin_tasa_vigente'              => $paresSinTasa,
            ],
        ]);
    }
}
