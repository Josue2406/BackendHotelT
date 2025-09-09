<?php

namespace App\Http\Controllers\Api\catalogo;

use App\Http\Controllers\Controller;
use App\Http\Requests\reserva\StoreEstadoReservaRequest;
use App\Http\Requests\reserva\UpdateEstadoReservaRequest;
use App\Models\reserva\EstadoReserva;

class EstadoReservaController extends Controller
{
    public function index()
    {
        return EstadoReserva::orderByDesc('id_estado_res')->paginate(20);
    }

    public function store(StoreEstadoReservaRequest $request)
    {
        $estado = EstadoReserva::create($request->validated());
        return response()->json($estado, 201);
    }

    public function show(EstadoReserva $estado_reserva)
    {
        return $estado_reserva;
    }

    public function update(UpdateEstadoReservaRequest $request, EstadoReserva $estado_reserva)
    {
        $estado_reserva->update($request->validated());
        return $estado_reserva->fresh();
    }

    public function destroy(EstadoReserva $estado_reserva)
    {
        $estado_reserva->delete();
        return response()->noContent();
    }
}
