<?php

namespace App\Http\Controllers\Api\folio;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FolioHistorialController extends Controller
{
    public function index(Request $req, int $idFolio)
    {
        $tipoFiltro = $req->query('tipo'); // pago, distribucion, linea, cierre, etc.
        $from = $req->query('from');
        $to = $req->query('to');
        $page = max(1, (int) $req->query('page', 1));
        $perPage = min(100, (int) $req->query('per_page', 50));
        $offset = ($page - 1) * $perPage;

        // ===============================
        // 1️⃣ Validar que el folio exista
        // ===============================
        $exists = DB::table('folio')->where('id_folio', $idFolio)->exists();
        if (!$exists) {
            return response()->json(['message' => 'Folio no encontrado'], 404);
        }

        // =====================================
        // 2️⃣ Construir subconsultas dinámicas
        // =====================================
        $applyDateFilter = function ($query) use ($from, $to) {
            if ($from) $query->whereDate('created_at', '>=', $from);
            if ($to) $query->whereDate('created_at', '<=', $to);
        };

        // 🔹 folio_operacion
        $qOper = DB::table('folio_operacion')
            ->selectRaw("
                id_folio,
                operacion_uid,
                tipo,
                total,
                payload,
                summary,
                created_at,
                'operacion' as fuente
            ")
            ->where('id_folio', $idFolio);

        if ($tipoFiltro) $qOper->where('tipo', $tipoFiltro);
        $applyDateFilter($qOper);

        // 🔹 folio_linea
        $qLinea = DB::table('folio_linea')
            ->selectRaw("
                id_folio,
                NULL as operacion_uid,
                'linea' as tipo,
                monto as total,
                JSON_OBJECT('id_cliente', id_cliente, 'descripcion', descripcion) as payload,
                descripcion as summary,
                created_at,
                'linea' as fuente
            ")
            ->where('id_folio', $idFolio);
        $applyDateFilter($qLinea);

        // 🔹 folio_historial
        $qHist = DB::table('folio_historial')
            ->selectRaw("
                id_folio,
                operacion_uid,
                tipo,
                total,
                payload,
                summary,
                created_at,
                'historial' as fuente
            ")
            ->where('id_folio', $idFolio);
        $applyDateFilter($qHist);

        // =====================================
        // 3️⃣ Unificar con unionAll y ordenar
        // =====================================
        $union = $qOper->unionAll($qLinea)->unionAll($qHist);

        $eventos = DB::query()
            ->fromSub($union, 'u')
            ->orderByDesc('created_at')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        $total = DB::query()
            ->fromSub($union, 'c')
            ->count();

        // =====================================
        // 4️⃣ Formatear respuesta
        // =====================================
        $eventos = $eventos->map(function ($e) {
            return [
                'tipo'        => $e->tipo,
                'summary'     => $e->summary ?? '',
                'total'       => $e->total ? round((float)$e->total, 2) : 0,
                'payload'     => $this->parsePayload($e->payload),
                'fuente'      => $e->fuente,
                'created_at'  => $e->created_at,
                'operacion_uid' => $e->operacion_uid,
            ];
        });

        return response()->json([
            'folio' => $idFolio,
            'filters' => [
                'tipo' => $tipoFiltro,
                'from' => $from,
                'to' => $to,
                'page' => $page,
                'per_page' => $perPage,
            ],
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'has_more' => ($offset + $perPage) < $total,
            ],
            'events' => $eventos,
        ]);
    }

    private function parsePayload($payload)
    {
        if (empty($payload)) return null;
        try {
            return json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            return $payload;
        }
    }
}
