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

    public function update(AddReservaHabitacionRequest $r, Reserva $reserva, $id)
    {
        $row = $reserva->habitaciones()->where('id_reserva_hab', $id)->firstOrFail();
        $data = $r->validated();

        // Validar disponibilidad (excluyendo esta misma reserva)
        $desde = $data['fecha_llegada'];
        $hasta = $data['fecha_salida'];

        $choqueReserva = ReservaHabitacion::where('id_habitacion', $data['id_habitacion'])
            ->where('id_reserva_hab', '!=', $id)  // ← Excluir esta misma
            ->where('fecha_llegada', '<', $hasta)
            ->where('fecha_salida', '>', $desde)
            ->exists();

        $choqueAsign = AsignacionHabitacion::where('id_hab', $data['id_habitacion'])
            ->where('fecha_asignacion', '<', $hasta)
            ->exists();

        $choqueBloqueo = HabBloqueoOperativo::where('id_habitacion', $data['id_habitacion'])
            ->where('fecha_ini', '<', $hasta)
            ->where('fecha_fin', '>', $desde)
            ->exists();

        if ($choqueReserva || $choqueAsign || $choqueBloqueo) {
            return response()->json(['message' => 'La habitación no está disponible en el rango.'], 422);
        }

        $row->update($data);
        return response()->json($row->fresh('habitacion'));
    }

    public function destroy(Reserva $reserva, $id)
    {
        $row = $reserva->habitaciones()->where('id_reserva_hab',$id)->firstOrFail();
        $row->delete();
        return response()->noContent();
    }
}
