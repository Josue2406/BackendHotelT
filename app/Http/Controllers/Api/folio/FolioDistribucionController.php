<?php

namespace App\Http\Controllers\Api\folio;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FolioDistribucionController extends Controller
{
    /**
     * POST /api/folios/{id}/distribuir
     * Body:
     * {
     *   "operacion_uid": "uuid-que-envia-el-frontend",
     *   "strategy": "single|equal|percent|fixed|custom",
     *   "responsables": [
     *     {"id_cliente": 21, "percent": 70, "amount": 100},
     *     ...
     *   ]
     * }
     */
    public function distribuir(Request $req, int $folioId)
    {
        // 1) Validación básica del request
        $data = $req->validate([
            'operacion_uid' => ['required','string','max:64'],
            'strategy'      => ['required', Rule::in(['single','equal','percent','fixed','custom'])],
            'responsables'  => ['required','array','min:1'],
            'responsables.*.id_cliente' => ['required','integer'],
            'responsables.*.percent'    => ['nullable','numeric'],
            'responsables.*.amount'     => ['nullable','numeric'],
        ]);

        // 2) Idempotencia rápida: si ya existe, devolver estado actual
        $exists = DB::table('folio_operacion')
            ->where('operacion_uid', $data['operacion_uid'])
            ->where('id_folio', $folioId)
            ->where('tipo', 'distribucion')
            ->exists();

        if ($exists) {
            // Ya se procesó; devolvemos el estado actual del folio
            return app(FolioResumenController::class)->show($folioId);
        }

        // 3) Totales base: distribuimos SOLO lo pendiente (= cargos sin persona)
        $res = DB::table('vw_folio_resumen')->where('id_folio', $folioId)->first();
        if (!$res) {
            return response()->json(['message' => 'Folio no encontrado'], 404);
        }
        $pendiente = max(0.0, (float)$res->cargos_sin_persona);
        if ($pendiente <= 0) {
            return response()->json(['message' => 'No hay cargos generales para distribuir'], 422);
        }

        // 4) Calcular asignaciones por strategy
        $asignaciones = []; // [['id_cliente'=>X, 'monto'=>Y], ...]
        $strategy = $data['strategy'];
        $resp = $data['responsables'];

        switch ($strategy) {
            case 'single': {
                $asignaciones[] = ['id_cliente' => $resp[0]['id_cliente'], 'monto' => round($pendiente, 2)];
                break;
            }

            case 'equal': {
                $n = count($resp);
                $base = round($pendiente / $n, 2);
                $sum  = $base * ($n - 1);
                foreach ($resp as $i => $r) {
                    $m = ($i < $n - 1) ? $base : round($pendiente - $sum, 2);
                    $asignaciones[] = ['id_cliente' => $r['id_cliente'], 'monto' => $m];
                }
                break;
            }

            case 'percent': {
                $percSum = array_sum(array_map(fn($r) => (float)($r['percent'] ?? 0), $resp));
                if (abs($percSum - 100) > 0.01) {
                    return response()->json(['message' => 'Los porcentajes deben sumar 100%'], 422);
                }
                $n = count($resp); $acum = 0;
                foreach ($resp as $i => $r) {
                    if ($i < $n - 1) {
                        $m = round($pendiente * ((float)$r['percent'] / 100), 2);
                        $acum += $m;
                    } else {
                        $m = round($pendiente - $acum, 2);
                    }
                    $asignaciones[] = ['id_cliente' => $r['id_cliente'], 'monto' => $m];
                }
                break;
            }

            case 'fixed':
            case 'custom': {
                $sum = array_sum(array_map(fn($r) => (float)($r['amount'] ?? 0), $resp));
                if (round($sum, 2) !== round($pendiente, 2)) {
                    return response()->json(['message' => 'La suma de montos no coincide con el total a distribuir'], 422);
                }
                foreach ($resp as $r) {
                    $asignaciones[] = ['id_cliente' => $r['id_cliente'], 'monto' => round((float)$r['amount'], 2)];
                }
                break;
            }
        }

        // 5) Ejecutar de forma transaccional + idempotencia fuerte (unique operacion_uid)
        try {
            DB::transaction(function () use ($folioId, $data, $pendiente, $asignaciones) {

                // Registrar operación (UNIQUE operacion_uid asegura idempotencia)
                DB::table('folio_operacion')->insert([
                    'id_folio'      => $folioId,
                    'operacion_uid' => $data['operacion_uid'],
                    'tipo'          => 'distribucion',
                    'total'         => $pendiente,
                    'payload'       => json_encode(['asignaciones' => $asignaciones], JSON_UNESCAPED_UNICODE),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                // Línea negativa para quitar cargos generales
                DB::table('folio_linea')->insert([
                    'id_folio'   => $folioId,
                    'id_cliente' => null,
                    'descripcion'=> 'Distribución de cargos',
                    'monto'      => -1 * $pendiente,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Líneas por persona
                foreach ($asignaciones as $a) {
                    DB::table('folio_linea')->insert([
                        'id_folio'   => $folioId,
                        'id_cliente' => $a['id_cliente'],
                        'descripcion'=> 'Asignación de cargos',
                        'monto'      => $a['monto'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }, 3);
        } catch (\Illuminate\Database\QueryException $e) {
            // Si falló por UNIQUE (ya insertado por retry o doble click), devolvemos estado actual
            if (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), '1062')) {
                return app(FolioResumenController::class)->show($folioId);
            }
            throw $e;
        }

        // 6) Devolver el nuevo estado del folio
        return app(FolioResumenController::class)->show($folioId);
    }
}
