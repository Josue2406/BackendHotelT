<?php

namespace App\Http\Controllers\Api\house_keeping;

use App\Events\NuevoMantenimientoAsignado;
use App\Http\Controllers\Controller;
use App\Http\Requests\house_keeping\StoreMantenimientoRequest;
use App\Http\Requests\house_keeping\UpdateMantenimientoRequest;
use App\Http\Resources\house_keeping\MantenimientoResource;
use App\Models\house_keeping\Mantenimiento;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\house_keeping\MantenimientoService;
class MantenimientoController extends Controller
{

    protected MantenimientoService $service;

public function __construct(MantenimientoService $service)
{
    $this->service = $service;
}
    /** GET /mantenimientos */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);

        $query = Mantenimiento::with([
            'habitacion.tipo',   // 👈 importante
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
    // 1) Validar entrada
    $data = $request->validated();

    // 2) Forzar usuario que reporta y fecha de reporte desde backend
    $reporterId = optional(auth()->user())->id_usuario ?? auth()->id();
    $data['id_usuario_reporta'] = $reporterId;
    $data['fecha_reporte']      = Carbon::now();

    // 3) Crear y registrar historial
    $mtto = Mantenimiento::create($data);
    $this->service->registrarCreacion($mtto);

    // 4) Cargar relaciones ANTES del payload del evento (incluye tipo de habitación)
    $mtto->load(['habitacion.tipo','asignador','reportante','estadoHabitacion']);

    // 5) Emitir evento de broadcast
    event(new NuevoMantenimientoAsignado([
        'id'         => $mtto->id_mantenimiento ?? $mtto->id ?? null,
        'habitacion' => $mtto->habitacion->numero ?? 'N/A',
        'asignado_a' => optional($mtto->asignador)->nombre ?? 'Sin asignar',
        'estado'     => optional($mtto->estadoHabitacion)->nombre ?? 'Desconocido',
        'fecha'      => $mtto->fecha_inicio ?? now()->toDateTimeString(),
        'prioridad'  => $mtto->prioridad ?? null,
    ]));

    // 6) Respuesta API
    return (new MantenimientoResource($mtto))
        ->response()->setStatusCode(201);
}

    /** GET /mantenimientos/{mantenimiento} */
    public function show(Mantenimiento $mantenimiento)
    {
        $mantenimiento->load([
            'habitacion.tipo',   // 👈
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

    // Campos que pueden limpiarse si no vienen en request
    $nullableCampos = [
        'fecha_final',
        'notas',
        'prioridad',
    ];

    foreach ($nullableCampos as $campo) {
        if (!array_key_exists($campo, $data)) {
            $data[$campo] = null;
        }
    }

    // 🔒 Siempre setear quién y cuándo reporta al actualizar
    $data['id_usuario_reporta'] = optional(auth()->user())->id_usuario ?? auth()->id();
    $data['fecha_reporte'] = Carbon::now();

    // Hacer update
    $this->service->registrarActualizacion($mantenimiento, $data);
    $mantenimiento->update($data);

    // Recargar relaciones
    $mantenimiento->refresh()->load([
        'habitacion.tipo',
        'asignador',        // quien recibe el mantenimiento
        'reportante',       // quien lo reporta (última actualización)
        'estadoHabitacion',
    ]);

    // 🔍 DEBUG: Ver qué llega en el request
    \Log::info('🔍 UPDATE Mantenimiento - Request Data', [
        'mantenimiento_id' => $mantenimiento->id_mantenimiento ?? $mantenimiento->id,
        'request_has_id_usuario' => $request->has('id_usuario_asigna'),
        'request_id_usuario' => $request->input('id_usuario_asigna'),
        'mantenimiento_id_usuario_after_update' => $mantenimiento->id_usuario_asigna,
        'request_all' => $request->all(),
    ]);

    // Evento si se incluye asignación en el request
    if ($request->has('id_usuario_asigna') && $mantenimiento->id_usuario_asigna !== null) {
        \Log::info('✅ DISPARANDO EVENTO NuevoMantenimientoAsignado', [
            'habitacion' => $mantenimiento->habitacion->numero ?? 'N/A',
            'asignado_a' => optional($mantenimiento->asignador)->nombre ?? 'Sin asignar',
        ]);

        event(new NuevoMantenimientoAsignado([
            'id'         => $mantenimiento->id_mantenimiento ?? $mantenimiento->id,
            'habitacion' => $mantenimiento->habitacion->numero ?? 'N/A',
            'asignado_a' => optional($mantenimiento->asignador)->nombre ?? 'Sin asignar',
            'estado'     => optional($mantenimiento->estadoHabitacion)->nombre ?? 'Desconocido',
            'fecha'      => $mantenimiento->fecha_inicio ?? now()->toDateTimeString(),
            'prioridad'  => $mantenimiento->prioridad,
        ]));
    } else {
        \Log::info('❌ NO se cumple condición para disparar evento', [
            'request_has' => $request->has('id_usuario_asigna'),
            'id_usuario_value' => $mantenimiento->id_usuario_asigna,
        ]);
    }

    return new MantenimientoResource($mantenimiento);
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