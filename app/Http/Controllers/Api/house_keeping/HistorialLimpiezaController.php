<?php

namespace App\Http\Controllers\Api\house_keeping;

use App\Http\Controllers\Controller;
use App\Http\Resources\house_keeping\HistorialLimpiezaResource;
use App\Models\house_keeping\HistorialLimpieza;
use Illuminate\Http\Request;

class HistorialLimpiezaController extends Controller
{
    /**
     * GET /api/historial-limpiezas
     * Lista de historiales (paginado)
     */
    public function index(Request $request)
    {
        $perPage = (int) ($request->query('per_page', 15));

        $historiales = HistorialLimpieza::with(['actor', 'limpieza'])
            ->orderByDesc('fecha')
            ->paginate($perPage);

        return HistorialLimpiezaResource::collection($historiales);
    }

    /**
     * GET /api/historial-limpiezas/{id}
     * Ver un historial específico
     */
    public function show($id)
    {
        $historial = HistorialLimpieza::with(['actor', 'limpieza'])
            ->findOrFail($id);

        return new HistorialLimpiezaResource($historial);
    }

    /**
     * GET /api/limpiezas/{id}/historial
     * Lista el historial de una limpieza específica
     */
    public function porLimpieza($idLimpieza)
    {
        $historiales = HistorialLimpieza::with(['actor', 'limpieza'])
            ->where('id_limpieza', $idLimpieza)
            ->orderByDesc('fecha')
            ->get();

        return HistorialLimpiezaResource::collection($historiales);
    }
}
