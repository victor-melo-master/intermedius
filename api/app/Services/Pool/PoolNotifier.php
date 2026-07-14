<?php

namespace App\Services\Pool;

use App\Models\Operacion;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Notifications for pool events: SLA alarms, assignments, payments, cancellations.
 * Currently logs to the application log; may be extended to email/push/websocket.
 */
class PoolNotifier
{
    /**
     * Notifies that operations have been assigned to a pagador.
     *
     * @param  \Illuminate\Support\Collection<int, Operacion>  $operaciones
     * @param  User                                            $pagador
     */
    public function operacionesAsignadas(\Illuminate\Support\Collection $operaciones, User $pagador): void
    {
        $ids = $operaciones->pluck('id')->implode(', ');
        Log::info("Pool [asignada]: Operaciones {$ids} asignadas a pagador {$pagador->id}.");
    }

    /**
     * Notifies that an operation has been paid.
     */
    public function operacionPagada(Operacion $operacion, User $pagador): void
    {
        Log::info("Pool [pagada]: Operación {$operacion->id} pagada por {$pagador->id}.");
    }

    /**
     * Notifies that an operation has been cancelled.
     */
    public function operacionCancelada(Operacion $operacion, User $usuario, string $motivo): void
    {
        Log::warning("Pool [cancelada]: Operación {$operacion->id} cancelada por {$usuario->id}. Motivo: {$motivo}");
    }

    /**
     * Alerts that an operation has exceeded the SLA wait time.
     */
    public function slaExcedida(Operacion $operacion, int $minutosEspera): void
    {
        Log::warning("Pool [SLA]: Operación {$operacion->id} lleva {$minutosEspera} min en espera sin asignar.");
    }
}
