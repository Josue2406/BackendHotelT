<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClienteResetPassword extends Notification
{
    /*
    use Queueable;

    public function __construct()
    {
        //
    }

  
     * @return array<int, string>
     
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

  
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

  
     * @return array<string, mixed>
     
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }  */

    use Queueable;

    public function __construct(private string $token) {}

    public function via($notifiable) { return ['mail']; }

    public function toMail($notifiable)
    {
        $base  = rtrim(config('app.frontend_url', config('app.url')), '/');
        $email = urlencode($notifiable->email);
        $url   = "{$base}/reset-password?token={$this->token}&email={$email}";

        return (new MailMessage)
            ->subject('Restablecer contraseña')
            ->line('Haz clic en el botón para restablecer tu contraseña.')
            ->action('Restablecer contraseña', $url)
            ->line('Si no solicitaste este cambio, ignora este correo.');
    }
}
