<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job para recalcular el saldo_cache de una o más cuentas contables.
 * TODO Fase 3: implementar lógica de suma de movimientos.
 */
class RecalcularSaldoCuentaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  array  $cuentaIds  IDs de las cuentas a recalcular.
     */
    public function __construct(public readonly array $cuentaIds) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // TODO Fase 3: sumar todos los movimientos de cada cuenta y actualizar saldo_cache + saldo_cache_at.
    }
}
