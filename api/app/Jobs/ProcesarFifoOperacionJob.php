<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job para procesar la asignación FIFO de una operación sobre lotes de inventario.
 * TODO Fase 4: delegar a FifoService::procesarOperacion().
 */
class ProcesarFifoOperacionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  int  $operacionId  ID de la operación a procesar.
     */
    public function __construct(public readonly int $operacionId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // TODO Fase 4: FifoService::procesarOperacion($this->operacionId).
        // Lotes FIFO usan clave (titular_id, moneda_id) — NO cuenta_id.
        // TODO Fase 4: lotes_fifo usa titular_id, no cuenta_id.
    }
}
