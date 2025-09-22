<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NuevoMantenimientoAsignado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $mantenimiento;

    public function __construct(array $mantenimiento)
    {
        $this->mantenimiento = $mantenimiento;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('mantenimientos');
    }

    public function broadcastAs(): string
    {
        return 'NuevoMantenimientoAsignado';
    }

    public function broadcastWith(): array
    {
        return [
            'title'   => 'Nuevo mantenimiento asignado',
            'message' => 'Tienes una nueva tarea de mantenimiento asignada.',
            'data'    => $this->mantenimiento,
        ];
    }
}
