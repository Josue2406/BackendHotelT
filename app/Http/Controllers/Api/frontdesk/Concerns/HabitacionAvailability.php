<?php

namespace App\Http\Controllers\Api\frontdesk\Concerns;

use App\Models\reserva\ReservaHabitacion;
use App\Models\house_keeping\HabBloqueoOperativo;
use App\Models\check_in\AsignacionHabitacion;

trait HabitacionAvailability
{
    /** Detecta choques de una habitación en un rango 
    private function hayChoqueHab(int $idHabitacion, string $desde, string $hasta): bool
    {
        $choqueReserva = ReservaHabitacion::where('id_habitacion', $idHabitacion)
            ->where('fecha_llegada', '<', $hasta)
            ->where('fecha_salida',  '>', $desde)
            ->exists();

        $choqueAsign = AsignacionHabitacion::where('id_hab', $idHabitacion)
            ->where('fecha_asignacion', '<', $hasta)
            ->exists();

        $choqueBloqueo = HabBloqueoOperativo::where('id_habitacion', $idHabitacion)
            ->where('fecha_ini', '<', $hasta)
            ->where('fecha_fin', '>', $desde)
            ->exists();

        return $choqueReserva || $choqueAsign || $choqueBloqueo;
    } */
    private function hayChoqueHab(int $idHabitacion, string $desde, string $hasta, ?int $excluirReservaId = null): bool
    {
        // 1) Choque contra reservas por habitación
        $choqueReserva = ReservaHabitacion::where('id_habitacion', $idHabitacion)
            ->when($excluirReservaId, fn ($q) => $q->where('id_reserva', '!=', $excluirReservaId))
            ->where('fecha_llegada', '<', $hasta)
            ->whereRaw('DATE_ADD(fecha_salida, INTERVAL 1 DAY) > ?', [$desde]) // bloquea mismo día
            ->exists();

        // 2) Choque contra asignaciones (usando rango de la estadía)
        // Asegúrate de tener la relación: AsignacionHabitacion->estadia (belongsTo)
        $choqueAsign = AsignacionHabitacion::where('id_hab', $idHabitacion)
            ->when($excluirReservaId, fn ($q) => $q->where('id_reserva', '!=', $excluirReservaId))
            ->whereHas('estadia', function ($q) use ($desde, $hasta) {
                $q->where('fecha_llegada', '<', $hasta)
                  ->whereRaw('DATE_ADD(fecha_salida, INTERVAL 1 DAY) > ?', [$desde]); // bloquea mismo día
            })
            ->exists();

        // 3) Bloqueos operativos (mismo criterio de solapamiento)
        $choqueBloqueo = HabBloqueoOperativo::where('id_habitacion', $idHabitacion)
            ->where('fecha_ini', '<', $hasta)
            ->whereRaw('DATE_ADD(fecha_fin, INTERVAL 1 DAY) > ?', [$desde]) // bloquea mismo día
            ->exists();

        return $choqueReserva || $choqueAsign || $choqueBloqueo;
    }
}
