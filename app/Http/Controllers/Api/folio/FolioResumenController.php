<?php

namespace App\Http\Controllers\Api\folio;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\check_out\Folio;

class FolioResumenController extends Controller
{
    public function show(int $idFolio)
    {
        // 🔹 Buscar folio y su estado asociado
        $folio = Folio::with('estadoFolio:id_estado_folio,nombre')
            ->where('id_folio', $idFolio)
            ->first();

        if (!$folio) {
            return response()->json(['message' => 'Folio no encontrado'], 404);
        }

        // 🔹 Determinar descripción del estado
        $estado = strtoupper($folio->estadoFolio->nombre ?? 'DESCONOCIDO');
        $descripcionEstado = match ($estado) {
            'ABIERTO' => 'Folio activo con operaciones pendientes o pagos en curso.',
            'CERRADO' => 'Folio cerrado. No se permiten más operaciones.',
            default   => 'Estado del folio no identificado.',
        };

        // 🔹 Cargar resumen general desde la vista
        $resumen = DB::table('vw_folio_resumen')
            ->where('id_folio', $idFolio)
            ->first();

        if (!$resumen) {
            return response()->json(['message' => 'No hay datos en el resumen del folio.'], 404);
        }

        // 🔹 Detalle por persona
        $asignado = DB::table('vw_folio_por_persona')
            ->where('id_folio', $idFolio)
            ->get();

        $pagos = DB::table('vw_pagos_por_persona')
            ->where('id_folio', $idFolio)
            ->get();

        // 🔹 Combinar asignaciones y pagos
        $byCliente = [];
        foreach ($asignado as $a) {
            $byCliente[$a->id_cliente] = [
                'id_cliente' => (int) $a->id_cliente,
                'asignado'   => (float) $a->asignado,
                'pagos'      => 0.0,
                'saldo'      => (float) $a->asignado,
            ];
        }

        foreach ($pagos as $p) {
            if (!isset($byCliente[$p->id_cliente])) {
                $byCliente[$p->id_cliente] = [
                    'id_cliente' => (int) $p->id_cliente,
                    'asignado'   => 0.0,
                    'pagos'      => 0.0,
                    'saldo'      => 0.0,
                ];
            }
            $byCliente[$p->id_cliente]['pagos'] += (float) $p->pagos;
            $byCliente[$p->id_cliente]['saldo'] =
                $byCliente[$p->id_cliente]['asignado'] - $byCliente[$p->id_cliente]['pagos'];
        }

        // ============================
        // 🔹 Totales con precisión decimal
        // ============================
        $scale = 2;
        $sum = fn($a, $b) => bcadd((string)$a, (string)$b, $scale);
        $sub = fn($a, $b) => bcsub((string)$a, (string)$b, $scale);

        $pagosPorPersonaTotal = array_reduce($byCliente, fn($t, $r) => $sum($t, $r['pagos']), '0');
        $pagosGenerales       = $resumen ? (string)$resumen->pagos_generales : '0';
        $pagosTotales         = $sum($pagosGenerales, $pagosPorPersonaTotal);

        $aDistribuir      = $resumen ? (string)$resumen->a_distribuir : '0';
        $distribuido      = $resumen ? (string)$resumen->distribuido : '0';
        $cargosSinPersona = $resumen ? (string)$resumen->cargos_sin_persona : '0';

        $saldoGlobal = $sub($aDistribuir, $pagosTotales);
        $controlDiff = $sub($sum($distribuido, $cargosSinPersona), $aDistribuir);

        // ============================
        // 🔹 Respuesta JSON
        // ============================
        return response()->json([
            'folio' => $idFolio,
            'estado_folio' => [
                'nombre' => $estado,
                'descripcion' => $descripcionEstado,
            ],
            'resumen' => [
                'a_distribuir' => $aDistribuir,
                'distribuido' => $distribuido,
                'cargos_sin_persona' => $cargosSinPersona,
                'pagos_generales' => $pagosGenerales,
            ],
            'personas' => array_values($byCliente),
            'totales' => [
                'pagos_por_persona_total' => $pagosPorPersonaTotal,
                'pagos_generales' => $pagosGenerales,
                'pagos_totales' => $pagosTotales,
                'saldo_global' => $saldoGlobal,
                'control_diff' => $controlDiff,
            ],
        ]);
    }
}
