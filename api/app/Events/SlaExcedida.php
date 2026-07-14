<?php

namespace App\Events;

use App\Models\Operacion;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SlaExcedida implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Operacion $operacion, public int $minutosEspera)
    {}

    public function broadcastOn(): Channel
    {
        return new Channel('pool');
    }

    public function broadcastAs(): string
    {
        return 'sla.excedida';
    }

    public function broadcastWith(): array
    {
        return [
            'operacion_id'   => $this->operacion->id,
            'minutos_espera' => $this->minutosEspera,
            'created_at'     => $this->operacion->created_at->toIso8601String(),
        ];
    }
}
