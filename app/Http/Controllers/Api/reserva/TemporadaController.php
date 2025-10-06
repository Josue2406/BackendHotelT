<?php

namespace App\Http\Controllers\Api\reserva;

use App\Http\Controllers\Controller;
use App\Models\reserva\Temporada;
use Illuminate\Http\Request;

class TemporadaController extends Controller
{
    // GET /api/temporadas
    public function index()
    {
        return response()->json(Temporada::all());
    }

    // GET /api/temporadas/{id}
    public function show($id)
    {
        $temporada = Temporada::findOrFail($id);
        return response()->json($temporada);
    }

    // POST /api/temporadas
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'     => 'required|string|max:100',
            'fecha_ini'  => 'required|date',
            'fecha_fin'  => 'required|date|after:fecha_ini',
            'prioridad'  => 'nullable|integer|min:1|max:10',
            'activo'     => 'boolean'
        ]);

        $temporada = Temporada::create($data);
        return response()->json($temporada, 201);
    }

    // PUT/PATCH /api/temporadas/{id}
    public function update(Request $request, $id)
    {
        $temporada = Temporada::findOrFail($id);

        $data = $request->validate([
            'nombre'     => 'sometimes|string|max:100',
            'fecha_ini'  => 'sometimes|date',
            'fecha_fin'  => 'sometimes|date|after:fecha_ini',
            'prioridad'  => 'sometimes|integer|min:1|max:10',
            'activo'     => 'sometimes|boolean'
        ]);

        $temporada->update($data);
        return response()->json($temporada);
    }

    // DELETE /api/temporadas/{id}
    public function destroy($id)
    {
        $temporada = Temporada::findOrFail($id);
        $temporada->delete();

        return response()->json(null, 204);
    }
}
