<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
//use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Auth\Notifications\ResetPassword; // ← IMPORTANTE
use App\Models\reserva\Reserva;
use App\Models\reserva\ReservaPago;
use App\Observers\ReservaObserver;
use App\Observers\ReservaPagoObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar Observers
        Reserva::observe(ReservaObserver::class);
        ReservaPago::observe(ReservaPagoObserver::class);

        // URL del front que muestra el formulario de reset
        // Ponla en .env como APP_FRONTEND_URL=https://tu-frontend.com
       ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $base  = rtrim(config('app.frontend_url', config('app.url')), '/');
            $email = urlencode($notifiable->email);

            // URL de tu FRONT que mostrará el form de reset
            return "{$base}/reset-password?token={$token}&email={$email}";
        });

    }
}
