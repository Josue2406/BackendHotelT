<?php

namespace App\Http\Controllers\Api\frontdesk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\cliente\Cliente;
use App\Http\Resources\clientes\ClienteResource;

class ClientesLookupController extends Controller
{
    /**
     * GET /api/frontdesk/clientes/_lookup?cedula=... | ?id=... [&id_tipo_doc=...]
     * - Busca por id_cliente o por numero_doc (cédula).
     * - Devuelve ClienteResource con relaciones útiles para recepción.
     */
    public function show(Request $request)
    {
        $data = $request->validate([
            'id'          => 'nullable|integer|exists:clientes,id_cliente',
            'cedula'      => 'nullable|string|max:50',
            'id_tipo_doc' => 'nullable|integer|exists:tipos_documento,id_tipo_doc', // opcional
        ]);

        if (empty($data['id']) && empty($data['cedula'])) {
            return response()->json(['message' => 'Debe enviar id o cedula.'], 422);
        }

        $query = Cliente::query()->with([
            'tipoDocumento',
            'preferencias',
            'perfilViaje',
            'salud',
            'contactoEmergencia',
        ]);

        // (Opcional) Si envían tipo de documento, filtramos
        if (!empty($data['id_tipo_doc'])) {
            $query->where('id_tipo_doc', (int)$data['id_tipo_doc']);
        }

        if (!empty($data['id'])) {
            $query->where('id_cliente', (int)$data['id']);
        } else {
            // Buscar por numero_doc (cédula) con y sin guiones/espacios
            $raw  = trim($data['cedula']);
            $norm = $this->normalizeDocumento($raw);

            $query->where(function ($q) use ($raw, $norm) {
                $q->where('numero_doc', $raw);
                if ($norm !== $raw) {
                    $q->orWhere('numero_doc', $norm);
                }
            });
        }

        $cliente = $query->first();

        if (!$cliente) {
            return response()->json(['message' => 'No se encontró el cliente.'], 404);
        }

        return new ClienteResource($cliente);
    }

    private function normalizeDocumento(string $s): string
    {
        // Quita espacios y guiones (convierte 1-1111-1111 -> 1111111111)
        $s = preg_replace('/\s+/', '', $s);
        $s = str_replace(['-','–','—'], '', $s);
        return $s;
    }
}
