<?php

namespace App\Http\Controllers\Api\folio;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\check_out\Folio;
use App\Models\check_out\FolioLinea;

/**
 * Controlador para la gestión de cargos (consumos) en folios
 * 
 * Permite agregar cargos generales o individuales por huésped dentro de un folio activo.
 * Los cargos se registran en folio_linea y se auditan en folio_historial.
 * 
 * @author Sistema PMS Hotel Lanaku
 * @version 1.0.0
 */
class FolioCargosController extends Controller
{
    /**
     * Listar todos los cargos de un folio
     * 
     * @param int $folioId ID del folio
     * @return \Illuminate\Http\JsonResponse
     * 
     * @example
     * GET /folios/1/cargos
     */
    public function index(int $folioId)
    {
        // Verificar que el folio existe
        $folio = Folio::find($folioId);
        
        if (!$folio) {
            return response()->json([
                'status' => 'error',
                'message' => 'Folio no encontrado',
            ], 404);
        }

        // Obtener todos los cargos del folio con información del cliente
        $cargos = FolioLinea::where('id_folio', $folioId)
            ->with(['cliente:id_cliente,nombre,apellido1,apellido2,numero_doc,email'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($cargo) {
                return [
                    'id_folio_linea' => $cargo->id_folio_linea,
                    'id_folio' => $cargo->id_folio,
                    'id_cliente' => $cargo->id_cliente,
                    'descripcion' => $cargo->descripcion,
                    'monto' => round((float) $cargo->monto, 2),
                    'tipo' => $cargo->id_cliente ? 'individual' : 'general',
                    'created_at' => $cargo->created_at->toIso8601String(),
                    'updated_at' => $cargo->updated_at->toIso8601String(),
                    'cliente' => $cargo->cliente ? [
                        'id_cliente' => $cargo->cliente->id_cliente,
                        'nombre' => trim(($cargo->cliente->nombre ?? '') . ' ' . ($cargo->cliente->apellido1 ?? '') . ' ' . ($cargo->cliente->apellido2 ?? '')),
                        'documento' => $cargo->cliente->numero_doc ?? null,
                        'email' => $cargo->cliente->email,
                    ] : null,
                ];
            });

        return response()->json([
            'status' => 'ok',
            'data' => $cargos,
            'total' => $cargos->count(),
        ]);
    }

    /**
     * Agregar un nuevo cargo al folio
     * 
     * Permite registrar dos tipos de cargos:
     * 1. Cargo general (cliente_id = null): Se agrega al folio sin asignación específica
     * 2. Cargo individual (cliente_id = X): Se asigna a un huésped específico
     * 
     * @param Request $request
     * @param int $folioId ID del folio al que se agregará el cargo
     * @return \Illuminate\Http\JsonResponse
     * 
     * @example
     * POST /folios/1/cargos
     * {
     *   "monto": 25.50,
     *   "descripcion": "Consumo minibar",
     *   "cliente_id": 12
     * }
     */
    public function store(Request $request, int $folioId)
    {
        // ===============================
        // 1️⃣ Validación del payload
        // ===============================
        $data = $request->validate([
            'monto' => ['required', 'numeric', 'gt:0'],
            'descripcion' => ['required', 'string', 'max:255'],
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id_cliente'],
        ], [
            'monto.required' => 'El monto del cargo es obligatorio',
            'monto.numeric' => 'El monto debe ser un valor numérico',
            'monto.gt' => 'El monto debe ser mayor a 0',
            'descripcion.required' => 'La descripción del cargo es obligatoria',
            'descripcion.max' => 'La descripción no puede exceder 255 caracteres',
            'cliente_id.exists' => 'El cliente especificado no existe',
        ]);

        // ===============================
        // 2️⃣ Verificar existencia del folio
        // ===============================
        $folio = Folio::with('estadoFolio')->find($folioId);
        
        if (!$folio) {
            return response()->json([
                'status' => 'error',
                'message' => 'Folio no encontrado',
            ], 404);
        }

        // ===============================
        // 3️⃣ Validar que el folio esté abierto
        // ===============================
        $estadoFolio = strtoupper($folio->estadoFolio->nombre ?? '');
        
        if ($estadoFolio === 'CERRADO') {
            return response()->json([
                'status' => 'error',
                'message' => 'El folio está cerrado. No se pueden agregar más cargos.',
                'folio_estado' => $estadoFolio,
            ], 409);
        }

        // ===============================
        // 4️⃣ Validar cliente (si es cargo individual)
        // ===============================
        $clienteId = $data['cliente_id'] ?? null;
        
        if ($clienteId) {
            // Verificar que el cliente esté asociado al folio/estadía
            $clienteValido = DB::table('estadia_cliente')
                ->join('estadia', 'estadia_cliente.id_estadia', '=', 'estadia.id_estadia')
                ->join('folio', 'estadia.id_estadia', '=', 'folio.id_estadia')
                ->where('folio.id_folio', $folioId)
                ->where('estadia_cliente.id_cliente', $clienteId)
                ->exists();

            if (!$clienteValido) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'El cliente especificado no está asociado a este folio.',
                    'cliente_id' => $clienteId,
                ], 422);
            }
        }

        // ===============================
        // 5️⃣ Registrar el cargo en transacción
        // ===============================
        DB::beginTransaction();

        try {
            // Generar UID único para la operación
            $operacionUid = 'cargo-' . time() . '-' . uniqid();

            // a) Insertar en folio_linea
            $linea = FolioLinea::create([
                'id_folio' => $folioId,
                'id_cliente' => $clienteId,
                'descripcion' => $data['descripcion'],
                'monto' => $data['monto'],
            ]);

            // b) Registrar en folio_historial para auditoría
            DB::table('folio_historial')->insert([
                'id_folio' => $folioId,
                'operacion_uid' => $operacionUid,
                'tipo' => 'cargo',
                'total' => $data['monto'],
                'payload' => json_encode([
                    'id_folio_linea' => $linea->id_folio_linea,
                    'id_cliente' => $clienteId,
                    'descripcion' => $data['descripcion'],
                    'tipo_cargo' => $clienteId ? 'individual' : 'general',
                ], JSON_UNESCAPED_UNICODE),
                'summary' => $clienteId 
                    ? "Cargo individual agregado al cliente {$clienteId}: {$data['descripcion']}"
                    : "Cargo general agregado al folio: {$data['descripcion']}",
                'created_at' => now(),
            ]);

            DB::commit();

            // ===============================
            // 6️⃣ Respuesta exitosa
            // ===============================
            return response()->json([
                'status' => 'ok',
                'message' => 'Cargo agregado correctamente',
                'data' => [
                    'id_folio_linea' => $linea->id_folio_linea,
                    'id_folio' => $folioId,
                    'id_cliente' => $clienteId,
                    'descripcion' => $data['descripcion'],
                    'monto' => round((float) $data['monto'], 2),
                    'tipo' => $clienteId ? 'individual' : 'general',
                    'created_at' => $linea->created_at->toIso8601String(),
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error al registrar el cargo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
