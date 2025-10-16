<?php

namespace App\Http\Controllers\Api\reserva;

use App\Http\Controllers\Controller;
use App\Http\Requests\reserva\StoreServicioRequest;
use App\Http\Requests\reserva\UpdateServicioRequest;
use App\Models\reserva\Servicio;
use Illuminate\Http\Request;

class ServicioController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/servicios
     * Supports: ?search=texto
     */
    public function index(Request $request)
    {
        $query = Servicio::query();

        // Filtro de búsqueda
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('nombre')->paginate(20);
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/servicios
     */
    public function store(StoreServicioRequest $request)
    {
        $servicio = Servicio::create($request->validated());
        return response()->json($servicio, 201);
    }

    /**
     * Display the specified resource.
     * GET /api/servicios/{id}
     */
    public function show(Servicio $servicio)
    {
        return response()->json($servicio->load('reservas'));
    }

    /**
     * Update the specified resource in storage.
     * PUT/PATCH /api/servicios/{id}
     */
    public function update(UpdateServicioRequest $request, Servicio $servicio)
    {
        $servicio->update($request->validated());
        return response()->json($servicio);
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/servicios/{id}
     */
    public function destroy(Servicio $servicio)
    {
        // Verificar si el servicio está siendo usado en reservas
        if ($servicio->reservas()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar el servicio porque está asociado a una o más reservas.'
            ], 422);
        }

        $servicio->delete();
        return response()->noContent();
    }
}