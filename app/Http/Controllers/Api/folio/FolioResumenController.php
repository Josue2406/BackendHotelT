<?php

namespace App\Http\Controllers\Api\folio;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FolioResumenController extends Controller
{
    // app/Http/Controllers/FolioResumenController.php

public function show(int $idFolio)
{
    $resumen = DB::table('vw_folio_resumen')->where('id_folio', $idFolio)->first();

    $asignado = DB::table('vw_folio_por_persona')
        ->where('id_folio', $idFolio)
        ->get();

    $pagos = DB::table('vw_pagos_por_persona')
        ->where('id_folio', $idFolio)
        ->get();

    // merge por persona
    $byCliente = [];
    foreach ($asignado as $a) {
        $byCliente[$a->id_cliente] = [
            'id_cliente' => (int) $a->id_cliente,
            'asignado'   => (float) $a->asignado,
            'pagos'      => 0.0,
            'saldo'      => (float) $a->asignado, // temporal
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

    /* totales agregados
    $pagosPorPersonaTotal = array_sum(array_map(fn($r) => $r['pagos'], $byCliente));
    $pagosGenerales       = $resumen ? (float) $resumen->pagos_generales : 0.0;
    $pagosTotales         = $pagosGenerales + $pagosPorPersonaTotal;

    $aDistribuir          = $resumen ? (float) $resumen->a_distribuir : 0.0;
    $distribuido          = $resumen ? (float) $resumen->distribuido : 0.0;
    $cargosSinPersona     = $resumen ? (float) $resumen->cargos_sin_persona : 0.0;

    $saldoGlobal          = $aDistribuir - $pagosTotales;
    $controlDiff          = ($distribuido + $cargosSinPersona) - $aDistribuir; // debe ser 0 */

    //Evita errores de redondeo: usa DECIMAL y, si quieres máxima precisión, bcadd/bcsub con escala 2 en lugar de float.
    $scale = 2;
$sum = fn($a,$b) => bcadd((string)$a, (string)$b, $scale);
$sub = fn($a,$b) => bcsub((string)$a, (string)$b, $scale);

$pagosPorPersonaTotal = array_reduce($byCliente, fn($t,$r)=>$sum($t,$r['pagos']), '0');
$pagosGenerales = $resumen ? (string)$resumen->pagos_generales : '0';
$pagosTotales   = $sum($pagosGenerales, $pagosPorPersonaTotal);

$aDistribuir      = $resumen ? (string)$resumen->a_distribuir      : '0';
$distribuido      = $resumen ? (string)$resumen->distribuido        : '0';
$cargosSinPersona = $resumen ? (string)$resumen->cargos_sin_persona : '0';

$saldoGlobal = $sub($aDistribuir, $pagosTotales);
$controlDiff = $sub($sum($distribuido, $cargosSinPersona), $aDistribuir);

if (!$resumen) {
  return response()->json(['message' => 'Folio no encontrado'], 404);
}


    return response()->json([
        'folio'   => $idFolio,
        'resumen' => $resumen,
        'personas'=> array_values($byCliente),
        'totales' => [
            'pagos_por_persona_total' => $pagosPorPersonaTotal,
            'pagos_generales'         => $pagosGenerales,
            'pagos_totales'           => $pagosTotales,
            'saldo_global'            => $saldoGlobal,
            'control_diff'            => $controlDiff,
        ],
    ]);
}
}