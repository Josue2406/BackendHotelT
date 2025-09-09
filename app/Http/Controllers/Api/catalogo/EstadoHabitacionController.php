<?php
namespace App\Http\Controllers\Api\catalogo;

use App\Http\Controllers\Controller;
use App\Http\Requests\habitaciones\StoreEstadoHabitacionRequest;
use App\Http\Requests\habitaciones\UpdateEstadoHabitacionRequest;
use App\Models\habitacion\EstadoHabitacion;

class EstadoHabitacionController extends Controller
{
    public function index() { return EstadoHabitacion::orderByDesc('id_estado_hab')->paginate(20); }
    public function show(EstadoHabitacion $estados_habitacion) { return $estados_habitacion; }

    public function store(StoreEstadoHabitacionRequest $r) {
        return response()->json(EstadoHabitacion::create($r->validated()), 201);
    }
    public function update(UpdateEstadoHabitacionRequest $r, EstadoHabitacion $estados_habitacion) {
        $estados_habitacion->update($r->validated());
        return $estados_habitacion->fresh();
    }
    public function destroy(EstadoHabitacion $estados_habitacion) {
        $estados_habitacion->delete();
        return response()->noContent();
    }
}
