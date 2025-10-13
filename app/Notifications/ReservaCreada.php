<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
// Si luego activas colas, puedes agregar:
// use Illuminate\Contracts\Queue\ShouldQueue;

class ReservaCreada extends Notification
{
    use Queueable;

    /**
     * La reserva asociada a la notificación.
     */
    protected $reserva;

    /**
     * Crear una nueva instancia de la notificación.
     */
    public function __construct($reserva)
    {
        $this->reserva = $reserva;
    }

    /**
     * Canales de entrega de la notificación.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Representación del correo electrónico.
     */
    public function toMail($notifiable): MailMessage
    {
        // Cargamos relaciones necesarias
        $r = $this->reserva->loadMissing(['cliente','estado','fuente','habitaciones.habitacion']);

        return (new MailMessage)
            ->subject("Tu reserva fue creada")
            ->markdown('mail.reservas.creada', [
                'reserva' => $r,
                'cliente' => $r->cliente,
            ]);
    }

    /**
     * Representación opcional como array (no la usamos aquí).
     */
    public function toArray($notifiable): array
    {
        return [
            'id_reserva' => $this->reserva->id_reserva,
            'cliente' => $this->reserva->cliente?->email,
        ];
    }
}
