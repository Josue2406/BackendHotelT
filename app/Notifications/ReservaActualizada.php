<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
// Si más adelante usas colas: use Illuminate\Contracts\Queue\ShouldQueue;

class ReservaActualizada extends Notification
{
    use Queueable;

    protected $reserva;
    protected $cambios;

    /**
     * Crear nueva instancia.
     *
     * @param  mixed  $reserva
     * @param  array|null  $cambios  (opcional, lista de campos modificados)
     */
    public function __construct($reserva, $cambios = [])
    {
        $this->reserva = $reserva;
        $this->cambios = $cambios;
    }

    /**
     * Canales de notificación.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Contenido del correo.
     */
    public function toMail($notifiable): MailMessage
    {
        $r = $this->reserva->loadMissing(['cliente','estado','fuente','habitaciones.habitacion']);

        return (new MailMessage)
            ->subject("Tu reserva #{$r->id_reserva} fue actualizada")
            ->markdown('mail.reservas.actualizada', [
                'reserva' => $r,
                'cliente' => $r->cliente,
                'cambios' => $this->cambios,
            ]);
    }

    /**
     * Representación opcional como array.
     */
    public function toArray($notifiable): array
    {
        return [
            'id_reserva' => $this->reserva->id_reserva,
            'cambios' => $this->cambios,
        ];
    }
}
