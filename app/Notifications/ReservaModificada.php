<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // si quieres colas
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservaModificada extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $tipo,      // 'cambiar_habitacion' | 'modificar_fechas' | 'reducir_estadia'
        public array $payload,    // el array detallado que retorna el service
        public $reserva           // instancia de Reserva con relaciones
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Puedes pasar datos al markdown con ->markdown(view, data)
        return (new MailMessage)
            ->subject('Actualización de tu reserva #' . $this->reserva->id_reserva)
            ->markdown('mail.reservas.modificada', [
                'tipo'     => $this->tipo,
                'payload'  => $this->payload,
                'reserva'  => $this->reserva,
                'cliente'  => $notifiable,
            ]);
    }
}
