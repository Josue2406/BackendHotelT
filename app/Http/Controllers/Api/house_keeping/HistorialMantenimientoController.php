<?php

namespace App\Http\Controllers\Api\house_keeping;

use App\Http\Controllers\Controller;
use App\Http\Resources\house_keeping\HistorialMantenimientoResource;
use App\Models\house_keeping\HistorialMantenimiento;
use Illuminate\Http\Request;

class HistorialMantenimientoController extends Controller
{
    /**
     * GET /api/historial-mantenimientos
     * Lista todos los historiales (paginado)
     */
    public function index(Request $request)
    {
        $perPage = (int) ($request->query('per_page', 15));

        $historiales = HistorialMantenimiento::with(['actor', 'mantenimiento'])
            ->orderByDesc('fecha')
            ->paginate($perPage);

        return HistorialMantenimientoResource::collection($historiales);
    }

    /**
     * GET /api/historial-mantenimientos/{id}
     * Ver un historial específico
     */
    public function show($id)
    {
        $historial = HistorialMantenimiento::with(['actor', 'mantenimiento'])
            ->findOrFail($id);

        return new HistorialMantenimientoResource($historial);
    }

    /**
     * GET /api/mantenimientos/{id}/historial
     * Lista el historial de un mantenimiento específico
     */
    public function porMantenimiento($idMantenimiento)
    {
        $historiales = HistorialMantenimiento::with(['actor', 'mantenimiento'])
            ->where('id_mantenimiento', $idMantenimiento)
            ->orderByDesc('fecha')
            ->get();

        return HistorialMantenimientoResource::collection($historiales);
    }
}
