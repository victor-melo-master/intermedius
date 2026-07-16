<?php

namespace App\Services\Tasas;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for fetching market exchange rates from external APIs.
 * Sources: BCV (via dolarapi.com) and Binance P2P (USDT/VES).
 */
class TasasMercadoService
{
    private const DOLAR_API_BASE = 'https://ve.dolarapi.com/v1/dolares';
    private const BINANCE_P2P    = 'https://p2p.binance.com/bapi/c2c/v2/friendly/c2c/adv/search';

    /**
     * Obtiene la tasa BCV (tipo oficial) desde dolarapi.com.
     *
     * @return array{fuente:string, par:string, valor:float, payload:array}|null
     */
    public function obtenerBcv(): ?array
    {
        try {
            $response = Http::timeout(10)
                ->retry(2, 500)
                ->get(self::DOLAR_API_BASE . '/oficial');

            $response->throw();
            $data = $response->json();

            return [
                'fuente'  => 'bcv',
                'par'     => 'USD/VES',
                'valor'   => (float) ($data['promedio'] ?? $data['promedioVenta'] ?? 0),
                'payload' => $data,
            ];
        } catch (\Throwable $e) {
            Log::warning('TasasMercadoService::obtenerBcv falló', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Obtiene la tasa del dólar paralelo desde dolarapi.com.
     *
     * @return array{fuente:string, par:string, valor:float, payload:array}|null
     */
    public function obtenerParalelo(): ?array
    {
        try {
            $response = Http::timeout(10)
                ->retry(2, 500)
                ->get(self::DOLAR_API_BASE . '/paralelo');

            $response->throw();
            $data = $response->json();

            return [
                'fuente'  => 'paralelo',
                'par'     => 'USD/VES',
                'valor'   => (float) ($data['promedio'] ?? $data['promedioVenta'] ?? 0),
                'payload' => $data,
            ];
        } catch (\Throwable $e) {
            Log::warning('TasasMercadoService::obtenerParalelo falló', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Obtiene la tasa del Euro oficial desde dolarapi.com.
     *
     * @return array{fuente:string, par:string, valor:float, payload:array}|null
     */
    public function obtenerEuroBcv(): ?array
    {
        try {
            $response = Http::timeout(10)
                ->retry(2, 500)
                ->get(self::DOLAR_API_BASE . '/../euros/oficial');

            $response->throw();
            $data = $response->json();

            return [
                'fuente'  => 'bcv_eur',
                'par'     => 'EUR/VES',
                'valor'   => (float) ($data['promedio'] ?? $data['promedioVenta'] ?? 0),
                'payload' => $data,
            ];
        } catch (\Throwable $e) {
            Log::warning('TasasMercadoService::obtenerEuroBcv falló', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Obtiene precios de Binance P2P para USDT/VES.
     *
     * @param  string $tradeType  'BUY' (compradores de USDT) o 'SELL' (vendedores de USDT)
     * @param  int    $top        Número de anuncios a consultar
     * @return array{fuente:string, par:string, valor:float, mediana:float, min:float, max:float, muestras:int, payload:array}|null
     */
    public function obtenerBinanceP2P(string $tradeType = 'BUY', int $top = 10): ?array
    {
        try {
            $response = Http::timeout(10)
                ->retry(2, 500)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent'   => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->post(self::BINANCE_P2P, [
                    'fiat'          => 'VES',
                    'asset'         => 'USDT',
                    'tradeType'     => $tradeType,
                    'page'          => 1,
                    'rows'          => $top,
                    'payTypes'      => [],
                    'publisherType' => null,
                ]);

            $response->throw();
            $data = $response->json();

            $precios = collect($data['data'] ?? [])
                ->map(fn ($item) => (float) ($item['adv']['price'] ?? 0))
                ->filter(fn ($p) => $p > 0)
                ->values();

            if ($precios->isEmpty()) {
                return null;
            }

            $sorted   = $precios->sort()->values();
            $count    = $sorted->count();
            $mid      = intdiv($count, 2);
            $mediana  = $count % 2 === 0
                ? ($sorted[$mid - 1] + $sorted[$mid]) / 2
                : $sorted[$mid];

            $fuente = $tradeType === 'BUY' ? 'binance_p2p_buy' : 'binance_p2p_sell';

            return [
                'fuente'   => $fuente,
                'par'      => 'USDT/VES',
                'valor'    => round($precios->avg(), 8),
                'mediana'  => round($mediana, 8),
                'min'      => $sorted->first(),
                'max'      => $sorted->last(),
                'muestras' => $count,
                'payload'  => $data,
            ];
        } catch (\Throwable $e) {
            Log::warning("TasasMercadoService::obtenerBinanceP2P({$tradeType}) falló", ['error' => $e->getMessage()]);
            return null;
        }
    }
}
