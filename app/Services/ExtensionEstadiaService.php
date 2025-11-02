<?php

namespace App\Services;

use App\Models\reserva\Reserva;
use App\Models\reserva\ReservaHabitacion;
use App\Models\habitacion\Habitacione;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExtensionEstadiaService
{
    /**
     * Verificar disponibilidad para extender estadía en la misma habitación
     *
     * @param ReservaHabitacion $reservaHab
     * @param Carbon $nuevaFechaSalida
     * @return array
     */
    public function verificarDisponibilidadMismaHabitacion(ReservaHabitacion $reservaHab, Carbon $nuevaFechaSalida): array
    {
        $habitacion = $reservaHab->habitacion;
        $fechaSalidaActual = $reservaHab->fecha_salida;

        // Buscar conflictos en el rango de extensión
        $conflicto = ReservaHabitacion::where('id_habitacion', $habitacion->id_habitacion)
            ->where('id_reserva_hab', '!=', $reservaHab->id_reserva_hab)
            ->where(function ($query) use ($fechaSalidaActual, $nuevaFechaSalida) {
                $query->whereBetween('fecha_llegada', [$fechaSalidaActual, $nuevaFechaSalida])
                    ->orWhereBetween('fecha_salida', [$fechaSalidaActual, $nuevaFechaSalida])
                    ->orWhere(function ($q) use ($fechaSalidaActual, $nuevaFechaSalida) {
                        $q->where('fecha_llegada', '<=', $fechaSalidaActual)
                          ->where('fecha_salida', '>=', $nuevaFechaSalida);
                    });
            })
            ->with('reserva.cliente')
            ->first();

        if ($conflicto) {
            return [
                'disponible' => false,
                'tipo' => 'conflicto',
                'conflicto' => $conflicto,
                'fecha_conflicto' => $conflicto->fecha_llegada,
                'dias_disponibles' => $fechaSalidaActual->diffInDays($conflicto->fecha_llegada),
                'mensaje' => "La habitación está reservada desde {$conflicto->fecha_llegada->format('d/m/Y')}. Puede extender hasta un día antes.",
            ];
        }

        return [
            'disponible' => true,
            'tipo' => 'disponible',
            'mensaje' => 'La habitación está disponible para la extensión completa.',
        ];
    }

    /**
     * Buscar habitaciones alternativas disponibles
     *
     * @param ReservaHabitacion $reservaHab
     * @param Carbon $fechaInicioExtension
     * @param Carbon $nuevaFechaSalida
     * @return array
     */
    public function buscarHabitacionesAlternativas(
        ReservaHabitacion $reservaHab,
        Carbon $fechaInicioExtension,
        Carbon $nuevaFechaSalida
    ): array {
        $habitacionActual = $reservaHab->habitacion;

        // Buscar habitaciones del mismo tipo o similar capacidad
        $habitacionesDisponibles = Habitacione::where('id_tipo_habitacion', $habitacionActual->id_tipo_habitacion)
            ->where('id_habitacion', '!=', $habitacionActual->id_habitacion)
            ->whereDoesntHave('reservaHabitaciones', function ($query) use ($fechaInicioExtension, $nuevaFechaSalida) {
                $query->where(function ($q) use ($fechaInicioExtension, $nuevaFechaSalida) {
                    $q->whereBetween('fecha_llegada', [$fechaInicioExtension, $nuevaFechaSalida])
                      ->orWhereBetween('fecha_salida', [$fechaInicioExtension, $nuevaFechaSalida])
                      ->orWhere(function ($subQ) use ($fechaInicioExtension, $nuevaFechaSalida) {
                          $subQ->where('fecha_llegada', '<=', $fechaInicioExtension)
                               ->where('fecha_salida', '>=', $nuevaFechaSalida);
                      });
                });
            })
            ->with(['tipoHabitacion', 'estado'])
            ->get();

        return $habitacionesDisponibles->map(function ($habitacion) use ($fechaInicioExtension, $nuevaFechaSalida) {
            $noches = $fechaInicioExtension->diffInDays($nuevaFechaSalida);
            $costoAdicional = $habitacion->precio_base * $noches;

            return [
                'id_habitacion' => $habitacion->id_habitacion,
                'nombre' => $habitacion->nombre,
                'tipo' => $habitacion->tipoHabitacion->nombre ?? 'N/A',
                'capacidad' => $habitacion->capacidad,
                'precio_noche' => $habitacion->precio_base,
                'costo_adicional' => $costoAdicional,
                'estado' => $habitacion->estado->nombre ?? 'Disponible',
            ];
        })->toArray();
    }

    /**
     * Calcular costo adicional por extensión
     *
     * @param ReservaHabitacion $reservaHab
     * @param Carbon $nuevaFechaSalida
     * @param Habitacione|null $habitacionAlternativa
     * @return array
     */
    public function calcularCostoAdicional(
        ReservaHabitacion $reservaHab,
        Carbon $nuevaFechaSalida,
        ?Habitacione $habitacionAlternativa = null
    ): array {
        $fechaSalidaActual = $reservaHab->fecha_salida;
        $nochesAdicionales = $fechaSalidaActual->diffInDays($nuevaFechaSalida);

        // Usar habitación actual o alternativa
        $habitacion = $habitacionAlternativa ?? $reservaHab->habitacion;
        $precioPorNoche = $habitacion->precio_base ?? 0;

        $subtotalAdicional = $precioPorNoche * $nochesAdicionales;

        // Si hay cambio de habitación, puede haber un cargo adicional
        $cargoCambio = 0;
        if ($habitacionAlternativa && $habitacionAlternativa->id_habitacion != $reservaHab->id_habitacion) {
            $cargoCambio = 0; // Puedes configurar un cargo fijo por cambio si lo deseas
        }

        return [
            'noches_adicionales' => $nochesAdicionales,
            'precio_por_noche' => $precioPorNoche,
            'subtotal_adicional' => $subtotalAdicional,
            'cargo_cambio_habitacion' => $cargoCambio,
            'total_adicional' => $subtotalAdicional + $cargoCambio,
            'habitacion_utilizada' => [
            'id' => $habitacion->id_habitacion,
            'nombre' => $habitacion->nombre,
            ],
        ];
    }

    /**
     * Extender estadía en la misma habitación
     *
     * @param ReservaHabitacion $reservaHab
     * @param Carbon $nuevaFechaSalida
     * @param string|null $motivo
     * @return array
     */
    public function extenderEnMismaHabitacion(
        ReservaHabitacion $reservaHab,
        Carbon $nuevaFechaSalida,
        ?string $motivo = null
    ): array {
        return DB::transaction(function () use ($reservaHab, $nuevaFechaSalida, $motivo) {
            $reserva = $reservaHab->reserva;
            $fechaSalidaOriginal = $reservaHab->fecha_salida->copy();

            // Calcular costo adicional
            $calculo = $this->calcularCostoAdicional($reservaHab, $nuevaFechaSalida);

            // Actualizar fecha de salida
            $reservaHab->update([
                'fecha_salida' => $nuevaFechaSalida,
            ]);

            // Recalcular subtotal de la reserva de habitación
            $reservaHab->load('habitacion');
            $nuevoSubtotal = $reservaHab->calcularSubtotal();
            $reservaHab->update(['subtotal' => $nuevoSubtotal]);

            // Actualizar total de la reserva
            $nuevoTotalReserva = $reserva->habitaciones()->sum('subtotal');
            $nuevoPendiente = $nuevoTotalReserva - $reserva->monto_pagado;

            $reserva->update([
                'total_monto_reserva' => $nuevoTotalReserva,
                'monto_pendiente' => max(0, $nuevoPendiente),
                'pago_completo' => $nuevoPendiente <= 0,
            ]);

            // Registrar en notas
            $notaExtension = sprintf(
                "Extensión de estadía: %d noches adicionales. Fecha salida original: %s, Nueva: %s. Costo adicional: $%s%s",
                $calculo['noches_adicionales'],
                $fechaSalidaOriginal->format('d/m/Y'),
                $nuevaFechaSalida->format('d/m/Y'),
                number_format($calculo['total_adicional'], 2),
                $motivo ? ". Motivo: {$motivo}" : ''
            );

            $reserva->update([
                'notas' => $reserva->notas ? $reserva->notas . "\n\n" . $notaExtension : $notaExtension
            ]);

            Log::info("Extensión de estadía procesada", [
                'id_reserva' => $reserva->id_reserva,
                'id_reserva_hab' => $reservaHab->id_reserva_hab,
                'noches_adicionales' => $calculo['noches_adicionales'],
                'costo_adicional' => $calculo['total_adicional'],
            ]);

            return [
                'success' => true,
                'tipo_extension' => 'misma_habitacion',
                'reserva_hab' => $reservaHab->fresh(),
                'calculo' => $calculo,
                'reserva' => $reserva->fresh(['habitaciones.habitacion']),
                'mensaje' => "Extensión exitosa. Se agregaron {$calculo['noches_adicionales']} noches adicionales.",
            ];
        });
    }

    /**
     * Extender estadía con cambio de habitación
     *
     * @param ReservaHabitacion $reservaHab
     * @param Carbon $fechaCambio
     * @param Carbon $nuevaFechaSalida
     * @param Habitacione $habitacionAlternativa
     * @param string|null $motivo
     * @return array
     */
    public function extenderConCambioHabitacion(
        ReservaHabitacion $reservaHab,
        Carbon $fechaCambio,
        Carbon $nuevaFechaSalida,
        Habitacione $habitacionAlternativa,
        ?string $motivo = null
    ): array {
        return DB::transaction(function () use ($reservaHab, $fechaCambio, $nuevaFechaSalida, $habitacionAlternativa, $motivo) {
            $reserva = $reservaHab->reserva;
            $habitacionOriginal = $reservaHab->habitacion;

            // Ajustar fecha de salida de la reserva original
            $reservaHab->update(['fecha_salida' => $fechaCambio]);
            $reservaHab->load('habitacion');
            $subtotalOriginal = $reservaHab->calcularSubtotal();
            $reservaHab->update(['subtotal' => $subtotalOriginal]);

            // Crear nueva reserva de habitación para la extensión
            $nuevaReservaHab = ReservaHabitacion::create([
                'id_reserva' => $reserva->id_reserva,
                'id_habitacion' => $habitacionAlternativa->id_habitacion,
                'fecha_llegada' => $fechaCambio,
                'fecha_salida' => $nuevaFechaSalida,
                'adultos' => $reservaHab->adultos,
                'ninos' => $reservaHab->ninos,
                'bebes' => $reservaHab->bebes,
                'subtotal' => 0,
            ]);

            $nuevaReservaHab->load('habitacion');
            $subtotalNuevo = $nuevaReservaHab->calcularSubtotal();
            $nuevaReservaHab->update(['subtotal' => $subtotalNuevo]);

            // Actualizar total de reserva
            $nuevoTotalReserva = $reserva->habitaciones()->sum('subtotal');
            $nuevoPendiente = $nuevoTotalReserva - $reserva->monto_pagado;

            $reserva->update([
                'total_monto_reserva' => $nuevoTotalReserva,
                'monto_pendiente' => max(0, $nuevoPendiente),
                'pago_completo' => $nuevoPendiente <= 0,
            ]);

            // Calcular costo adicional total
            $nochesAdicionales = $reservaHab->fecha_salida->copy()->diffInDays($nuevaFechaSalida);
            $costoAdicional = $nuevoPendiente - $reserva->getOriginal('monto_pendiente');

            // Registrar en notas
            $notaCambio = sprintf(
                "Extensión con cambio de habitación: De '%s' a '%s' desde %s. %d noches adicionales. Costo adicional: $%s%s",
                $habitacionOriginal->nombre,
                $habitacionAlternativa->nombre,
                $fechaCambio->format('d/m/Y'),
                $nochesAdicionales,
                number_format($costoAdicional, 2),
                $motivo ? ". Motivo: {$motivo}" : ''
            );

            $reserva->update([
                'notas' => $reserva->notas ? $reserva->notas . "\n\n" . $notaCambio : $notaCambio
            ]);

            Log::info("Extensión con cambio de habitación procesada", [
                'id_reserva' => $reserva->id_reserva,
                'habitacion_original' => $habitacionOriginal->nombre,
                'habitacion_nueva' => $habitacionAlternativa->nombre,
                'fecha_cambio' => $fechaCambio->toDateString(),
                'noches_adicionales' => $nochesAdicionales,
            ]);

            return [
                'success' => true,
                'tipo_extension' => 'cambio_habitacion',
                'reserva_hab_original' => $reservaHab->fresh(),
                'reserva_hab_nueva' => $nuevaReservaHab->fresh(),
                'habitacion_original' => $habitacionOriginal->nombre,
                'habitacion_nueva' => $habitacionAlternativa->nombre,
                'fecha_cambio' => $fechaCambio,
                'noches_adicionales' => $nochesAdicionales,
                'costo_adicional' => $costoAdicional,
                'reserva' => $reserva->fresh(['habitaciones.habitacion']),
                'mensaje' => "Extensión exitosa con cambio de habitación. Se agregaron {$nochesAdicionales} noches adicionales.",
            ];
        });
    }
}