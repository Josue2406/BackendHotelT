<?php
namespace App\Http\Controllers\Api;

use App\Models\Habitacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHabitacionRequest;
use App\Http\Requests\UpdateHabitacionRequest;
use App\Http\Resources\HabitacionResource;
use Illuminate\Http\Request;

class HabitacionController extends Controller
{
    public function index(Request $request)
    {
        $q = Habitacion::with('tipo');

        if ($estado = $request->get('estado')) {
            $q->where('estado', $estado);
        }
        if ($tipoId = $request->get('tipo')) {
            $q->where('tipo_habitacion_id', $tipoId);
        }
        if ($search = $request->get('q')) {
            $q->where('numero', 'like', "%{$search}%");
        }

        return HabitacionResource::collection(
            $q->orderBy('numero')->paginate($request->get('per_page', 15))
        );
    }

    public function store(StoreHabitacionRequest $request)
    {
        $habitacion = Habitacion::create($request->validated());
        $habitacion->load('tipo');
        return new HabitacionResource($habitacion);
    }

    public function show(Habitacion $habitacion)
    {
        $habitacion->load('tipo');
        return new HabitacionResource($habitacion);
    }

    public function update(UpdateHabitacionRequest $request, Habitacion $habitacion)
    {
        $habitacion->update($request->validated());
        $habitacion->load('tipo');
        return new HabitacionResource($habitacion);
    }

    public function destroy(Habitacion $habitacion)
    {
        // Regla de negocio: solo eliminar si no está ocupada
        if ($habitacion->estado === 'ocupada') {
            return response()->json([
                'message' => 'No se puede eliminar una habitación ocupada.'
            ], 422);
        }

        $habitacion->delete();
        return response()->noContent();
    }
}
