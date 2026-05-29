<?php

namespace App\Jobs;

use App\Models\Moneda;
use App\Models\TasaMercado;
use App\Services\Tasas\TasasMercadoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SincronizarTasasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 30;

    public function handle(TasasMercadoService $service): void
    {
        $resultados = [
            $service->obtenerBcv(),
            $service->obtenerParalelo(),
            $service->obtenerBinanceP2P('BUY'),
            $service->obtenerBinanceP2P('SELL'),
        ];

        // Pre-cargar los IDs de monedas para no hacer N queries
        $monedaIds = Moneda::whereIn('codigo', ['USD', 'VES', 'USDT'])
            ->pluck('id', 'codigo');

        $ahora = now();

        foreach ($resultados as $resultado) {
            if ($resultado === null) {
                continue;
            }

            [$codigoBase, $codigoCotizada] = explode('/', $resultado['par']);

            $monedaBaseId    = $monedaIds[$codigoBase]    ?? null;
            $monedaCotizadaId = $monedaIds[$codigoCotizada] ?? null;

            if ($monedaBaseId === null || $monedaCotizadaId === null) {
                Log::warning('SincronizarTasasJob: moneda no encontrada para par ' . $resultado['par']);
                continue;
            }

            try {
                TasaMercado::create([
                    'fuente'             => $resultado['fuente'],
                    'moneda_base_id'     => $monedaBaseId,
                    'moneda_cotizada_id' => $monedaCotizadaId,
                    'valor'              => $resultado['valor'],
                    'capturado_en'       => $ahora,
                    'payload_original'   => $resultado['payload'],
                ]);
            } catch (\Throwable $e) {
                Log::error('SincronizarTasasJob: error guardando tasa', [
                    'fuente' => $resultado['fuente'],
                    'error'  => $e->getMessage(),
                ]);
            }

            $cached = array_merge(
                $resultado,
                ['capturado_en' => $ahora->toIso8601String()]
            );
            unset($cached['payload']); // El payload completo no va al cache

            Cache::put("tasa_actual:{$resultado['fuente']}", $cached, now()->addMinutes(30));
        }
    }
}
