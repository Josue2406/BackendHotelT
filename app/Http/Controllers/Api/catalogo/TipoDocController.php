<?php
namespace App\Http\Controllers\Api\catalogo;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTipoDocRequest;
use App\Http\Requests\UpdateTipoDocRequest;
use App\Models\TipoDoc;

class TipoDocController extends Controller
{
    public function index() { return TipoDoc::orderByDesc('id_tipo_doc')->paginate(20); }
    public function show(TipoDoc $tipos_doc) { return $tipos_doc; }

    public function store(StoreTipoDocRequest $r) {
        return response()->json(TipoDoc::create($r->validated()), 201);
    }
    public function update(UpdateTipoDocRequest $r, TipoDoc $tipos_doc) {
        $tipos_doc->update($r->validated());
        return $tipos_doc->fresh();
    }
    public function destroy(TipoDoc $tipos_doc) {
        $tipos_doc->delete();
        return response()->noContent();
    }
}
