<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * Define qué proxies son confiables. '*' acepta cualquier proxy (como ngrok o devtunnels).
     */
    protected $proxies = '*';

    /**
     * Define qué encabezados se usan para detectar el protocolo y host correcto.
     */
    protected $headers = Request::HEADER_X_FORWARDED_ALL;
}
