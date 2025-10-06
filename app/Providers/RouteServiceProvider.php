<?php

namespace App\Providers;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;


class RouteServiceProvider extends ServiceProvider
{
      public function register(): void
    {
        //
    }
    public function boot(): void
    {
          // Definir rate limiter "login"
        RateLimiter::for('login', function (Request $request) {
            $email = mb_strtolower((string) $request->input('email', ''));
            // 5 intentos por minuto por combinación email+IP
            return [
                Limit::perMinute(5)->by($email.'|'.$request->ip()),
            ];
        });


        // Si aquí ya tienes la carga de rutas, déjala como está.
        // $this->routes(function () { ... });
    }
}