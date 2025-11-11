<x-mail::message>
# Tu reserva #{{ $reserva->id_reserva }} fue actualizada

Hola **{{ $cliente->nombre }}**,  
Te informamos que tu reserva ha sido actualizada con éxito.

@if(!empty($cambios))
### Cambios realizados
<x-mail::panel>
@foreach($cambios as $campo => $valor)
- **{{ ucfirst(str_replace('_', ' ', $campo)) }}:** {{ is_scalar($valor) ? $valor : json_encode($valor) }}
@endforeach
</x-mail::panel>
@endif

<x-mail::button :url="config('app.frontend_url').'/reservas/'.$reserva->id_reserva">
Ver detalles de mi reserva
</x-mail::button>

Gracias por confiar en **{{ config('app.name') }}**.  
Si no solicitaste esta modificación, contáctanos para más información.

Saludos cordiales,<br>
El equipo de {{ config('app.name') }}
</x-mail::message>
