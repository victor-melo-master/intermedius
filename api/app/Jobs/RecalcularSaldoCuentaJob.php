<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalcularSaldoCuentaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly array $cuentaIds) {}

    public function handle(): void
    {
        // TODO Fase 3: sumar todos los movimientos de cada cuenta y actualizar saldo_cache + saldo_cache_at.
    }
}
