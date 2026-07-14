<?php

namespace App\Events;

use App\Models\Operacion;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OperacionSoltada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Operacion $operacion)
    {}

    public function broadcastOn(): Channel
    {
        return new Channel('pool');
    }

    public function broadcastAs(): string
    {
        return 'operacion.soltada';
    }

    public function broadcastWith(): array
    {
        return [
            'operacion_id' => $this->operacion->id,
            'estado'       => $this->operacion->estado,
            'estado_pool'  => $this->operacion->estado_pool,
        ];
    }
}
