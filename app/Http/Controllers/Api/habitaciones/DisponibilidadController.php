<?php
namespace App\Http\Controllers\Api\habitaciones;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\habitacion\Habitacione;
use App\Models\reserva\ReservaHabitacion;
use App\Models\check_in\AsignacionHabitacion;
use App\Models\house_keeping\HabBloqueoOperativo;

class DisponibilidadController extends Controller
{
    public function __invoke(Request $req)
    {
        $data = $req->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after:desde',
            'tipo'  => 'nullable|integer|exists:tipos_habitacion,id_tipo_hab',
        ]);

        $desde = $data['desde'];
        $hasta = $data['hasta'];

        $base = Habitacione::query()
            ->when(isset($data['tipo']), fn($q) => $q->where('tipo_habitacion_id', $data['tipo']));

        $ocupadasReserva = ReservaHabitacion::select('id_habitacion')
            ->where('fecha_llegada', '<', $hasta)
            ->where('fecha_salida',  '>', $desde);

        $ocupadasAsign = AsignacionHabitacion::select('id_hab')
            ->where('fecha_asignacion', '<', $hasta); 

        $bloqueos = HabBloqueoOperativo::select('id_habitacion')
            ->where('fecha_ini', '<', $hasta)
            ->where('fecha_fin', '>', $desde);

        $disponibles = $base
            ->whereNotIn('id_habitacion', $ocupadasReserva)
            ->whereNotIn('id_habitacion', $ocupadasAsign)
            ->whereNotIn('id_habitacion', $bloqueos)
            ->get();

        return response()->json([
            'desde' => $desde,
            'hasta' => $hasta,
            'tipo'  => $data['tipo'] ?? null,
            'total_disponibles' => $disponibles->count(),
            'habitaciones'      => $disponibles,
        ]);
    }
}