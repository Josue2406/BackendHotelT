<?php

namespace App\Http\Controllers\Api\folio;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FolioPagosController extends Controller
{
    /**
     * POST /api/folios/{id}/pagos
     * Body:
     * {
     *   "operacion_uid": "uuid-frontend",
     *   "monto": 100.00,
     *   "id_cliente": 21,        // null => pago general
     *   "metodo": "Tarjeta",     // opcional (si usas id_metodo_pago, envíalo también)
     *   "resultado": "OK",       // opcional
     *   "nota": "Pago a cuenta"  // opcional
     * }
     */
    public function store(Request $req, int $folioId)
    {
        // 1) Validación
        $data = $req->validate([
            'operacion_uid' => ['required','string','max:64'],
            'monto'         => ['required','numeric','gt:0'],
            'id_cliente'    => ['nullable','integer'],
            'metodo'        => ['nullable','string','max:100'],
            'resultado'     => ['nullable','string','max:50'],
            'nota'          => ['nullable','string','max:255'],
            // Si manejas id_metodo_pago / id_tipo_transaccion, descomenta:
            // 'id_metodo_pago'   => ['nullable','integer'],
            // 'id_tipo_transaccion'=> ['nullable','integer'],
        ]);

        // 2) Idempotencia: si ya existe esta operación, devuelve el estado actual
        $exists = DB::table('folio_operacion')
            ->where('operacion_uid', $data['operacion_uid'])
            ->where('id_folio', $folioId)
            ->where('tipo', 'pago')
            ->exists();

        if ($exists) {
            return app(FolioResumenController::class)->show($folioId);
        }

        // 3) Verifica que el folio exista (usamos la vista resumen)
        $resumen = DB::table('vw_folio_resumen')->where('id_folio', $folioId)->first();
        if (!$resumen) {
            return response()->json(['message' => 'Folio no encontrado'], 404);
        }

        // (Opcional) Validar que no se pague por encima del saldo:
        // - Pago general contra saldo_global
        // - Pago por persona contra saldo de esa persona
        // Si no quieres esta validación, comenta el bloque.
        $monto = (float) $data['monto'];
        if (array_key_exists('id_cliente', $data) && !is_null($data['id_cliente'])) {
            $idCliente = (int) $data['id_cliente'];

            $asignado = DB::table('vw_folio_por_persona')
                ->where('id_folio', $folioId)->where('id_cliente', $idCliente)
                ->value('asignado') ?? 0;

            $pagado  = DB::table('vw_pagos_por_persona')
                ->where('id_folio', $folioId)->where('id_cliente', $idCliente)
                ->value('pagos') ?? 0;

            $saldoPersona = (float)$asignado - (float)$pagado;
            if ($monto > $saldoPersona + 0.005) {
                return response()->json([
                    'message' => 'El monto excede el saldo del cliente',
                    'saldo_persona' => round($saldoPersona, 2)
                ], 422);
            }
        } else {
            // pago general
            // saldo_global = a_distribuir − (pagos_generales + pagos_por_persona_total)
            $pagosPorPersonaTotal = (float) (DB::table('vw_pagos_por_persona')
                ->where('id_folio', $folioId)
                ->sum('pagos') ?? 0);
            $saldoGlobal = (float)$resumen->a_distribuir - ((float)$resumen->pagos_generales + $pagosPorPersonaTotal);

            if ($monto > $saldoGlobal + 0.005) {
                return response()->json([
                    'message' => 'El monto excede el saldo global del folio',
                    'saldo_global' => round($saldoGlobal, 2)
                ], 422);
            }
        }

        // 4) Transacción + idempotencia fuerte (unique operacion_uid, id_folio, tipo)
        DB::transaction(function () use ($folioId, $data, $monto) {

            // Registrar la operación (tabla folio_operacion ya creada antes)
            DB::table('folio_operacion')->insert([
                'id_folio'      => $folioId,
                'operacion_uid' => $data['operacion_uid'],
                'tipo'          => 'pago',
                'total'         => $monto,
                'payload'       => json_encode([
                    'id_cliente' => $data['id_cliente'] ?? null,
                    'metodo'     => $data['metodo']     ?? null,
                    'nota'       => $data['nota']       ?? null,
                ], JSON_UNESCAPED_UNICODE),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            // Insertar en transaccion_pago (ajusta columnas si tu schema requiere más)
            $row = [
                'id_folio'   => $folioId,
                'id_cliente' => $data['id_cliente'] ?? null, // null = pago general
                'monto'      => $monto,
                'resultado'  => $data['resultado'] ?? 'OK',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Si usas columnas adicionales, descomenta:
            // if (!empty($data['id_metodo_pago']))     $row['id_metodo_pago'] = (int)$data['id_metodo_pago'];
            // if (!empty($data['id_tipo_transaccion'])) $row['id_tipo_transaccion'] = (int)$data['id_tipo_transaccion'];
            // if (!empty($data['metodo']))              $row['metodo'] = $data['metodo']; // si existe columna 'metodo'

            DB::table('transaccion_pago')->insert($row);
        }, 3);

        // 5) devolver estado actualizado
        return app(FolioResumenController::class)->show($folioId);
    }
}
