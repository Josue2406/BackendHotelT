<?php

namespace App\Http\Controllers\Api\house_keeping;

use App\Http\Controllers\Controller;
use App\Http\Requests\house_keeping\StoreMantenimientoRequest;
use App\Http\Requests\house_keeping\UpdateMantenimientoRequest;
use App\Http\Resources\house_keeping\MantenimientoResource;
use App\Models\house_keeping\Mantenimiento;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MantenimientoController extends Controller
{
    /** GET /mantenimientos */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);

        $query = Mantenimiento::with([
            'habitacion',
            'asignador',
            'reportante',
            'estadoHabitacion',
            'historialMantenimientos',
        ])->orderByDesc('fecha_inicio');

        // --- Filtros opcionales ---
        if ($request->filled('prioridad')) {
            $query->where('prioridad', $request->input('prioridad'));
        }
        if ($request->boolean('pendientes', false)) {
            $query->whereNull('fecha_final');
        }
        if ($request->filled('id_habitacion')) {
            $query->where('id_habitacion', (int) $request->input('id_habitacion'));
        }
        if ($request->filled('estado_id')) {
            $query->where('id_estado_hab', (int) $request->input('estado_id'));
        }
        if ($request->filled('desde')) {
            $query->whereDate('fecha_inicio', '>=', $request->input('desde'));
        }
        if ($request->filled('hasta')) {
            $query->whereDate('fecha_inicio', '<=', $request->input('hasta'));
        }

        return MantenimientoResource::collection($query->paginate($perPage));
    }

    /** POST /mantenimientos */
    public function store(StoreMantenimientoRequest $request)
    {
        // Solo datos validados (no permitimos que el cliente mande fecha_reporte ni id_usuario_reporta)
        $data = $request->validated();

        // Forzar usuario que reporta y fecha de reporte desde backend
        $reporterId = optional(auth()->user())->id_usuario ?? auth()->id(); // ajusta según tu esquema
        $data['id_usuario_reporta'] = $reporterId;
        $data['fecha_reporte']      = Carbon::now();

        $mtto = Mantenimiento::create($data);

        return (new MantenimientoResource(
            $mtto->load(['habitacion','asignador','reportante','estadoHabitacion'])
        ))->response()->setStatusCode(201);
    }

    /** GET /mantenimientos/{mantenimiento} */
    public function show(Mantenimiento $mantenimiento)
    {
        $mantenimiento->load([
            'habitacion',
            'asignador',
            'reportante',
            'estadoHabitacion',
            'historialMantenimientos',
        ]);

        return new MantenimientoResource($mantenimiento);
    }

    /** PUT/PATCH /mantenimientos/{mantenimiento} */
    public function update(UpdateMantenimientoRequest $request, Mantenimiento $mantenimiento)
    {
        $data = $request->validated();

        // Blindaje: NO permitir editar fecha_reporte ni id_usuario_reporta
        unset($data['fecha_reporte'], $data['id_usuario_reporta']);

        $mantenimiento->update($data);

        return new MantenimientoResource(
            $mantenimiento->fresh()->load(['habitacion','asignador','reportante','estadoHabitacion'])
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
     * Finaliza un mantenimiento estableciendo fecha_final (y opcionalmente notas).
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

        return new MantenimientoResource(
            $mantenimiento->fresh()->load(['estadoHabitacion'])
        );
    }
}
