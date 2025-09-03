<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mantenimiento;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MantenimientoController extends Controller
{
    /**
     * GET /mantenimientos
     * Lista con paginación y relaciones cargadas.
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);

        $query = Mantenimiento::with([
            'id_habitacion',
            'id_usuario_asigna',
            'id_usuario_reporta',
            'historial_mantenimientos_where_id_mantenimiento',
        ])->orderByDesc('fecha_inicio');

        // Filtros opcionales
        if ($request->filled('prioridad')) {
            $query->where('prioridad', $request->input('prioridad'));
        }
        if ($request->filled('pendientes')) {
            // pendientes = 1 -> sin fecha_final
            $query->when((bool)$request->boolean('pendientes'), fn($q) => $q->whereNull('fecha_final'));
        }
        if ($request->filled('id_habitacion')) {
            $query->where('id_habitacion', (int) $request->input('id_habitacion'));
        }

        return $query->paginate($perPage);
    }

    /**
     * POST /mantenimientos
     * Crea un mantenimiento.
     */
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        // Por defecto, quien reporta puede ser el autenticado (opcional)
        if (!isset($data['id_usuario_reporta']) && auth()->check()) {
            $data['id_usuario_reporta'] = auth()->id();
        }

        $mtto = Mantenimiento::create($data);

        return response()->json(
            $mtto->load(['id_habitacion','id_usuario_asigna','id_usuario_reporta']),
            201
        );
    }

    /**
     * GET /mantenimientos/{mantenimiento}
     * Muestra un mantenimiento por id.
     */
    public function show(Mantenimiento $mantenimiento)
    {
        return $mantenimiento->load([
            'id_habitacion',
            'id_usuario_asigna',
            'id_usuario_reporta',
            'historial_mantenimientos_where_id_mantenimiento',
        ]);
    }

    /**
     * PUT/PATCH /mantenimientos/{mantenimiento}
     * Actualiza un mantenimiento.
     */
    public function update(Request $request, Mantenimiento $mantenimiento)
    {
        $data = $this->validateData($request, updating: true);

        $mantenimiento->update($data);

        return $mantenimiento->fresh()->load([
            'id_habitacion','id_usuario_asigna','id_usuario_reporta'
        ]);
    }

    /**
     * DELETE /mantenimientos/{mantenimiento}
     * Elimina un mantenimiento.
     */
    public function destroy(Mantenimiento $mantenimiento)
    {
        $mantenimiento->delete();
        return response()->noContent();
    }

    /**
     * PATCH /mantenimientos/{mantenimiento}/finalizar
     * Actualiza solo la fecha_final (y opcionalmente notas).
     */
    public function finalizar(Request $request, Mantenimiento $mantenimiento)
    {
        $data = $request->validate([
            'fecha_final' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'notas'       => ['nullable', 'string', 'max:500'],
        ]);

        $mantenimiento->update([
            'fecha_final' => $data['fecha_final'],
            'notas'       => $data['notas'] ?? $mantenimiento->notas,
        ]);

        return response()->json($mantenimiento->fresh(), 200);
    }

    /**
     * Validación compartida (store/update) SIN 'exists:*' por ahora.
     * Nota: en tu modelo, fecha_inicio es nullable; dejamos requerida solo fecha_reporte.
     */
    private function validateData(Request $request, bool $updating = false): array
    {
        $required = fn($field) => $updating ? ['sometimes'] : ['required'];

        return $request->validate([
            'nombre'              => array_merge($required('nombre'), ['string','max:100']),
            'descripcion'         => ['nullable','string','max:500'],
            'notas'               => ['nullable','string','max:500'],
            'prioridad'           => ['nullable', Rule::in(['baja','media','alta','urgente'])],

            'fecha_inicio'        => ['nullable','date'], // puede venir null
            'fecha_reporte'       => array_merge($required('fecha_reporte'), ['date']),
            'fecha_final'         => ['nullable','date','after_or_equal:fecha_inicio'],

            // Sin exists:* para no chocar mientras no están las tablas/relaciones
            'id_habitacion'       => ['nullable','integer','exists:habitaciones,id'],
            'id_usuario_asigna'   => ['nullable','integer','exists:users,id'],
            'id_usuario_reporta'  => ['nullable','integer','exists:users,id'],
        ]);
    }
}
