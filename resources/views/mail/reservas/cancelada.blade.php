{{-- resources/views/mail/reservas/cancelada.blade.php --}}
<x-mail::message>
# Tu reserva #{{ $reserva->id_reserva }} ha sido cancelada

Hola **{{ $cliente->nombre }}**,  
hemos procesado la **cancelación** de tu reserva. A continuación te dejamos el detalle.

---

### 🏨 Detalles de la reserva
- **Estado:** {{ $reserva->estado?->nombre ?? 'Cancelada' }}
- **Fecha de cancelación:** {{ \Illuminate\Support\Carbon::now()->format('d/m/Y H:i') }}
- **Total original:** ${{ number_format($reserva->getOriginal('total_monto_reserva') ?? $reserva->total_monto_reserva, 2) }}

@if(!empty($reserva->habitaciones) && $reserva->habitaciones->count())
---
### 🛏️ Habitaciones asociadas
<x-mail::panel>
@foreach($reserva->habitaciones as $h)
- **{{ $h->habitacion?->nombre ?? "Habitación #{$h->id_habitacion}" }}**  
  Del **{{ \Illuminate\Support\Carbon::parse($h->fecha_llegada)->format('d/m/Y') }}**
  al **{{ \Illuminate\Support\Carbon::parse($h->fecha_salida)->format('d/m/Y') }}**
@endforeach
</x-mail::panel>
@endif

@if(!empty($payload))
---
### 📄 Política aplicada
- **Días de anticipación:** {{ $payload['dias_anticipacion'] ?? 'N/D' }}
- **Fecha de llegada:** {{ $payload['fecha_llegada'] ?? 'N/D' }}
- **Política:** {{ $payload['politica']['nombre'] ?? 'N/D' }}
@if(!empty($payload['politica']['descripcion']))
- **Descripción:** {{ $payload['politica']['descripcion'] }}
@endif
@if(!empty($payload['mensaje']))
- **Notas:** {{ $payload['mensaje'] }}
@endif

---
### 💳 Resumen de montos
- **Monto pagado:** ${{ number_format($payload['monto_pagado'] ?? 0, 2) }}
- **Reembolso:** ${{ number_format($payload['reembolso'] ?? 0, 2) }}
- **Penalidad:** ${{ number_format($payload['penalidad'] ?? 0, 2) }}
@if(isset($payload['porcentaje_reembolso']))
- **% de reembolso sobre lo pagado:** {{ $payload['porcentaje_reembolso'] }}%
@endif
@if(array_key_exists('solicitar_reembolso', $payload))
- **¿Solicitaste procesamiento de reembolso?** {{ $payload['solicitar_reembolso'] ? 'Sí' : 'No' }}
@endif

@if(!empty($payload['habitaciones_liberadas']))
---
### 🔓 Habitaciones liberadas
<x-mail::panel>
@foreach($payload['habitaciones_liberadas'] as $hid)
- Habitación ID: {{ $hid }}
@endforeach
</x-mail::panel>
@endif

@if(!empty($payload['motivo']))
---
### 🗒️ Motivo de cancelación (cliente)
> {{ $payload['motivo'] }}
@endif
@endif

@php
  $front = config('app.frontend_url') ?? config('app.url');
@endphp

<x-mail::button :url="$front.'/reservas'">
Ver mis reservas
</x-mail::button>

Si esta cancelación fue por error o deseas **reprogramar**, por favor contáctanos.  
Puedes responder a este correo o comunicarte con nuestro equipo de atención.

Gracias por confiar en **{{ config('app.name') }}**.  
Saludos cordiales,<br>
El equipo de {{ config('app.name') }}
</x-mail::message>
