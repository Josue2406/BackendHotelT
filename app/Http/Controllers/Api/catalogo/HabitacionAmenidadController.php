<?php
namespace App\Http\Controllers\Api\catalogo;

use App\Http\Controllers\Controller;
use App\Http\Requests\catalogo\StoreHabitacionAmenidadRequest;
use App\Models\habitacion\catalogo\HabitacionAmenidad;

class HabitacionAmenidadController extends Controller
{
    public function index() {
        return HabitacionAmenidad::with(['habitacion','amenidad'])
            ->orderByDesc('id_amenidad_habitacion')->paginate(20);
    }

    public function show(HabitacionAmenidad $habitacion_amenidad) {
        return $habitacion_amenidad->load(['habitacion','amenidad']);
    }

    public function store(StoreHabitacionAmenidadRequest $r) {
        return response()->json(HabitacionAmenidad::create($r->validated()), 201);
    }

    public function destroy(HabitacionAmenidad $habitacion_amenidad) {
        $habitacion_amenidad->delete();
        return response()->noContent();
    }
}
