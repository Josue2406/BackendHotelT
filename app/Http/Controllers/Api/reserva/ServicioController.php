<?php
namespace App\Http\Controllers\Api\reserva;

use App\Http\Controllers\Controller;
use App\Http\Requests\reserva\StoreServicioRequest;
use App\Http\Requests\reserva\UpdateServicioRequest;
use App\Models\reserva\Servicio;

class ServicioController extends Controller
{
    public function index() {
        return Servicio::orderByDesc('id_servicio')->paginate(20);
    }

    public function show(Servicio $servicio) { return $servicio; }

    public function store(StoreServicioRequest $r) {
        return response()->json(Servicio::create($r->validated()), 201);
    }

    public function update(UpdateServicioRequest $r, Servicio $servicio) {
        $servicio->update($r->validated());
        return $servicio->fresh();
    }

    public function destroy(Servicio $servicio) {
        $servicio->delete();
        return response()->noContent();
    }
}
