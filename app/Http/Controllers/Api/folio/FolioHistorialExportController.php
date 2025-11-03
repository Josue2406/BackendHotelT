<?php

namespace App\Http\Controllers\Api\folio;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FolioHistorialExportController extends Controller
{
    public function exportCsv(Request $req, int $idFolio)
    {
        $tipoFiltro = $req->query('tipo');
        $from = $req->query('from');
        $to = $req->query('to');

        // 1️⃣ Validar que el folio exista
        $folio = DB::table('folio')->where('id_folio', $idFolio)->first();
        if (!$folio) {
            return response()->json(['message' => 'Folio no encontrado'], 404);
        }

        // 2️⃣ Reutilizar la misma lógica del historial
        $query = DB::table('folio_operacion')
            ->selectRaw("
                id_folio, operacion_uid, tipo, total, payload, summary, created_at, 'operacion' as fuente
            ")
            ->where('id_folio', $idFolio);

        if ($tipoFiltro) $query->where('tipo', $tipoFiltro);
        if ($from) $query->whereDate('created_at', '>=', $from);
        if ($to) $query->whereDate('created_at', '<=', $to);

        $query->unionAll(
            DB::table('folio_linea')
                ->selectRaw("
                    id_folio, NULL as operacion_uid, 'linea' as tipo,
                    monto as total,
                    JSON_OBJECT('id_cliente', id_cliente, 'descripcion', descripcion) as payload,
                    descripcion as summary, created_at, 'linea' as fuente
                ")
                ->where('id_folio', $idFolio)
        );

        $query->unionAll(
            DB::table('folio_historial')
                ->selectRaw("
                    id_folio, operacion_uid, tipo, total, payload, summary, created_at, 'historial' as fuente
                ")
                ->where('id_folio', $idFolio)
        );

        $eventos = DB::query()->fromSub($query, 'u')->orderBy('created_at')->get();

        if ($eventos->isEmpty()) {
            return response()->json(['message' => 'No hay registros para exportar'], 404);
        }

        // 3️⃣ StreamedResponse para no consumir memoria
        $filename = "historial_folio_{$idFolio}.csv";

        return new StreamedResponse(function () use ($eventos) {
            $output = fopen('php://output', 'w');

            // Encabezados
            fputcsv($output, ['Fecha', 'Tipo', 'Descripción', 'Monto', 'Operación UID', 'Fuente', 'Detalle (payload)']);

            // Filas
            foreach ($eventos as $e) {
                $payload = $this->prettyPayload($e->payload);
                fputcsv($output, [
                    $e->created_at,
                    strtoupper($e->tipo),
                    $e->summary,
                    number_format((float)$e->total, 2, '.', ''),
                    $e->operacion_uid,
                    $e->fuente,
                    $payload,
                ]);
            }

            fclose($output);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    private function prettyPayload($payload): string
    {
        if (empty($payload)) return '';
        try {
            $decoded = json_decode($payload, true);
            if (is_array($decoded)) {
                return collect($decoded)
                    ->map(fn($v, $k) => "$k: " . (is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v))
                    ->implode('; ');
            }
            return (string) $payload;
        } catch (\Throwable $e) {
            return (string) $payload;
        }
    }
}
