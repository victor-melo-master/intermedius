<?php

namespace App\Jobs;

use App\Models\Operacion;
use App\Services\Pool\PoolNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VerificarSlaPoolJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(PoolNotifier $notifier): void
    {
        $threshold = now()->subMinutes(5);

        $operaciones = Operacion::where('estado_pool', 'pendiente')
            ->whereNull('sla_notificado_en')
            ->where('created_at', '<=', $threshold)
            ->get();

        if ($operaciones->isEmpty()) {
            return;
        }

        foreach ($operaciones as $operacion) {
            $minutosEspera = (int) $operacion->created_at->diffInMinutes(now());
            $notifier->slaExcedida($operacion, $minutosEspera);
            event(new \App\Events\SlaExcedida($operacion, $minutosEspera));
            $operacion->update(['sla_notificado_en' => now()]);
            Log::warning("SLA excedida para operación {$operacion->id} con {$minutosEspera} minutos de espera.");
        }
    }
}
