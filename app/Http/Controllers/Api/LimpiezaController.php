<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller; 
use App\Models\Limpieza;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LimpiezaController extends Controller
{
    /**
     * GET /limpiezas
     * Lista con paginación y relaciones cargadas.
     */
    public function index(Request $request)
    {
        $perPage = (int) ($request->input('per_page', 15));

        $query = Limpieza::with([
            'id_habitacion',
            'id_usuario_asigna',
            'id_usuario_reporta',
            'historial_limpiezas_where_id_limpieza',
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
     * POST /limpiezas
     * Crea una limpieza.
     */
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        // Por defecto, el usuario que reporta puede ser el autenticado (opcional)
        if (!isset($data['id_usuario_reporta']) && auth()->check()) {
            $data['id_usuario_reporta'] = auth()->id();
        }

        $limpieza = Limpieza::create($data);

        return response()->json(
            $limpieza->load(['id_habitacion','id_usuario_asigna','id_usuario_reporta']),
            201
        );
    }

    /**
     * GET /limpiezas/{limpieza}
     * Muestra una limpieza por id.
     */
    public function show(Limpieza $limpieza)
    {
        return $limpieza->load([
            'id_habitacion',
            'id_usuario_asigna',
            'id_usuario_reporta',
            'historial_limpiezas_where_id_limpieza',
        ]);
    }

    /**
     * PUT/PATCH /limpiezas/{limpieza}
     * Actualiza una limpieza.
     */
    public function update(Request $request, Limpieza $limpieza)
    {
        $data = $this->validateData($request, updating: true);

        $limpieza->update($data);

        return $limpieza->fresh()->load([
            'id_habitacion','id_usuario_asigna','id_usuario_reporta'
        ]);
    }

    /**
     * DELETE /limpiezas/{limpieza}
     * Elimina una limpieza.
     */
    public function destroy(Limpieza $limpieza)
    {
        $limpieza->delete();
        return response()->noContent();
    }

    /**
     * Validación compartida (store/update) SIN cambiar tu modelo.
     */
    private function validateData(Request $request, bool $updating = false): array
    {
        // En update todos los campos son opcionales; en store algunos requeridos
        $required = fn($field) => $updating ? ['sometimes'] : ['required'];

        return $request->validate([
            'nombre'              => array_merge($required('nombre'), ['string','max:100']),
            'descripcion'         => ['nullable','string','max:500'],
            'notas'               => ['nullable','string','max:500'],
            'prioridad'           => ['nullable', Rule::in(['baja','media','alta','urgente'])],

            'fecha_inicio'        => array_merge($required('fecha_inicio'), ['date']),
            'fecha_reporte'       => array_merge($required('fecha_reporte'), ['date']),
            'fecha_final'         => ['nullable','date','after_or_equal:fecha_inicio'],

            'id_habitacion'       => ['nullable','integer','exists:habitaciones,id'],
            'id_usuario_asigna'   => ['nullable','integer','exists:users,id'],
            'id_usuario_reporta'  => ['nullable','integer','exists:users,id'],
        ]);
    }
}
