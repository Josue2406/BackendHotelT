<?php

namespace App\Http\Controllers\Api\house_keeping;

use App\Events\NuevaLimpiezaAsignada;
use App\Http\Controllers\Controller;
use App\Http\Requests\house_keeping\StoreLimpiezaRequest;
use App\Http\Requests\house_keeping\UpdateLimpiezaRequest;
use App\Http\Resources\house_keeping\LimpiezaResource;
use App\Models\house_keeping\Limpieza;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\house_keeping\LimpiezaService;

class LimpiezaController extends Controller
{
    protected $limpiezaService;

    public function __construct(LimpiezaService $limpiezaService)
    {
        $this->limpiezaService = $limpiezaService;
    }

    /** GET /limpiezas */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);

        $query = Limpieza::with([
            'habitacion.tipo',   // 👈 importante
            'asignador',
            'reportante',
            'estadoHabitacion',
            'historialLimpiezas',
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

        return LimpiezaResource::collection($query->paginate($perPage));
    }

    /** POST /limpiezas */
    public function store(StoreLimpiezaRequest $request)
    {
        $data = $request->validated();

        $reporterId = optional(auth()->user())->id_usuario ?? auth()->id();
        $data['id_usuario_reporta'] = $reporterId;
        $data['fecha_reporte']      = Carbon::now();

        $limpieza = $this->limpiezaService->crearLimpieza($data);

        // Cargar relaciones (incluye tipo de habitación)
        $limpieza->load(['habitacion.tipo','asignador','estadoHabitacion','reportante']);

        event(new NuevaLimpiezaAsignada([
            'id'         => $limpieza->id_limpieza ?? $limpieza->id ?? null,
            'habitacion' => $limpieza->habitacion->numero ?? 'N/A',
            'asignado_a' => optional($limpieza->asignador)->nombre ?? 'Sin asignar',
            'estado'     => optional($limpieza->estadoHabitacion)->nombre ?? 'Desconocido',
            'fecha'      => $limpieza->fecha_inicio ?? now()->toDateTimeString(),
            'prioridad'  => $limpieza->prioridad,
        ]));

        return (new LimpiezaResource($limpieza))
            ->response()->setStatusCode(201);
    }

    /** GET /limpiezas/{limpieza} */
    public function show(Limpieza $limpieza)
    {
        $limpieza->load([
            'habitacion.tipo',   // 👈
            'asignador',
            'reportante',
            'estadoHabitacion',
            'historialLimpiezas',
        ]);

        return new LimpiezaResource($limpieza);
    }

    /** PUT/PATCH /limpiezas/{limpieza} */
    public function update(UpdateLimpiezaRequest $request, Limpieza $limpieza)
{
    $data = $request->validated();

    // Campos que pueden limpiarse si no vienen en request
    $nullableCampos = [
        //'fecha_inicio',
        'fecha_final',
        'notas',
        'prioridad',
       // 'id_usuario_asigna',
        //'id_estado_hab',
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
    $this->limpiezaService->actualizarLimpieza($limpieza, $data);

    // Recargar relaciones
    $limpieza->refresh()->load([
        'habitacion.tipo',
        'asignador',        // quien recibe la limpieza
        'reportante',       // quien la reporta (última actualización)
        'estadoHabitacion',
    ]);

    // 🔍 DEBUG: Ver qué llega en el request
    \Log::info('🔍 UPDATE Limpieza - Request Data', [
        'limpieza_id' => $limpieza->id_limpieza ?? $limpieza->id,
        'request_has_id_usuario' => $request->has('id_usuario_asigna'),
        'request_id_usuario' => $request->input('id_usuario_asigna'),
        'limpieza_id_usuario_after_update' => $limpieza->id_usuario_asigna,
        'request_all' => $request->all(),
    ]);

    // Evento si se incluye asignación en el request
    if ($request->has('id_usuario_asigna') && $limpieza->id_usuario_asigna !== null) {
        \Log::info('✅ DISPARANDO EVENTO NuevaLimpiezaAsignada', [
            'habitacion' => $limpieza->habitacion->numero ?? 'N/A',
            'asignado_a' => optional($limpieza->asignador)->nombre ?? 'Sin asignar',
        ]);

        event(new NuevaLimpiezaAsignada([
            'id'         => $limpieza->id_limpieza ?? $limpieza->id,
            'habitacion' => $limpieza->habitacion->numero ?? 'N/A',
            'asignado_a' => optional($limpieza->asignador)->nombre ?? 'Sin asignar',
            'estado'     => optional($limpieza->estadoHabitacion)->nombre ?? 'Desconocido',
            'fecha'      => $limpieza->fecha_inicio ?? now()->toDateTimeString(),
            'prioridad'  => $limpieza->prioridad,
        ]));
    } else {
        \Log::info('❌ NO se cumple condición para disparar evento', [
            'request_has' => $request->has('id_usuario_asigna'),
            'id_usuario_value' => $limpieza->id_usuario_asigna,
        ]);
    }

    return new LimpiezaResource($limpieza);
}

    /**
     * PATCH /limpiezas/{limpieza}/finalizar
     */
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

        return new LimpiezaResource($limpieza->fresh()->load(['estadoHabitacion']));
    }

    public function destroy(Limpieza $limpieza)
    {
        $limpieza->delete();
        return response()->noContent();
    }
}