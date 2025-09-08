<?php

namespace App\Http\Controllers\Api\house_keeping;

use App\Http\Controllers\Controller;
use App\Http\Requests\house_keeping\StoreLimpiezaRequest;
use App\Http\Requests\house_keeping\UpdateLimpiezaRequest;
use App\Http\Resources\house_keeping\LimpiezaResource;
use App\Models\house_keeping\Limpieza;
use Illuminate\Http\Request;

class LimpiezaController extends Controller
{
    /** GET /limpiezas */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);

        $query = Limpieza::with([
            // Relaciones según tu modelo (nombres tal cual)
            'id_habitacion',
            'id_usuario_asigna',
            'id_usuario_reporta',
            'historial_limpiezas_where_id_limpieza',
        ])->orderByDesc('fecha_inicio');

        // Filtros opcionales
        if ($request->filled('prioridad')) {
            $query->where('prioridad', $request->input('prioridad'));
        }
        if ($request->boolean('pendientes', false)) {
            $query->whereNull('fecha_final');
        }
        if ($request->filled('id_habitacion')) {
            $query->where('id_habitacion', (int) $request->input('id_habitacion'));
        }

        return LimpiezaResource::collection($query->paginate($perPage));
    }

    /** POST /limpiezas */
    public function store(StoreLimpiezaRequest $request)
    {
        $data = $request->validated();

        // Por defecto, quien reporta puede ser el autenticado (opcional)
        if (!isset($data['id_usuario_reporta']) && auth()->check()) {
            $data['id_usuario_reporta'] = auth()->id();
        }

        $limpieza = Limpieza::create($data);

        return (new LimpiezaResource(
            $limpieza->load(['id_habitacion','id_usuario_asigna','id_usuario_reporta'])
        ))->response()->setStatusCode(201);
    }

    /** GET /limpiezas/{limpieza} */
    public function show(Limpieza $limpieza)
    {
        $limpieza->load([
            'id_habitacion',
            'id_usuario_asigna',
            'id_usuario_reporta',
            'historial_limpiezas_where_id_limpieza',
        ]);

        return new LimpiezaResource($limpieza);
    }

    /** PUT/PATCH /limpiezas/{limpieza} */
    public function update(UpdateLimpiezaRequest $request, Limpieza $limpieza)
    {
        $limpieza->update($request->validated());

        return new LimpiezaResource(
            $limpieza->fresh()->load(['id_habitacion','id_usuario_asigna','id_usuario_reporta'])
        );
    }

    /** DELETE /limpiezas/{limpieza} */
    public function destroy(Limpieza $limpieza)
    {
        $limpieza->delete();
        return response()->noContent();
    }

    /** PATCH /limpiezas/{limpieza}/finalizar — solo fecha_final (y opcional notas) */
    public function finalizar(Request $request, Limpieza $limpieza)
    {
        $data = $request->validate([
            'fecha_final' => ['required','date','after_or_equal:'.$limpieza->fecha_inicio],
            'notas'       => ['nullable','string','max:500'],
        ]);

        $limpieza->update([
            'fecha_final' => $data['fecha_final'],
            'notas'       => $data['notas'] ?? $limpieza->notas,
        ]);

        return new LimpiezaResource($limpieza->fresh());
    }
}
