<?php

namespace App\Http\Controllers\Api;

use App\Models\TipoHabitacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTipoHabitacionRequest;
use App\Http\Requests\UpdateTipoHabitacionRequest;
use App\Http\Resources\TipoHabitacionResource;
use Illuminate\Http\Request;

class TipoHabitacionController extends Controller
{
    public function index(Request $request)
    {
        $q = TipoHabitacion::query();

        if ($search = $request->get('q')) {
            $q->where(function ($x) use ($search) {
                $x->where('nombre', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%");
            });
        }

        return TipoHabitacionResource::collection(
            $q->orderBy('nombre')->paginate($request->get('per_page', 15))
        );
    }

    public function store(StoreTipoHabitacionRequest $request)
    {
        $tipo = TipoHabitacion::create($request->validated());
        return new TipoHabitacionResource($tipo);
    }

    public function show(TipoHabitacion $tipo_habitacion)
    {
        return new TipoHabitacionResource($tipo_habitacion);
    }

    public function update(UpdateTipoHabitacionRequest $request, TipoHabitacion $tipo_habitacion)
    {
        $tipo_habitacion->update($request->validated());
        return new TipoHabitacionResource($tipo_habitacion);
    }

    public function destroy(TipoHabitacion $tipo_habitacion)
    {
        // Evita borrar si tiene habitaciones
        if ($tipo_habitacion->habitaciones()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar: existen habitaciones asociadas.'
            ], 422);
        }

        $tipo_habitacion->delete();
        return response()->noContent();
    }
}
