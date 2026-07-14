<?php

namespace App\Jobs;

use App\Models\Operacion;
use App\Services\Transaccion\TransaccionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Optional background job that processes pending transactions for an operation.
 * Useful for bulk creation or deferred processing scenarios.
 */
class ProcesarTransaccionesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 10;
    public int $timeout = 60;

    /**
     * Create a new job instance.
     *
     * @param  int    $operacionId
     * @param  array  $transaccionesData
     */
    public function __construct(
        public readonly int $operacionId,
        public readonly array $transaccionesData,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TransaccionService $service): void
    {
        $operacion = Operacion::find($this->operacionId);

        if (!$operacion) {
            Log::warning("ProcesarTransaccionesJob: operación {$this->operacionId} no encontrada.");
            return;
        }

        $transacciones = $service->crearTransacciones($operacion, $this->transaccionesData);

        Log::info("ProcesarTransaccionesJob: {$transacciones->count()} transacciones creadas para operación {$this->operacionId}.");
    }
}
