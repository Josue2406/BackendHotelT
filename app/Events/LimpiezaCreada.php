<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LimpiezaCreada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $limpieza; // ✅ buena práctica: declarar tipo

    /**
     * Crea una nueva instancia del evento.
     */
    public function __construct(array $limpieza)
    {
        $this->limpieza = $limpieza;
    }

    /**
     * Canal por el que se enviará el evento.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('limpiezas'); // canal público
    }

    /**
     * Nombre del evento que se verá en el frontend (opcional pero útil).
     */
    public function broadcastAs(): string
    {
        return 'LimpiezaCreada'; // nombre usado por Echo.listen('LimpiezaCreada')
    }
}
