<x-mail::message>
@php
    // Helpers de formato
    $money = fn($n) => '₡' . number_format((float)$n, 2);

    // Atajos del payload
    $p = $payload; 
@endphp

# ¡Tu reserva ha sido actualizada!

Número de reserva: **#{{ $reserva->id_reserva }}**  
Estado: **{{ $reserva->estado?->nombre ?? 'N/D' }}**  
Fecha de última actualización: **{{ now()->format('d/m/Y H:i') }}**

---

@switch($tipo)

@case('cambiar_habitacion')
## Cambio de habitación
- **Antes:** {{ $p['habitacion_antigua']['nombre'] }} (ID {{ $p['habitacion_antigua']['id'] }}) — {{ $money($p['habitacion_antigua']['precio']) }}
- **Ahora:** {{ $p['habitacion_nueva']['nombre'] }} (ID {{ $p['habitacion_nueva']['id'] }}) — {{ $money($p['habitacion_nueva']['precio']) }}

**Diferencia:** {{ $p['diferencia_precio'] >= 0 ? '+' : '-' }}{{ $money(abs($p['diferencia_precio'])) }}  
**Ajuste aplicado:** 
@php
    $ajusteTxt = [
        'cargo_adicional' => 'Cargo adicional',
        'reembolso'       => 'Reembolso',
        'sin_cambio'      => 'Sin cambio'
    ][$p['tipo_ajuste']] ?? 'N/D';
@endphp
**{{ $ajusteTxt }}** por {{ $money($p['monto_ajuste']) }}

@break

@case('modificar_fechas')
## Modificación de fechas
**Fechas originales:** {{ $p['fechas_originales']['llegada'] }} → {{ $p['fechas_originales']['salida'] }} ({{ $p['fechas_originales']['noches'] }} noches)  
**Nuevas fechas:** {{ $p['fechas_nuevas']['llegada'] }} → {{ $p['fechas_nuevas']['salida'] }} ({{ $p['fechas_nuevas']['noches'] }} noches)

**Precio anterior:** {{ $money($p['precios']['precio_anterior']) }}  
**Precio nuevo:** {{ $money($p['precios']['precio_nuevo']) }}  
**Diferencia:** {{ $p['precios']['diferencia'] >= 0 ? '+' : '-' }}{{ $money(abs($p['precios']['diferencia'])) }}

@isset($p['precios']['penalidad'])
**Penalidad por política:** {{ $money($p['precios']['penalidad']) }}
@endisset

**Ajuste total aplicado a la reserva:** {{ $p['precios']['ajuste_total'] >= 0 ? '+' : '-' }}{{ $money(abs($p['precios']['ajuste_total'])) }}

@isset($p['politica'])
> _Política aplicada:_ {{ $p['politica'] }}
@endisset
@break

@case('reducir_estadia')
## Reducción de estadía (checkout anticipado)
- **Noches canceladas:** {{ $p['reduccion']['noches_canceladas'] }}  
- **Original:** {{ $p['reduccion']['fecha_salida_original'] }} ({{ $p['reduccion']['noches_originales'] }} noches)  
- **Nueva salida:** {{ $p['reduccion']['fecha_salida_nueva'] }} ({{ $p['reduccion']['noches_nuevas'] }} noches)

**Precio original:** {{ $money($p['montos']['precio_original']) }}  
**Nuevo precio:** {{ $money($p['montos']['precio_nuevo']) }}  
**Monto de noches canceladas:** {{ $money($p['montos']['monto_noches_canceladas']) }}

@isset($p['montos']['penalidad'])
**Penalidad:** {{ $money($p['montos']['penalidad']) }}
@endisset

@isset($p['montos']['reembolso'])
**Reembolso:** {{ $money($p['montos']['reembolso']) }}
@endisset

@isset($p['politica'])
> _Política aplicada:_ {{ $p['politica'] }}
@endisset
@break

@default
## Actualización de reserva
Se han aplicado cambios a tu reserva.
@endswitch

---

## Resumen de cuenta
- **Total de la reserva:** {{ $money($p['reserva']['total_nuevo']) }}
- **Monto pagado:** {{ $money($p['reserva']['monto_pagado']) }}
- **Pendiente:** {{ $money($p['reserva']['monto_pendiente']) }}

@isset($p['multidivisa'])
<x-mail::panel>
**Multidivisa (estimado):**  
@foreach($p['multidivisa'] as $code => $val)
- {{ $code }} {{ number_format($val, 2) }}
@endforeach
</x-mail::panel>
@endisset

<x-mail::button :url="config('app.frontend_url').'/reservas/'.$reserva->id_reserva">
Ver mi reserva
</x-mail::button>

Si tienes dudas, responde a este correo y con gusto te ayudamos.  
Gracias por elegir **{{ config('app.name') }}**.

</x-mail::message>
