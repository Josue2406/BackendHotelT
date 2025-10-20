<?php

namespace App\Observers;

use App\Models\reserva\Reserva;
use App\Models\reserva\EstadoReserva;
use App\Models\habitacion\EstadoHabitacion;
use App\Services\CodigoReservaService;
use Illuminate\Support\Facades\Log;

class ReservaObserver
{
    /**
     * Handle the Reserva "creating" event.
     * Se ejecuta ANTES de crear el registro
     */
    public function creating(Reserva $reserva): void
    {
        // Generar código único si no tiene uno
        if (empty($reserva->codigo_reserva)) {
            $codigoService = app(CodigoReservaService::class);
            $reserva->codigo_reserva = $codigoService->generarCodigoUnico();

            Log::info("Código de reserva generado automáticamente", [
                'codigo_reserva' => $reserva->codigo_reserva,
            ]);
        }
    }

    /**
     * Handle the Reserva "updating" event.
     * Se ejecuta ANTES de que se actualice el registro
     */
    public function updating(Reserva $reserva): void
    {
        // Detectar si cambió el estado de la reserva
        if ($reserva->isDirty('id_estado_res')) {
            $estadoAnterior = $reserva->getOriginal('id_estado_res');
            $estadoNuevo = $reserva->id_estado_res;

            Log::info("Cambio de estado de reserva detectado", [
                'id_reserva' => $reserva->id_reserva,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $estadoNuevo,
            ]);
        }
    }

    /**
     * Handle the Reserva "updated" event.
     * Se ejecuta DESPUÉS de que se actualizó el registro
     */
    public function updated(Reserva $reserva): void
    {
        // Verificar si cambió el estado de la reserva
        if ($reserva->wasChanged('id_estado_res')) {
            $estadoAnterior = $reserva->getOriginal('id_estado_res');
            $estadoNuevo = $reserva->id_estado_res;

            // Si la reserva fue cancelada, liberar habitaciones
            if ($estadoNuevo == EstadoReserva::ESTADO_CANCELADA) {
                $this->liberarHabitaciones($reserva);
            }

            // Si la reserva fue confirmada, marcar habitaciones como ocupadas
            if ($estadoNuevo == EstadoReserva::ESTADO_CONFIRMADA) {
                $this->marcarHabitacionesConfirmadas($reserva);
            }

            // Si la reserva hizo check-in, marcar habitaciones como ocupadas
            if ($estadoNuevo == EstadoReserva::ESTADO_CHECKIN) {
                $this->marcarHabitacionesOcupadas($reserva);
            }

            // Si la reserva hizo check-out, marcar habitaciones como sucias
            if ($estadoNuevo == EstadoReserva::ESTADO_CHECKOUT) {
                $this->marcarHabitacionesSucias($reserva);
            }
        }
    }

    /**
     * Liberar habitaciones cuando una reserva es cancelada
     */
    protected function liberarHabitaciones(Reserva $reserva): void
    {
        // Cargar habitaciones relacionadas
        $reserva->load('habitaciones.habitacion');

        foreach ($reserva->habitaciones as $reservaHabitacion) {
            if ($reservaHabitacion->habitacion) {
                $habitacion = $reservaHabitacion->habitacion;

                // Solo cambiar a disponible si no está en mantenimiento
                if ($habitacion->id_estado_hab != EstadoHabitacion::ESTADO_MANTENIMIENTO) {
                    $habitacion->update([
                        'id_estado_hab' => EstadoHabitacion::ESTADO_DISPONIBLE
                    ]);

                    Log::info("Habitación liberada por cancelación de reserva", [
                        'id_reserva' => $reserva->id_reserva,
                        'id_habitacion' => $habitacion->id_habitacion,
                        'nombre_habitacion' => $habitacion->nombre,
                    ]);
                }
            }
        }
    }

    /**
     * Marcar habitaciones como ocupadas cuando la reserva es confirmada
     */
    protected function marcarHabitacionesConfirmadas(Reserva $reserva): void
    {
        // Cargar habitaciones relacionadas
        $reserva->load('habitaciones.habitacion');

        foreach ($reserva->habitaciones as $reservaHabitacion) {
            if ($reservaHabitacion->habitacion) {
                $habitacion = $reservaHabitacion->habitacion;

                // Verificar si la fecha de llegada es hoy o ya pasó
                $fechaLlegada = $reservaHabitacion->fecha_llegada;
                $hoy = now();

                if ($fechaLlegada->lte($hoy)) {
                    // Si la fecha de llegada es hoy o ya pasó, marcar como ocupada
                    $habitacion->update([
                        'id_estado_hab' => EstadoHabitacion::ESTADO_OCUPADA
                    ]);
                } else {
                    // Si la reserva es futura, dejar disponible pero reservada
                    // (esto lo puedes manejar con otra lógica si tienes un estado "Reservada")
                    Log::info("Reserva confirmada para fecha futura", [
                        'id_reserva' => $reserva->id_reserva,
                        'id_habitacion' => $habitacion->id_habitacion,
                        'fecha_llegada' => $fechaLlegada->toDateString(),
                    ]);
                }
            }
        }
    }

    /**
     * Marcar habitaciones como ocupadas cuando se hace check-in
     */
    protected function marcarHabitacionesOcupadas(Reserva $reserva): void
    {
        // Cargar habitaciones relacionadas
        $reserva->load('habitaciones.habitacion');

        foreach ($reserva->habitaciones as $reservaHabitacion) {
            if ($reservaHabitacion->habitacion) {
                $habitacion = $reservaHabitacion->habitacion;

                $habitacion->update([
                    'id_estado_hab' => EstadoHabitacion::ESTADO_OCUPADA
                ]);

                Log::info("Habitación marcada como ocupada por check-in", [
                    'id_reserva' => $reserva->id_reserva,
                    'id_habitacion' => $habitacion->id_habitacion,
                    'nombre_habitacion' => $habitacion->nombre,
                ]);
            }
        }
    }

    /**
     * Marcar habitaciones como sucias cuando se hace check-out
     */
    protected function marcarHabitacionesSucias(Reserva $reserva): void
    {
        // Cargar habitaciones relacionadas
        $reserva->load('habitaciones.habitacion');

        foreach ($reserva->habitaciones as $reservaHabitacion) {
            if ($reservaHabitacion->habitacion) {
                $habitacion = $reservaHabitacion->habitacion;

                $habitacion->update([
                    'id_estado_hab' => EstadoHabitacion::ESTADO_SUCIA
                ]);

                Log::info("Habitación marcada como sucia por check-out", [
                    'id_reserva' => $reserva->id_reserva,
                    'id_habitacion' => $habitacion->id_habitacion,
                    'nombre_habitacion' => $habitacion->nombre,
                ]);
            }
        }
    }

    /**
     * Handle the Reserva "created" event.
     */
    public function created(Reserva $reserva): void
    {
        // Cuando se crea una reserva confirmada, podemos marcar las habitaciones
        if ($reserva->id_estado_res == EstadoReserva::ESTADO_CONFIRMADA) {
            $this->marcarHabitacionesConfirmadas($reserva);
        }
    }

    /**
     * Handle the Reserva "deleted" event.
     */
    public function deleted(Reserva $reserva): void
    {
        // Si se elimina una reserva, liberar las habitaciones
        $this->liberarHabitaciones($reserva);
    }
}