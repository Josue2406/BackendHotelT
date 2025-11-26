<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NuevaLimpiezaAsignada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $limpieza;

    public function __construct(array $limpieza)
    {
        $this->limpieza = $limpieza;
    }

    public function broadcastOn(): Channel
    {
        // Canal público (puedes cambiarlo a privado/presencia si luego necesitas auth)
        return new Channel('limpiezas');
    }

    // 👇 Este es el “nombre de la alerta” (el nombre del evento que escucha el frontend)
    public function broadcastAs(): string
    {
        return 'NuevaLimpiezaAsignada';
    }

    // 👇 Estandariza el payload para toasts/notificaciones en el frontend
    public function broadcastWith(): array
    {
        return [
            'title'   => 'Nueva limpieza asignada',
            'message' => 'Tienes una nueva tarea de limpieza asignada.',
            'data'    => $this->limpieza,
        ];
    }
}
