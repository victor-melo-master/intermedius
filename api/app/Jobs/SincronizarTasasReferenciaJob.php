<?php

namespace App\Jobs;

use App\Models\Moneda;
use App\Models\TasaMercado;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Captura tasas de referencia externas y las persiste en tasas_mercado.
 *
 * Fuente 1 — BCV oficial (dolarapi.com)      → fuente 'bcv',         par USD/VES
 * Fuente 2 — Binance P2P USDT/VES (compra)   → fuente 'binance_p2p', par USDT/VES
 *
 * Nota: la tabla tasas_mercado almacena el valor en la columna `valor`
 * y referencia las monedas por FK (moneda_base_id / moneda_cotizada_id).
 */
class SincronizarTasasReferenciaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const DOLAR_API_OFICIAL = 'https://ve.dolarapi.com/v1/dolares/oficial';
    private const BINANCE_P2P       = 'https://p2p.binance.com/bapi/c2c/v2/friendly/c2c/adv/search';

    public int $tries   = 3;
    public int $backoff = 30;

    public function handle(): void
    {
        $ahora     = now();
        $monedaIds = Moneda::whereIn('codigo', ['USD', 'VES', 'USDT'])->pluck('id', 'codigo');

        $this->capturarBcv($monedaIds, $ahora);
        $this->capturarBinanceP2P($monedaIds, $ahora);
    }

    /**
     * TASA 1 — BCV oficial. Respuesta: { "promedio": 36.50, "fechaActualizacion": "..." }
     */
    private function capturarBcv(\Illuminate\Support\Collection $monedaIds, Carbon $ahora): void
    {
        try {
            $response = Http::timeout(10)->retry(2, 500)->get(self::DOLAR_API_OFICIAL);
            $response->throw();
            $data = $response->json();

            $valor = (float) ($data['promedio'] ?? $data['promedioVenta'] ?? 0);
            if ($valor <= 0) {
                Log::warning('SincronizarTasasReferenciaJob: BCV sin valor válido', ['payload' => $data]);
                return;
            }

            $this->guardar('bcv', $monedaIds['USD'] ?? null, $monedaIds['VES'] ?? null, $valor, $ahora, $data);
        } catch (\Throwable $e) {
            Log::warning('SincronizarTasasReferenciaJob: BCV falló', ['error' => $e->getMessage()]);
        }
    }

    /**
     * TASA 2 — Binance P2P USDT/VES (tradeType BUY). Promedio de los primeros 3 anuncios.
     */
    private function capturarBinanceP2P(\Illuminate\Support\Collection $monedaIds, Carbon $ahora): void
    {
        try {
            $response = Http::timeout(10)->retry(2, 500)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent'   => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->post(self::BINANCE_P2P, [
                    'asset'     => 'USDT',
                    'fiat'      => 'VES',
                    'tradeType' => 'BUY',
                    'page'      => 1,
                    'rows'      => 5,
                    'payTypes'  => [],
                ]);

            $response->throw();
            $data = $response->json();

            $precios = collect($data['data'] ?? [])
                ->take(3)
                ->map(fn ($item) => (float) ($item['adv']['price'] ?? 0))
                ->filter(fn ($p) => $p > 0)
                ->values();

            if ($precios->isEmpty()) {
                Log::warning('SincronizarTasasReferenciaJob: Binance P2P sin precios válidos');
                return;
            }

            $valor = round($precios->avg(), 8);

            $this->guardar('binance_p2p', $monedaIds['USDT'] ?? null, $monedaIds['VES'] ?? null, $valor, $ahora, $data);
        } catch (\Throwable $e) {
            Log::warning('SincronizarTasasReferenciaJob: Binance P2P falló', ['error' => $e->getMessage()]);
        }
    }

    private function guardar(string $fuente, ?int $baseId, ?int $cotizadaId, float $valor, Carbon $capturadoEn, array $payload): void
    {
        if ($baseId === null || $cotizadaId === null) {
            Log::warning("SincronizarTasasReferenciaJob: moneda no encontrada para fuente {$fuente}");
            return;
        }

        TasaMercado::create([
            'fuente'             => $fuente,
            'moneda_base_id'     => $baseId,
            'moneda_cotizada_id' => $cotizadaId,
            'valor'              => $valor,
            'capturado_en'       => $capturadoEn,
            'payload_original'   => $payload,
        ]);
    }
}
