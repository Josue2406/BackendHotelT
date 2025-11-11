<x-mail::message>
# ¡Hemos recibido tu pago!

Hola **{{ $cliente->nombre }}**,

Hemos registrado un pago para tu **reserva #{{ $reserva->id_reserva }}**. Te compartimos el detalle:

---

### 💳 Detalle del pago
- **Monto**: {{ $payload['moneda']['codigo'] ?? '' }} {{ number_format($payload['monto'], 2) }}
@if(!empty($payload['monto_usd']))
- **Equivalente en USD**: ${{ number_format($payload['monto_usd'], 2) }}
@endif
@if(!empty($payload['tipo_cambio']))
- **Tipo de cambio**: {{ $payload['tipo_cambio_formateado'] }}
@endif
- **Método**: {{ $payload['metodo_pago'] ?? 'N/D' }}
- **Estado del pago**: {{ $payload['estado_pago'] ?? 'N/D' }}
@if(!empty($payload['referencia']))
- **Referencia**: {{ $payload['referencia'] }}
@endif
@if(!empty($payload['notas']))
- **Notas**: {{ $payload['notas'] }}
@endif
- **Fecha de pago**: {{ \Illuminate\Support\Carbon::parse($payload['fecha_pago'])->format('d/m/Y H:i') }}

---

### 🧾 Estado de tu reserva
- **Total de la reserva**: ${{ number_format($payload['reserva']['total_monto_reserva'] ?? 0, 2) }}
- **Monto pagado**: ${{ number_format($payload['reserva']['monto_pagado'] ?? 0, 2) }}
- **Monto pendiente**: ${{ number_format($payload['reserva']['monto_pendiente'] ?? 0, 2) }}
- **Porcentaje pagado**: {{ $payload['reserva']['porcentaje_pagado'] ?? 0 }}%

@isset($payload['hitos'])
<x-mail::panel>
@foreach($payload['hitos'] as $h)
- {{ $h }}
@endforeach
</x-mail::panel>
@endisset

@php
  $front = config('app.frontend_url') ?? config('app.url');
@endphp

<x-mail::button :url="$front.'/reservas/'.$reserva->id_reserva">
Ver mi reserva
</x-mail::button>

Gracias por confiar en **{{ config('app.name') }}**.  
Si tienes dudas, responde a este correo.

Saludos cordiales,  
El equipo de {{ config('app.name') }}
</x-mail::message>
