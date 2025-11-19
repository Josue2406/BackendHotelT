<?php

namespace App\Http\Controllers\Api\frontdesk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\cliente\Cliente;
use App\Http\Resources\clientes\ClienteResource;

class ClientesLookupController extends Controller
{
    /**
     * GET /api/frontdesk/clientes/_lookup
     * - Búsqueda flexible: por id, cédula, nombre o email
     * - Parámetros: 
     *   - q: término de búsqueda general
     *   - tipo: 'nombre', 'documento', 'email' (opcional)
     *   - id: búsqueda directa por ID
     *   - cedula: búsqueda por documento (retrocompatibilidad)
     * - Devuelve lista de clientes o cliente único
     */
    public function show(Request $request)
    {
        $data = $request->validate([
            'id'          => 'nullable|integer|exists:clientes,id_cliente',
            'cedula'      => 'nullable|string|max:50',
            'q'           => 'nullable|string|min:2|max:100', // término de búsqueda general
            'tipo'        => 'nullable|string|in:nombre,documento,email', // tipo de búsqueda
            'id_tipo_doc' => 'nullable|integer|exists:tipos_documento,id_tipo_doc',
        ]);

        // Búsqueda por ID (devuelve un solo cliente)
        if (!empty($data['id'])) {
            $cliente = Cliente::with([
                'tipoDocumento',
                'preferencias',
                'perfilViaje',
                'salud',
                'contactoEmergencia',
            ])->find((int)$data['id']);

            if (!$cliente) {
                return response()->json(['message' => 'No se encontró el cliente.'], 404);
            }

            return new ClienteResource($cliente);
        }

        // Búsqueda por cédula (retrocompatibilidad)
        if (!empty($data['cedula'])) {
            $query = Cliente::query()->with([
                'tipoDocumento',
                'preferencias',
                'perfilViaje',
                'salud',
                'contactoEmergencia',
            ]);

            if (!empty($data['id_tipo_doc'])) {
                $query->where('id_tipo_doc', (int)$data['id_tipo_doc']);
            }

            $raw  = trim($data['cedula']);
            $norm = $this->normalizeDocumento($raw);

            $query->where(function ($q) use ($raw, $norm) {
                $q->where('numero_doc', $raw);
                if ($norm !== $raw) {
                    $q->orWhere('numero_doc', $norm);
                }
            });

            $cliente = $query->first();

            if (!$cliente) {
                return response()->json(['message' => 'No se encontró el cliente.'], 404);
            }

            return new ClienteResource($cliente);
        }

        // Búsqueda general por término 'q'
        if (!empty($data['q'])) {
            $termino = trim($data['q']);
            $tipo = $data['tipo'] ?? 'nombre'; // por defecto busca por nombre

            $query = Cliente::query();

            switch ($tipo) {
                case 'nombre':
                    // Buscar en nombre, apellido1, apellido2
                    $query->where(function ($q) use ($termino) {
                        $q->where('nombre', 'LIKE', "%{$termino}%")
                          ->orWhere('apellido1', 'LIKE', "%{$termino}%")
                          ->orWhere('apellido2', 'LIKE', "%{$termino}%")
                          ->orWhereRaw("CONCAT(nombre, ' ', apellido1, ' ', COALESCE(apellido2, '')) LIKE ?", ["%{$termino}%"]);
                    });
                    break;

                case 'documento':
                    $norm = $this->normalizeDocumento($termino);
                    $query->where(function ($q) use ($termino, $norm) {
                        $q->where('numero_doc', 'LIKE', "%{$termino}%");
                        if ($norm !== $termino) {
                            $q->orWhere('numero_doc', 'LIKE', "%{$norm}%");
                        }
                    });
                    break;

                case 'email':
                    $query->where('email', 'LIKE', "%{$termino}%");
                    break;
            }

            // Limitar resultados
            $clientes = $query->limit(20)->get();

            return response()->json([
                'message' => 'Búsqueda completada.',
                'clientes' => $clientes->map(fn($c) => [
                    'id_cliente' => $c->id_cliente,
                    'nombre' => $c->nombre,
                    'apellido1' => $c->apellido1,
                    'apellido2' => $c->apellido2,
                    'email' => $c->email,
                    'telefono' => $c->telefono,
                    'numero_doc' => $c->numero_doc,
                    'nacionalidad' => $c->nacionalidad,
                ]),
                'total' => $clientes->count(),
            ]);
        }

        return response()->json(['message' => 'Debe enviar id, cedula o q (término de búsqueda).'], 422);
    }

    private function normalizeDocumento(string $s): string
    {
        // Quita espacios y guiones (convierte 1-1111-1111 -> 1111111111)
        $s = preg_replace('/\s+/', '', $s);
        $s = str_replace(['-','–','—'], '', $s);
        return $s;
    }
}
