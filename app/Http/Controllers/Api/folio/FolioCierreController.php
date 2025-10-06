<?php

namespace App\Http\Controllers\Api\folio;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FolioCierreController extends Controller
{
    /**
     * POST /api/folios/{id}/cerrar
     * Body:
     * {
     *   "operacion_uid": "uuid-frontend",
     *   "id_cliente_titular": 21
     * }
     */
    public function cerrar(Request $req, int $folioId)
    {
        // 1) Validación
        $data = $req->validate([
            'operacion_uid'       => ['required','string','max:64'],
            'id_cliente_titular'  => ['required','integer'],
        ]);
        $titular = (int) $data['id_cliente_titular'];

        // 2) Idempotencia: ¿ya cerramos con este UID?
        $exists = DB::table('folio_operacion')
            ->where('operacion_uid', $data['operacion_uid'])
            ->where('id_folio', $folioId)
            ->where('tipo', 'cierre')
            ->exists();

        if ($exists) {
            return app(FolioResumenController::class)->show($folioId);
        }

        // 3) Leer resumen del folio
        $res = DB::table('vw_folio_resumen')->where('id_folio', $folioId)->first();
        if (!$res) {
            return response()->json(['message' => 'Folio no encontrado'], 404);
        }

        $cargosGeneralesPend = round((float)$res->cargos_sin_persona, 2);

        // 4) Saldos por persona = asignado - pagos (solo >0)
        //    Usamos las vistas existentes.
        $asignado = DB::table('vw_folio_por_persona')
            ->where('id_folio', $folioId)
            ->get(['id_cliente','asignado']);

        $pagos    = DB::table('vw_pagos_por_persona')
            ->where('id_folio', $folioId)
            ->get(['id_cliente','pagos'])
            ->keyBy('id_cliente');

        $saldos = [];
        $totalSaldosPersonas = 0.0;

        foreach ($asignado as $a) {
            $idc = (int) $a->id_cliente;
            $asig = (float) $a->asignado;
            $pay  = (float) ($pagos[$idc]->pagos ?? 0);
            $saldo = round($asig - $pay, 2);
            if ($saldo > 0.0) {
                $saldos[] = ['id_cliente' => $idc, 'saldo' => $saldo];
                $totalSaldosPersonas += $saldo;
            }
        }

        // 5) Total a trasladar al titular
        $totalATitular = round($cargosGeneralesPend + $totalSaldosPersonas, 2);
        if ($totalATitular <= 0.0) {
            // Nada que mover: ya está cuadrado
            return response()->json([
                'message' => 'No hay saldos pendientes para trasladar al titular'
            ], 422);
        }

        // 6) Transacción + idempotencia fuerte
        DB::transaction(function () use ($folioId, $data, $titular, $cargosGeneralesPend, $saldos, $totalATitular) {

            // Registrar operación de cierre
            DB::table('folio_operacion')->insert([
                'id_folio'      => $folioId,
                'operacion_uid' => $data['operacion_uid'],
                'tipo'          => 'cierre',
                'total'         => $totalATitular,
                'payload'       => json_encode([
                    'titular' => $titular,
                    'cargos_generales_pendientes' => $cargosGeneralesPend,
                    'saldos_personas' => $saldos
                ], JSON_UNESCAPED_UNICODE),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            // a) Si hay cargos generales pendientes, reclasifícalos al titular
            if ($cargosGeneralesPend > 0) {
                // quita de generales (línea negativa sin persona)
                DB::table('folio_linea')->insert([
                    'id_folio'   => $folioId,
                    'id_cliente' => null,
                    'descripcion'=> 'Cierre: reclasificación de cargos generales',
                    'monto'      => -1 * $cargosGeneralesPend,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // b) Quita saldos de cada persona (líneas negativas por persona)
            $sumQuitar = 0.0;
            foreach ($saldos as $s) {
                DB::table('folio_linea')->insert([
                    'id_folio'   => $folioId,
                    'id_cliente' => $s['id_cliente'],
                    'descripcion'=> 'Cierre: transferencia de saldo al titular',
                    'monto'      => -1 * $s['saldo'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $sumQuitar += $s['saldo'];
            }

            // c) Agrega una sola línea positiva al titular por el total movido
            $montoTitular = round($cargosGeneralesPend + $sumQuitar, 2);
            DB::table('folio_linea')->insert([
                'id_folio'   => $folioId,
                'id_cliente' => $titular,
                'descripcion'=> 'Cierre: asunción de saldos por titular',
                'monto'      => $montoTitular,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }, 3);

        // 7) Devuelve estado actualizado
        return app(FolioResumenController::class)->show($folioId);
    }
}
