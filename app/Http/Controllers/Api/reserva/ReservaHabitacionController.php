<?php
namespace App\Http\Controllers\Api\reserva;

use App\Http\Controllers\Controller;
use App\Http\Requests\reserva\AddReservaHabitacionRequest;
use App\Models\reserva\Reserva;
use App\Models\reserva\ReservaHabitacion;
use App\Models\habitacion\Habitacione;
use App\Models\reserva\ReservaServicio;
use App\Models\house_keeping\HabBloqueoOperativo;
use App\Models\check_in\AsignacionHabitacion;

class ReservaHabitacionController extends Controller
{
    public function index(Reserva $reserva) {
        return $reserva->habitaciones()->with('habitacion')->get();
    }

    public function store(AddReservaHabitacionRequest $r, Reserva $reserva) {
        $data = $r->validated();

        // Validación de traslape (reservas/estadías/bloqueos):
        $desde = $data['fecha_llegada'];
        $hasta = $data['fecha_salida'];

        $choqueReserva = ReservaHabitacion::where('id_habitacion',$data['id_habitacion'])
            ->where('fecha_llegada','<',$hasta)
            ->where('fecha_salida','>',$desde)
            ->exists();

        $choqueAsign = AsignacionHabitacion::where('id_hab',$data['id_habitacion'])
            ->where('fecha_asignacion','<',$hasta) // si guardas checkout, filtra asignaciones activas
            ->exists();

        $choqueBloqueo = HabBloqueoOperativo::where('id_habitacion',$data['id_habitacion'])
            ->where('fecha_ini','<',$hasta)
            ->where('fecha_fin','>',$desde)
            ->exists();

        if ($choqueReserva || $choqueAsign || $choqueBloqueo) {
            return response()->json(['message'=>'La habitación no está disponible en el rango.'], 422);
        }

        $row = $reserva->habitaciones()->create($data);
        return response()->json($row->fresh('habitacion'), 201);
    }

    public function update(AddReservaHabitacionRequest $r, Reserva $reserva, $habitacion_id)
    {
        $row = $reserva->habitaciones()->where('id_reserva_hab', $habitacion_id)->firstOrFail();
        $data = $r->validated();

        // Validar disponibilidad de la nueva habitación en el nuevo rango de fechas
        // Excluir ESTA MISMA reserva-habitación (el registro que estamos editando)
        $desde = $data['fecha_llegada'];
        $hasta = $data['fecha_salida'];

        // Verificar choques con OTRAS reservas
        // Solo debemos excluir el registro actual (id_reserva_hab), no toda la reserva
        $choqueReserva = ReservaHabitacion::where('id_habitacion', $data['id_habitacion'])
            ->where('id_reserva_hab', '!=', $habitacion_id)  // ← Excluir SOLO este registro
            ->where(function($q) use ($desde, $hasta) {
                $q->where(function($query) use ($desde, $hasta) {
                    // Traslape: la reserva existente empieza antes de que termine la nueva
                    $query->where('fecha_llegada', '<', $hasta)
                          ->where('fecha_salida', '>', $desde);
                });
            })
            ->exists();

        // Verificar choques con bloqueos operativos
        $choqueBloqueo = HabBloqueoOperativo::where('id_habitacion', $data['id_habitacion'])
            ->where('fecha_ini', '<', $hasta)
            ->where('fecha_fin', '>', $desde)
            ->exists();

        // NOTA: No verificamos asignaciones porque no tenemos fecha de fin
        // Las asignaciones deberían gestionarse en el módulo de check-in/checkout
        // Si una habitación tiene un huésped activo, no debería poder reservarse nuevamente

        if ($choqueReserva || $choqueBloqueo) {
            return response()->json([
                'message' => 'La habitación no está disponible en el rango.',
                'debug' => [
                    'choque_reserva' => $choqueReserva,
                    'choque_bloqueo' => $choqueBloqueo,
                    'habitacion_solicitada' => $data['id_habitacion'],
                    'rango_fechas' => ['desde' => $desde, 'hasta' => $hasta],
                ]
            ], 422);
        }

        // Recalcular subtotal si cambió la habitación o las fechas
        $row->update($data);
        $row->load('habitacion');
        $subtotal = $row->calcularSubtotal();
        $row->update(['subtotal' => $subtotal]);

        // Recalcular el total de la reserva
        $totalReserva = $reserva->habitaciones()->sum('subtotal');
        $reserva->update(['total_monto_reserva' => $totalReserva]);

        return response()->json($row->fresh('habitacion'));
    }

    public function destroy(Reserva $reserva, $habitacion_id)
    {
        $row = $reserva->habitaciones()->where('id_reserva_hab', $habitacion_id)->firstOrFail();
        $row->delete();
        return response()->noContent();
    }
}
