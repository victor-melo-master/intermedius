<?php

namespace App\Jobs;

use App\Models\Operacion;
use App\Services\Pool\PoolNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VerificarSlaPoolJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function handle(PoolNotifier $notifier): void
    {
        $threshold = now()->subMinutes(5);

        $operacionesEnEspera = Operacion::where('estado', 'en_espera')
            ->where('created_at', '<=', $threshold)
            ->get();

        if ($operacionesEnEspera->isEmpty()) {
            return;
        }

        foreach ($operacionesEnEspera as $operacion) {
            $minutosEspera = $operacion->created_at->diffInMinutes(now());
            $notifier->slaExcedida($operacion, $minutosEspera);
            event(new \App\Events\SlaExcedida($operacion, $minutosEspera));
            Log::warning("SLA excedida para operación {$operacion->id} con {$minutosEspera} minutos de espera.");
        }
    }
}
