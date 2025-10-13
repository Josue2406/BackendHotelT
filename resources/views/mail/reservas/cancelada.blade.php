<x-mail::message>
# Tu reserva #{{ $reserva->id_reserva }} ha sido cancelada

Hola **{{ $cliente->nombre }}**,  
Lamentamos informarte que tu reserva ha sido **cancelada**.

---

### 🏨 Detalles de la reserva cancelada
- **Estado:** {{ $reserva->estado?->nombre ?? 'Cancelada' }}
- **Fecha de cancelación:** {{ now()->format('d/m/Y H:i') }}
- **Total original:** ₡{{ number_format($reserva->total_monto_reserva, 2) }}

@if(!empty($reserva->habitaciones) && count($reserva->habitaciones) > 0)
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

---

<x-mail::button :url="config('app.frontend_url').'/reservas'">
Ver mis reservas
</x-mail::button>

Si esta cancelación fue un error o deseas reprogramar, por favor contáctanos a la brevedad.  
Puedes responder directamente a este correo o comunicarte con nuestro equipo de atención.

Gracias por confiar en **{{ config('app.name') }}**.  
Esperamos poder atenderte en una próxima ocasión.

Saludos cordiales,<br>
El equipo de {{ config('app.name') }}
</x-mail::message>
