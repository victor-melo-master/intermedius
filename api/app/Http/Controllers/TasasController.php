<?php

namespace App\Http\Controllers;

use App\Models\TasaMercado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TasasController extends Controller
{
    private const FUENTES = ['bcv', 'paralelo', 'binance_p2p_buy', 'binance_p2p_sell'];

    /**
     * GET /api/v1/tasas/actuales
     *
     * Retorna las últimas tasas de cada fuente (cache → BD) y el spread entre ellas.
     */
    public function actuales(): JsonResponse
    {
        $tasas = [];

        foreach (self::FUENTES as $fuente) {
            $datos = Cache::get("tasa_actual:{$fuente}");

            if ($datos === null) {
                $registro = TasaMercado::where('fuente', $fuente)
                    ->latest('capturado_en')
                    ->first();

                if ($registro) {
                    $datos = [
                        'fuente'      => $fuente,
                        'valor'       => (float) $registro->valor,
                        'capturado_en' => $registro->capturado_en->toIso8601String(),
                    ];

                    // Para Binance incluir extras si existen en payload
                    if (str_starts_with($fuente, 'binance') && is_array($registro->payload_original)) {
                        foreach (['mediana', 'min', 'max', 'muestras'] as $campo) {
                            if (isset($registro->payload_original[$campo])) {
                                $datos[$campo] = $registro->payload_original[$campo];
                            }
                        }
                    }
                }
            }

            $tasas[$fuente] = $datos;
        }

        $spreads = $this->calcularSpreads($tasas);

        return response()->json([
            'tasas'   => $tasas,
            'spreads' => $spreads,
        ]);
    }

    /**
     * GET /api/v1/tasas/historico
     *
     * Histórico paginado con filtros opcionales: fuente, desde, hasta.
     */
    public function historico(Request $request): JsonResponse
    {
        $request->validate([
            'fuente' => ['nullable', 'string', 'in:' . implode(',', self::FUENTES)],
            'desde'  => ['nullable', 'date'],
            'hasta'  => ['nullable', 'date'],
        ]);

        $query = TasaMercado::with(['monedaBase:id,codigo,simbolo', 'monedaCotizada:id,codigo,simbolo'])
            ->when($request->filled('fuente'), fn ($q) => $q->where('fuente', $request->fuente))
            ->when($request->filled('desde'),  fn ($q) => $q->where('capturado_en', '>=', $request->desde))
            ->when($request->filled('hasta'),  fn ($q) => $q->where('capturado_en', '<=', $request->hasta . ' 23:59:59'))
            ->orderByDesc('capturado_en');

        $paginated = $query->paginate(50);

        $paginated->getCollection()->transform(fn ($t) => [
            'id'           => $t->id,
            'fuente'       => $t->fuente,
            'par'          => $t->monedaBase->codigo . '/' . $t->monedaCotizada->codigo,
            'valor'        => (string) $t->valor,
            'capturado_en' => $t->capturado_en->toIso8601String(),
        ]);

        return response()->json($paginated);
    }

    /**
     * Calcula los spreads porcentuales entre fuentes.
     * Retorna null en cada spread si faltan datos.
     */
    private function calcularSpreads(array $tasas): array
    {
        $bcv      = isset($tasas['bcv']['valor'])              ? (float) $tasas['bcv']['valor']              : null;
        $p2pSell  = isset($tasas['binance_p2p_sell']['valor']) ? (float) $tasas['binance_p2p_sell']['valor'] : null;
        $p2pBuy   = isset($tasas['binance_p2p_buy']['valor'])  ? (float) $tasas['binance_p2p_buy']['valor']  : null;

        return [
            // Cuánto % está el precio de venta USDT por encima del BCV
            'usdt_sell_vs_bcv'  => ($p2pSell && $bcv)     ? round((($p2pSell - $bcv) / $bcv) * 100, 4)     : null,
            // Cuánto % está el precio de compra USDT por encima del BCV
            'usdt_buy_vs_bcv'   => ($p2pBuy && $bcv)      ? round((($p2pBuy - $bcv) / $bcv) * 100, 4)      : null,
            // Spread bid/ask de USDT en P2P
            'usdt_sell_vs_buy'  => ($p2pSell && $p2pBuy)  ? round((($p2pSell - $p2pBuy) / $p2pBuy) * 100, 4) : null,
        ];
    }
}
