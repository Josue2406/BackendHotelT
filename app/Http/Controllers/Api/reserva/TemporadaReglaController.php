<?php

namespace App\Http\Controllers\Api\reserva;

use App\Http\Controllers\Controller;
use App\Models\reserva\TemporadaRegla;
use Illuminate\Http\Request;

class TemporadaReglaController extends Controller
{
    // GET /api/temporada-reglas
    public function index()
    {
        return response()->json(
            TemporadaRegla::with('temporada')->get()
        );
    }

    // GET /api/temporada-reglas/{id}
    public function show($id)
    {
        $regla = TemporadaRegla::with('temporada')->findOrFail($id);
        return response()->json($regla);
    }

    // POST /api/temporada-reglas
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_temporada'      => 'required|exists:temporadas,id_temporada',
            'scope'             => 'required|in:HOTEL,TIPO,HABITACION',
            'tipo_habitacion_id'=> 'nullable|exists:tipos_habitacion,id_tipo_hab',
            'habitacion_id'     => 'nullable|exists:habitaciones,id_habitacion',
            'tipo_ajuste'       => 'required|in:PORCENTAJE,MONTO',
            'valor'             => 'required|numeric',
            'prioridad'         => 'nullable|integer|min:1|max:10',
            'aplica_dow'        => 'nullable|string', // Ej: "1,2,3" (lunes, martes, miércoles)
            'min_noches'        => 'nullable|integer|min:1'
        ]);

        $regla = TemporadaRegla::create($data);
        return response()->json($regla, 201);
    }

    // PUT/PATCH /api/temporada-reglas/{id}
    public function update(Request $request, $id)
    {
        $regla = TemporadaRegla::findOrFail($id);

        $data = $request->validate([
            'scope'             => 'sometimes|in:HOTEL,TIPO,HABITACION',
            'tipo_habitacion_id'=> 'nullable|exists:tipos_habitacion,id_tipo_hab',
            'habitacion_id'     => 'nullable|exists:habitaciones,id_habitacion',
            'tipo_ajuste'       => 'sometimes|in:PORCENTAJE,MONTO',
            'valor'             => 'sometimes|numeric',
            'prioridad'         => 'sometimes|integer|min:1|max:10',
            'aplica_dow'        => 'nullable|string',
            'min_noches'        => 'nullable|integer|min:1'
        ]);

        $regla->update($data);
        return response()->json($regla);
    }

    // DELETE /api/temporada-reglas/{id}
    public function destroy($id)
    {
        $regla = TemporadaRegla::findOrFail($id);
        $regla->delete();

        return response()->json(null, 204);
    }
}
