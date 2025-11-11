<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // opcional si usas colas
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservaPagoRecibido extends Notification // implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public array $payload,   // detalles del pago + totales de la reserva
        public $reserva          // instancia de Reserva con relaciones
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pago recibido - Reserva #'.$this->reserva->id_reserva)
            ->markdown('mail.reservas.pago', [
                'reserva' => $this->reserva,
                'cliente' => $notifiable,
                'payload' => $this->payload,
            ]);
    }
}
