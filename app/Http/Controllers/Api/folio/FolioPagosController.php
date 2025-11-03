<?php

namespace App\Http\Controllers\Api\folio;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\check_out\Folio;

class FolioPagosController extends Controller
{
    public function store(Request $req, int $folioId)
    {
        // 1️⃣ Validación
        $data = $req->validate([
            'operacion_uid' => ['required', 'string', 'max:64'],
            'monto'         => ['required', 'numeric', 'gt:0'],
            'id_cliente'    => ['nullable', 'integer'],
            'metodo'        => ['nullable', 'string', 'max:100'],
            'resultado'     => ['nullable', 'string', 'max:50'],
            'nota'          => ['nullable', 'string', 'max:255'],
        ]);

        // 2️⃣ Verificar existencia y estado del folio
        $folio = Folio::with('estadoFolio')->find($folioId);
        if (!$folio) {
            return response()->json(['message' => 'Folio no encontrado'], 404);
        }

        if (strtoupper($folio->estadoFolio->nombre ?? '') === 'CERRADO') {
            return response()->json(['message' => 'El folio está cerrado y no se pueden registrar pagos.'], 409);
        }

        // 3️⃣ Idempotencia: si la operación ya existe → devolver resumen actual
        $exists = DB::table('folio_operacion')
            ->where('operacion_uid', $data['operacion_uid'])
            ->where('id_folio', $folioId)
            ->where('tipo', 'pago')
            ->exists();

        if ($exists) {
            return app(FolioResumenController::class)->show($folioId);
        }

        // 4️⃣ Validar existencia del resumen del folio
        $resumen = DB::table('vw_folio_resumen')->where('id_folio', $folioId)->first();
        if (!$resumen) {
            return response()->json(['message' => 'No se encontró información del folio.'], 404);
        }

        // 5️⃣ Validar límites de pago
        $monto = (float) $data['monto'];
        $idCliente = $data['id_cliente'] ?? null;

        if ($idCliente) {
            // Pago individual
            $asignado = (float) (DB::table('vw_folio_por_persona')
                ->where('id_folio', $folioId)
                ->where('id_cliente', $idCliente)
                ->value('asignado') ?? 0);

            $pagado = (float) (DB::table('vw_pagos_por_persona')
                ->where('id_folio', $folioId)
                ->where('id_cliente', $idCliente)
                ->value('pagos') ?? 0);

            $saldoPersona = $asignado - $pagado;

            if ($monto > $saldoPersona + 0.005) {
                return response()->json([
                    'message' => 'El monto excede el saldo del cliente.',
                    'saldo_persona' => round($saldoPersona, 2)
                ], 422);
            }
        } else {
            // Pago general
            $pagosPorPersonaTotal = (float) (DB::table('vw_pagos_por_persona')
                ->where('id_folio', $folioId)
                ->sum('pagos') ?? 0);

            $saldoGlobal = (float) $resumen->a_distribuir - ((float) $resumen->pagos_generales + $pagosPorPersonaTotal);

            if ($monto > $saldoGlobal + 0.005) {
                return response()->json([
                    'message' => 'El monto excede el saldo global del folio.',
                    'saldo_global' => round($saldoGlobal, 2)
                ], 422);
            }
        }

        // 6️⃣ Transacción + idempotencia fuerte
        try {
            DB::transaction(function () use ($folioId, $data, $monto) {
                // a) Registrar operación
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

                // b) Insertar en transacción de pago
                $pagoRow = [
                    'id_folio'   => $folioId,
                    'id_cliente' => $data['id_cliente'] ?? null,
                    'monto'      => $monto,
                    'resultado'  => $data['resultado'] ?? 'OK',
                    'metodo'     => $data['metodo'] ?? 'Efectivo',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                DB::table('transaccion_pago')->insert($pagoRow);

                // c) Registrar línea contable en folio_linea
                DB::table('folio_linea')->insert([
                    'id_folio'    => $folioId,
                    'id_cliente'  => $data['id_cliente'] ?? null,
                    'descripcion' => $data['id_cliente']
                        ? 'Pago aplicado al cliente ' . $data['id_cliente']
                        : 'Pago general aplicado al folio',
                    'monto'       => -1 * $monto, // Los pagos reducen saldo
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }, 3);
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), '1062')) {
                return app(FolioResumenController::class)->show($folioId);
            }
            throw $e;
        }

        // 7️⃣ Devolver resumen actualizado con estado_folio
        return app(FolioResumenController::class)->show($folioId);
    }
}
