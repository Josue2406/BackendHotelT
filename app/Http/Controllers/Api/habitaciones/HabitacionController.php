<?php
namespace App\Http\Controllers\Api\habitaciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\habitaciones\StoreHabitacionRequest;
use App\Http\Requests\habitaciones\UpdateHabitacionRequest;
use App\Models\habitacion\Habitacione;

class HabitacionController extends Controller
{
    public function index() { return Habitacione::with(['estado','tipo'])->orderBy('numero')->paginate(20); }
    public function show(Habitacione $habitacione) { return $habitacione->load(['estado','tipo']); }

    public function store(StoreHabitacionRequest $r) {
        return response()->json(Habitacione::create($r->validated()), 201);
    }

    public function update(UpdateHabitacionRequest $r, Habitacione $habitacione) {
        $habitacione->update($r->validated());
        return $habitacione->fresh();
    }
}
