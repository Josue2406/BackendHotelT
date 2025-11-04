<x-mail::message>
# ¡Gracias por tu reserva, {{ $cliente->nombre }}!

Tu reserva ha sido creada exitosamente.  
A continuación te dejamos un resumen de los detalles:

---

### 🏨 Detalles de la reserva
- **Estado:** {{ $reserva->estado?->nombre ?? 'Pendiente' }}
- **Fuente:** {{ $reserva->fuente?->nombre ?? 'N/D' }}
- **Fecha de creación:** {{ $reserva->fecha_creacion?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}
- **Total:** ${{ number_format($reserva->total_monto_reserva, 2) }}
- **Numero de reserva:** {{ $reserva->id_reserva }}

---

### 🛏️ Habitaciones reservadas
<x-mail::panel>
@foreach($reserva->habitaciones as $h)
- **{{ $h->habitacion?->nombre ?? "Habitación #{$h->id_habitacion}" }}**  
  Del **{{ \Illuminate\Support\Carbon::parse($h->fecha_llegada)->format('d/m/Y') }}**
  al **{{ \Illuminate\Support\Carbon::parse($h->fecha_salida)->format('d/m/Y') }}**  
  Adultos: {{ $h->adultos }}, Niños: {{ $h->ninos }}, Bebés: {{ $h->bebes }}  
  Subtotal: ${{ number_format($h->subtotal, 2) }}

@endforeach
</x-mail::panel>

<x-mail::button :url="config('app.frontend_url').'/reservas/'.$reserva->id_reserva">
Ver mi reserva
</x-mail::button>

Gracias por confiar en **{{ config('app.name') }}**.  
Si tienes alguna duda, puedes responder directamente a este correo.

Saludos cordiales,<br>
El equipo de {{ config('app.name') }}
</x-mail::message>
