<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Moneda;
use App\Models\Operacion;
use App\Models\TasaDiaria;
use App\Models\TasaMercado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    /**
     * GET /api/v1/dashboard/tasas-referencia
     *
     * Última tasa capturada de cada fuente externa (BCV y Binance P2P).
     * Accesible a cualquier usuario autenticado.
     */
    public function tasasReferencia(): JsonResponse
    {
        $resultado = [];

        foreach (['bcv', 'binance_p2p'] as $fuente) {
            $registro = TasaMercado::where('fuente', $fuente)->latest('capturado_en')->first();

            $resultado[$fuente] = $registro ? [
                'tasa'         => (float) $registro->valor,
                'capturado_en' => $registro->capturado_en->toIso8601String(),
            ] : null;
        }

        return response()->json($resultado);
    }

    /**
     * GET /api/v1/dashboard/resumen
     *
     * Resumen agregado de operaciones para el período indicado.
     * Filtros opcionales: fecha_desde, fecha_hasta (default: hoy), moneda, operador_id.
     */
    public function resumen(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date'],
            'moneda'      => ['nullable', 'string', 'max:10'],
            'operador_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $desde      = $request->input('fecha_desde', today()->toDateString());
        $hasta      = $request->input('fecha_hasta', today()->toDateString());
        $moneda     = $request->input('moneda');
        $operadorId = $request->input('operador_id');

        $ops = Operacion::query()
            ->with([
                'tipoOperacion:id,codigo',
                'operador:id,name',
                'movimientos.moneda:id,codigo',
            ])
            ->whereBetween('fecha', [$desde, $hasta])
            ->when($operadorId, fn ($q) => $q->where('operador_id', $operadorId))
            ->when($moneda, fn ($q) => $q->whereHas('movimientos.moneda', fn ($mq) => $mq->where('codigo', $moneda)))
            ->get();

        $compras = $ventas = $intermediadas = 0;
        $brutaUsd = $netaUsd = 0.0;
        $volPorMoneda = [];   // codigo => ['comprado' => x, 'vendido' => y]
        $porOperador  = [];   // operador_id => [...]
        $efCount = 0;
        $efMonto = 0.0;

        foreach ($ops as $op) {
            $codigo = $op->tipoOperacion?->codigo;

            match ($codigo) {
                'compra_usd' => $compras++,
                'venta_usd'  => $ventas++,
                'cambio'     => $intermediadas++,
                default      => null,
            };

            $brutaUsd += (float) $op->ganancia_bruta_usd;
            $netaUsd  += (float) $op->ganancia_neta_usd;

            // Volúmenes por moneda (excluye la local VES)
            foreach ($op->movimientos as $mov) {
                $codMoneda = $mov->moneda?->codigo;
                if (! $codMoneda || $codMoneda === 'VES') {
                    continue;
                }
                if ($moneda && $codMoneda !== $moneda) {
                    continue;
                }

                $volPorMoneda[$codMoneda] ??= ['comprado' => 0.0, 'vendido' => 0.0];
                $monto = (float) $mov->monto;

                if ($codigo === 'compra_usd' && $monto > 0) {
                    $volPorMoneda[$codMoneda]['comprado'] += $monto;
                } elseif ($codigo === 'venta_usd' && $monto < 0) {
                    $volPorMoneda[$codMoneda]['vendido'] += abs($monto);
                }
            }

            $volumenUsd = $this->volumenUsdDeOperacion($op);

            // Agregado por operador
            $oid = $op->operador_id;
            $porOperador[$oid] ??= [
                'operador'          => $op->operador?->name ?? '—',
                'total_operaciones' => 0,
                'volumen_usd'       => 0.0,
            ];
            $porOperador[$oid]['total_operaciones']++;
            $porOperador[$oid]['volumen_usd'] += $volumenUsd;

            // Efectivo pendiente (heurística sobre descripcion, Fase 1 sin columna dedicada)
            if (stripos((string) $op->descripcion, 'pendiente') !== false) {
                $efCount++;
                $efMonto += $volumenUsd;
            }
        }

        $volumenes = collect($volPorMoneda)
            ->map(fn ($v, $cod) => [
                'moneda'   => $cod,
                'comprado' => round($v['comprado'], 2),
                'vendido'  => round($v['vendido'], 2),
            ])
            ->values();

        $porOperadorArr = collect($porOperador)
            ->map(fn ($v) => [
                'operador'          => $v['operador'],
                'total_operaciones' => $v['total_operaciones'],
                'volumen_usd'       => round($v['volumen_usd'], 2),
            ])
            ->sortByDesc('volumen_usd')
            ->values();

        return response()->json([
            'periodo' => [
                'desde' => $desde,
                'hasta' => $hasta,
            ],
            'operaciones' => [
                'total'         => $ops->count(),
                'compras'       => $compras,
                'ventas'        => $ventas,
                'intermediadas' => $intermediadas,
            ],
            'volumenes' => $volumenes,
            'ganancias' => [
                'bruta_usd' => round($brutaUsd, 2),
                'neta_usd'  => round($netaUsd, 2),
            ],
            'por_operador' => $porOperadorArr,
            'efectivo_pendiente' => [
                'count'     => $efCount,
                'monto_usd' => round($efMonto, 2),
            ],
        ]);
    }

    /**
     * Volumen en USD de una operación: usa el equivalente USD de la pata
     * no-local (USD/USDT/EUR/COP); si no hay, toma el mayor equivalente.
     */
    private function volumenUsdDeOperacion(Operacion $op): float
    {
        $asset = $op->movimientos->first(fn ($m) => $m->moneda?->codigo !== 'VES');

        if ($asset) {
            return abs((float) $asset->monto_usd_equivalente);
        }

        return (float) ($op->movimientos->max(fn ($m) => abs((float) $m->monto_usd_equivalente)) ?? 0.0);
    }
}
