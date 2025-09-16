<?php
namespace App\Http\Controllers\Api\clientes;

use App\Http\Controllers\Controller;
use App\Http\Requests\clientes\StoreClienteRequest;
use App\Http\Requests\clientes\UpdateClienteRequest;
use App\Models\cliente\Cliente;

class ClienteController extends Controller
{
    public function index()  { return Cliente::orderByDesc('id_cliente')->paginate(20); }
    public function show(Cliente $cliente) { return $cliente; }

    public function store(StoreClienteRequest $r) {
        return response()->json(Cliente::create($r->validated()), 201);
    }

    public function update(UpdateClienteRequest $r, Cliente $cliente) {
        $cliente->update($r->validated());
        return $cliente->fresh();
    }

    public function destroy(Cliente $cliente) {
        $cliente->delete();
        return response()->noContent();
    }
}
