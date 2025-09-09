<?php
namespace App\Http\Controllers\Api\catalogo;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTipoHabitacionRequest;
use App\Http\Requests\UpdateTipoHabitacionRequest;
use App\Models\TiposHabitacion;

class TipoHabitacionController extends Controller
{
    public function index() { return TiposHabitacion::orderByDesc('id_tipo_hab')->paginate(20); }
    public function show(TiposHabitacion $tipos_habitacion) { return $tipos_habitacion; }

    public function store(StoreTipoHabitacionRequest $r) {
        return response()->json(TiposHabitacion::create($r->validated()), 201);
    }
    public function update(UpdateTipoHabitacionRequest $r, TiposHabitacion $tipos_habitacion) {
        $tipos_habitacion->update($r->validated());
        return $tipos_habitacion->fresh();
    }
    public function destroy(TiposHabitacion $tipos_habitacion) {
        $tipos_habitacion->delete();
        return response()->noContent();
    }
}
