<?php
namespace App\Http\Controllers\Api\catalogo;

use App\Http\Controllers\Controller;
use App\Http\Requests\catalogo\StoreFuenteRequest;
use App\Http\Requests\catalogo\UpdateFuenteRequest;
use App\Models\estadia\Fuente;

class FuenteController extends Controller
{
    public function index() { return Fuente::orderByDesc('id_fuente')->paginate(20); }
    public function show(Fuente $fuente) { return $fuente; }

    public function store(StoreFuenteRequest $r) {
        return response()->json(Fuente::create($r->validated()), 201);
    }
    public function update(UpdateFuenteRequest $r, Fuente $fuente) {
        $fuente->update($r->validated());
        return $fuente->fresh();
    }
    public function destroy(Fuente $fuente) {
        $fuente->delete(); // soft delete si tu modelo lo usa
        return response()->noContent();
    }
}
