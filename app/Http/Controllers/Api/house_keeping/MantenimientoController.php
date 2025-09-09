<?php

namespace App\Http\Controllers\Api\house_keeping;

use App\Http\Controllers\Controller;
use App\Http\Requests\house_keeping\StoreMantenimientoRequest;
use App\Http\Requests\house_keeping\UpdateMantenimientoRequest;
use App\Http\Resources\house_keeping\MantenimientoResource;

use App\Models\house_keeping\Mantenimiento;

use Illuminate\Http\Request;

class MantenimientoController extends Controller
{
    /** GET /mantenimientos */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);

        $query = Mantenimiento::with([
            'id_habitacion',
            'id_usuario_asigna',
            'id_usuario_reporta',
            'historial_mantenimientos_where_id_mantenimiento',
        ])->orderByDesc('fecha_inicio');

        if ($request->filled('prioridad')) {
            $query->where('prioridad', $request->input('prioridad'));
        }
        if ($request->boolean('pendientes', false)) {
            $query->whereNull('fecha_final');
        }
        if ($request->filled('id_habitacion')) {
            $query->where('id_habitacion', (int) $request->input('id_habitacion'));
        }

        return MantenimientoResource::collection($query->paginate($perPage));
    }

    /** POST /mantenimientos */
    public function store(StoreMantenimientoRequest $request)
{
    $data = $request->validated();

    // Asignar automáticamente quien reporta si no se especifica
    if (!isset($data['id_usuario_reporta']) && auth()->check()) {
        $data['id_usuario_reporta'] = auth()->id();
    }

    // Asignar la fecha de reporte con la hora actual (zona horaria definida en .env)
    $data['fecha_reporte'] = \Carbon\Carbon::now();

    $mtto = Mantenimiento::create($data);

    return (new MantenimientoResource(
        $mtto->load(['id_habitacion', 'id_usuario_asigna', 'id_usuario_reporta'])
    ))->response()->setStatusCode(201);
}

    /** GET /mantenimientos/{mantenimiento} */
    public function show(Mantenimiento $mantenimiento)
    {
        $mantenimiento->load([
            'id_habitacion',
            'id_usuario_asigna',
            'id_usuario_reporta',
            'historial_mantenimientos_where_id_mantenimiento',
        ]);

        return new MantenimientoResource($mantenimiento);
    }

    /** PUT/PATCH /mantenimientos/{mantenimiento} */
    public function update(UpdateMantenimientoRequest $request, Mantenimiento $mantenimiento)
    {
        $mantenimiento->update($request->validated());

        return new MantenimientoResource(
            $mantenimiento->fresh()->load(['id_habitacion','id_usuario_asigna','id_usuario_reporta'])
        );
    }

    /** DELETE /mantenimientos/{mantenimiento} */
    public function destroy(Mantenimiento $mantenimiento)
    {
        $mantenimiento->delete();
        return response()->noContent();
    }

    /**
     * PATCH /mantenimientos/{mantenimiento}/finalizar
     * Actualiza SOLO fecha_final (y opcionalmente notas).
     */
    public function finalizar(Request $request, Mantenimiento $mantenimiento)
    {
        $data = $request->validate([
            'fecha_final' => ['required','date','after_or_equal:'.$mantenimiento->fecha_inicio],
            'notas'       => ['nullable','string','max:500'],
        ]);

        $mantenimiento->update([
            'fecha_final' => $data['fecha_final'],
            'notas'       => $data['notas'] ?? $mantenimiento->notas,
        ]);

        return new MantenimientoResource($mantenimiento->fresh());
    }
}
