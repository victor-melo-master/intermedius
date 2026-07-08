<?php

namespace App\Jobs;

use App\Models\Cliente;
use App\Models\Operacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoArchivarClientesInactivos implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(): void
    {
        $mesesInactividad = config('sistema.clientes_meses_inactividad', 4);
        $fechaLimite = now()->subMonths($mesesInactividad);

        $clientes = Cliente::whereNull('deleted_at')
            ->whereDoesntHave('operaciones', function ($q) use ($fechaLimite) {
                $q->where('fecha', '>=', $fechaLimite);
            })
            ->get();

        $contador = 0;
        foreach ($clientes as $cliente) {
            try {
                $cliente->delete();
                $contador++;
                Log::info("AutoArchivarClientesInactivos: cliente #{$cliente->id} ({$cliente->nombre}) archivado.");
            } catch (\Throwable $e) {
                Log::error("AutoArchivarClientesInactivos: error al archivar cliente #{$cliente->id}: {$e->getMessage()}");
            }
        }

        Log::info("AutoArchivarClientesInactivos: {$contador} cliente(s) archivado(s).");
    }
}
