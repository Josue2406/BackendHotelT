<?php

namespace App\Http\Controllers\Api\folio;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FolioHistorialController extends Controller
{
    /**
     * GET /api/folios/{id}/historial
     * Query params opcionales:
     *  - page (int)      : página (default 1)
     *  - per_page (int)  : items por página (default 50, máx 200)
     *  - tipo (string)   : filtro por tipo ('distribucion'|'pago'|'cierre')
     */
    public function index(Request $req, int $folioId)
    {
        // (Opcional) validar existencia del folio con la vista
        $exists = DB::table('vw_folio_resumen')->where('id_folio', $folioId)->exists();
        if (!$exists) {
            return response()->json(['message' => 'Folio no encontrado'], 404);
        }

        $tipo = $req->query('tipo');
        $page = max(1, (int)$req->query('page', 1));
        $perPage = min(200, max(1, (int)$req->query('per_page', 50)));

        $q = DB::table('folio_operacion')
            ->where('id_folio', $folioId)
            ->when($tipo, fn($qq) => $qq->where('tipo', $tipo))
            ->orderByDesc('created_at');

        $total = (clone $q)->count();
        $items = $q->forPage($page, $perPage)->get();

        // Enriquecer cada ítem con resumen legible
        $events = $items->map(function ($row) {
            $payload = $this->safeJsonDecode($row->payload);
            $summary = $this->buildSummary($row->tipo, $row->total, $payload);

            return [
                'id'            => (int)$row->id,
                'operacion_uid' => $row->operacion_uid,
                'tipo'          => $row->tipo,            // 'distribucion' | 'pago' | 'cierre'
                'total'         => $this->toDecimal($row->total),
                'payload'       => $payload,              // datos crudos guardados en la operación
                'summary'       => $summary,              // texto breve para UI
                'created_at'    => $row->created_at,
            ];
        });

        return response()->json([
            'folio'     => $folioId,
            'filters'   => ['tipo' => $tipo, 'page' => $page, 'per_page' => $perPage],
            'pagination'=> [
                'total' => $total,
                'page'  => $page,
                'per_page' => $perPage,
                'has_more' => $page * $perPage < $total,
            ],
            'events'    => $events,
        ]);
    }

    private function safeJsonDecode(?string $json): array
    {
        if (!$json) return [];
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    private function toDecimal($num): string
    {
        return number_format((float)$num, 2, '.', '');
    }

    private function buildSummary(string $tipo, $total, array $payload): string
    {
        $totalFmt = $this->toDecimal($total);

        switch ($tipo) {
            case 'pago':
                // Deudor (a quién se aplica el pago) y pagador (quién paga) desde payload
                $deudor   = $payload['id_cliente'] ?? null;
                $pTipo    = $payload['pagador_tipo'] ?? null;
                $pCli     = $payload['id_pagador_cliente'] ?? null;
                $pEmp     = $payload['id_pagador_empresa'] ?? null;

                if ($deudor !== null) {
                    if ($pTipo === 'empresa' && $pEmp) {
                        return "Pago $totalFmt aplicado a cliente {$deudor} (pagó empresa {$pEmp})";
                    } elseif ($pTipo === 'cliente' && $pCli) {
                        return "Pago $totalFmt aplicado a cliente {$deudor} (pagó cliente {$pCli})";
                    }
                    return "Pago $totalFmt aplicado a cliente {$deudor}";
                }
                return "Pago general $totalFmt";

            case 'distribucion':
                $asigs = $payload['asignaciones'] ?? [];
                $n = count($asigs);
                $det = $n ? (" → " . implode(', ',
                    array_map(fn($a) =>
                        (isset($a['id_cliente']) ? $a['id_cliente'] : '?')
                        . ':' .
                        (isset($a['monto']) ? $this->toDecimal($a['monto']) : '0.00')
                    , $asigs)
                )) : '';
                return "Distribución $totalFmt entre {$n} persona(s)$det";

            case 'cierre':
                $tit = $payload['titular'] ?? null;
                return "Cierre: traslado total $totalFmt al titular " . ($tit ?? '?');

            default:
                return ucfirst($tipo) . " $totalFmt";
        }
    }
}
