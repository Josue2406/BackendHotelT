<?php
namespace App\Http\Controllers\Api\clientes;

use App\Http\Controllers\Controller;
use App\Http\Requests\clientes\StoreClienteRequest;
use App\Http\Requests\clientes\UpdateClienteRequest;
use App\Http\Resources\clientes\ClienteResource;
use App\Models\cliente\Cliente;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;

class ClienteController extends Controller
{
    /** GET /api/clientes?search=&id_tipo_doc=&genero=&sortBy=&sortDir=&perPage= */
    public function index(Request $r)
    {
        $perPage = (int) $r->query('perPage', 20);

        $clientes = Cliente::query()
        ->with(['tipoDocumento','preferencias','perfilViaje', 'salud', 'contactoEmergencia'])
            ->search($r->query('search'))
            ->when($r->filled('id_tipo_doc'), fn ($q) => $q->where('id_tipo_doc', $r->query('id_tipo_doc')))
            ->when($r->filled('genero'), fn ($q) => $q->where('genero', $r->query('genero')))
            ->orderBy($r->query('sortBy', 'id_cliente'), $r->query('sortDir', 'desc'))
            ->paginate($perPage);

        // colección paginada => incluye links y meta automáticamente
        return ClienteResource::collection($clientes);
    }

    /** GET /api/clientes/{cliente} */
    public function show(Cliente $cliente)
    {

         $cliente->load(['tipoDocumento', 'preferencias', 'perfilViaje', 'salud', 'contactoEmergencia']);
    return new ClienteResource($cliente);
    
    }

    /** POST /api/clientes */
    public function store(StoreClienteRequest $r)
    {
        try {
            $cliente = Cliente::create($r->validated());
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json(['message' => 'Conflicto: documento, email o teléfono ya existe.'], Response::HTTP_CONFLICT);
            }
            throw $e;
        }
        $cliente->load('tipoDocumento');
        $cliente->load('preferencias');
        $cliente->load('perfilViaje');
        $cliente->load('salud');
        $cliente->load('contactoEmergencia');

        return (new ClienteResource($cliente))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /** PUT /api/clientes/{cliente} */
    public function update(UpdateClienteRequest $r, Cliente $cliente)
    {
        try {
            $cliente->update($r->validated());
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json(['message' => 'Conflicto: documento, email o teléfono ya existe.'], Response::HTTP_CONFLICT);
            }
            throw $e;
        }
        $cliente->load('tipoDocumento');
        $cliente->load('preferencias');
        $cliente->load('perfilViaje');
        $cliente->load('salud');
        $cliente->load('contactoEmergencia');

        return new ClienteResource($cliente);
    }

    /** DELETE /api/clientes/{cliente} */
    public function destroy(Cliente $cliente)
    {
        $cliente->delete();
        return response()->noContent();
    }

    /** GET /api/clientes/por-doc/{numero_doc} */
    public function findByDocumento(string $numero_doc)
    {
        $cliente = Cliente::with(['tipoDocumento','preferencias', 'perfilViaje', 'salud', 'contactoEmergencia'])
        ->where('numero_doc', $numero_doc)
        ->first();

    if (!$cliente) return response()->json(['message' => 'No encontrado'], 404);
    return new ClienteResource($cliente);
    }

    /** GET /api/clientes/exists-doc/{numero_doc} → { "exists": true/false } */
    
    /*public function existsByDocumento(string $numero_doc)
    {
        return ['exists' => Cliente::where('numero_doc', $numero_doc)->exists()];
    }*/

    public function existsByDocumento(Request $r, string $numero_doc)
{
    $idTipo = $r->query('id_tipo_doc'); // opcional en querystring
    $q = Cliente::where('numero_doc', $numero_doc);
    if ($idTipo !== null) $q->where('id_tipo_doc', $idTipo);
    return ['exists' => $q->exists()];
}


    /**
     * POST /api/clientes/upsert-por-doc
     * Body: StoreClienteRequest (requiere al menos numero_doc para upsert).
     * - Crea si no existe; si existe, actualiza y devuelve el registro fresco.
     */
    public function upsertByDocumento(StoreClienteRequest $r)
    {
       $data = $r->validated();
    if (empty($data['numero_doc'])) {
        return response()->json(['message' => 'numero_doc es requerido para upsert.'], 422);
    }

    try {
        $cliente = Cliente::firstOrCreate(
            ['id_tipo_doc' => $data['id_tipo_doc'] ?? null, 'numero_doc' => $data['numero_doc']],
            $data
        );

        if (!$cliente->wasRecentlyCreated) {
            $cliente->update($data);
        }
    } catch (\Illuminate\Database\QueryException $e) {
        if ($e->getCode() === '23000') {
            return response()->json(['message' => 'Conflicto: documento, email o teléfono ya existe.'], \Symfony\Component\HttpFoundation\Response::HTTP_CONFLICT);
        }
        throw $e;
    }

    // Importante: fresh + load (o solo load si no necesitas refrescar timestamps)
    $cliente = $cliente->fresh()->load(['tipoDocumento','preferencias']);
    return new ClienteResource($cliente);
    }
}
