<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservaCancelada extends Notification
{
    use Queueable;

    public function __construct(
        public array $payload, // detalles de la cancelación (preview + extras)
        public $reserva        // instancia de Reserva con relaciones
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Cancelación de tu reserva #'.$this->reserva->id_reserva)
            ->markdown('mail.reservas.cancelada', [
                'reserva' => $this->reserva,
                'cliente' => $notifiable,
                'payload' => $this->payload,
            ]);
    }
}
