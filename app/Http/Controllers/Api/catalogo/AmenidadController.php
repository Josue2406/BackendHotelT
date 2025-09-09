<?php
namespace App\Http\Controllers\Api\catalogo;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAmenidadRequest;
use App\Http\Requests\UpdateAmenidadRequest;
use App\Models\habitacion\Amenidad;

class AmenidadController extends Controller
{
    public function index() { return Amenidad::orderByDesc('id_amenidad')->paginate(20); }
    public function show(Amenidad $amenidade) { return $amenidade; }

    public function store(StoreAmenidadRequest $r) {
        return response()->json(Amenidad::create($r->validated()), 201);
    }
    public function update(UpdateAmenidadRequest $r, Amenidad $amenidade) {
        $amenidade->update($r->validated());
        return $amenidade->fresh();
    }
    public function destroy(Amenidad $amenidade) {
        $amenidade->delete();
        return response()->noContent();
    }
}
