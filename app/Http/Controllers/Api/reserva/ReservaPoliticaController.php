<?php
namespace App\Http\Controllers\Api\reserva;

use App\Http\Controllers\Controller;
use App\Http\Requests\SetReservaPoliticaRequest;
use App\Models\Reserva;
use App\Models\ReservaPolitica;

class ReservaPoliticaController extends Controller
{
    public function index(Reserva $reserva) {
        return $reserva->politicas()->with('politica')->get();
    }

    public function store(SetReservaPoliticaRequest $r, Reserva $reserva) {
        $data = $r->validated();

        // Evitar duplicados por (reserva, politica)
        $existe = ReservaPolitica::where('id_reserva',$reserva->id_reserva)
            ->where('id_politica',$data['id_politica'])->exists();

        if ($existe) return response()->json(['message'=>'Ya asignada.'], 422);

        $row = $reserva->politicas()->create($data);
        return response()->json($row->fresh('politica'), 201);
    }

    public function destroy(Reserva $reserva, $id) {
        $row = $reserva->politicas()->where('id_reserva_politica',$id)->firstOrFail();
        $row->delete();
        return response()->noContent();
    }
}
