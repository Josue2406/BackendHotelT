<?php

namespace App\Http\Controllers\Api\house_keeping;

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
    // 1) Validar entrada
    $data = $request->validated();

    // 2) Forzar usuario que reporta y fecha de reporte desde backend
    $reporterId = optional(auth()->user())->id_usuario ?? auth()->id();
    $data['id_usuario_reporta'] = $reporterId;
    $data['fecha_reporte']      = Carbon::now();

    // 3) Crear y registrar historial (tu servicio actual)
    $mtto = Mantenimiento::create($data);
    $this->service->registrarCreacion($mtto);

    // 4) Cargar relaciones ANTES del payload del evento
    $mtto->load(['habitacion','asignador','reportante','estadoHabitacion']);

    // 5) Emitir evento de broadcast (usa broadcast() si quieres excluir al emisor actual)
    // event(new NuevoMantenimientoAsignado([
    //     'id'         => $mtto->id_mantenimiento ?? $mtto->id ?? null,
    //     'habitacion' => $mtto->habitacion->numero ?? 'N/A',
    //     'asignado_a' => optional($mtto->asignador)->nombre ?? 'Sin asignar',
    //     'estado'     => optional($mtto->estadoHabitacion)->nombre ?? 'Desconocido',
    //     'fecha'      => $mtto->fecha_inicio ?? now()->toDateTimeString(),
    //     'prioridad'  => $mtto->prioridad ?? null,
    // ]));

    // 6) Respuesta API
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

    // Campos que pueden ser "limpiados" si no vienen en el request
    $nullableCampos = [
        //'fecha_inicio',
        'fecha_final',
        'notas',
        'prioridad',
        //'id_usuario_asigna',
        //'id_estado_hab',
    ];

    foreach ($nullableCampos as $campo) {
        if (!array_key_exists($campo, $data)) {
            $data[$campo] = null;
        }
    }

    //  Siempre sobreescribir usuario que reporta y fecha de reporte
    $data['id_usuario_reporta'] = optional(auth()->user())->id_usuario ?? auth()->id();
    $data['fecha_reporte'] = Carbon::now();
    // Verificar si hubo cambio en la asignación
    $asignacionAnterior = $mantenimiento->id_usuario_asigna ?? null;
    $asignacionNueva = $data['id_usuario_asigna'] ?? $asignacionAnterior;

    // Actualizar y registrar historial
    $mantenimiento->update($data);
    $this->service->registrarActualizacion($mantenimiento, $data);

    // Respuesta con relaciones actualizadas
    return new MantenimientoResource(
        $mantenimiento->fresh()->load([
            'habitacion',
            'asignador',
            'reportante',
            'estadoHabitacion',
        ])
    );
    if ($asignacionAnterior !== $asignacionNueva && $asignacionNueva !== null) {
        event(new NuevoMantenimientoAsignado([
            'id'         => $mantenimiento->id_mantenimiento ?? $mantenimiento->id,
            'habitacion' => $mantenimiento->habitacion->numero ?? 'N/A',
            'asignado_a' => optional($mantenimiento->asignador)->nombre ?? 'Sin asignar',
            'estado'     => optional($mantenimiento->estadoHabitacion)->nombre ?? 'Desconocido',
            'fecha'      => $mantenimiento->fecha_inicio ?? now()->toDateTimeString(),
            'prioridad'  => $mantenimiento->prioridad ?? null,
        ]));
    }
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
