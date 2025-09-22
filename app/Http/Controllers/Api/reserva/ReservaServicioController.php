<?php
namespace App\Http\Controllers\Api\reserva;

use App\Http\Controllers\Controller;
use App\Http\Requests\reserva\AddReservaServicioRequest;
use App\Models\reserva\Reserva;
use App\Models\reserva\ReservaServicio;
use Illuminate\Http\Request;

class ReservaServicioController extends Controller
{
    public function index(Reserva $reserva) {
        return $reserva->servicios()->with('servicio')->get();
    }

    public function store(AddReservaServicioRequest $r, Reserva $reserva) {
        $data = $r->validated();

        // En tu esquema hay índice único (id_reserva, id_servicio) -> ajusta si aplica
        $existe = ReservaServicio::where('id_reserva',$reserva->id_reserva)
            ->where('id_servicio',$data['id_servicio'])->first();

        if ($existe) {
            // si quieres acumular cantidades:
            $existe->update([
                'cantidad' => $existe->cantidad + $data['cantidad'],
                'precio_unitario' => $data['precio_unitario'], // o mantener el anterior
                'descripcion' => $data['descripcion'] ?? $existe->descripcion
            ]);
            return $existe->fresh();
        }

        $row = $reserva->servicios()->create($data);
        return response()->json($row->fresh('servicio'), 201);
    }

    public function update(AddReservaServicioRequest $r, Reserva $reserva, $id) {
        $row = $reserva->servicios()->where('id_reserva_serv',$id)->firstOrFail();
        $row->update($r->validated());
        return $row->fresh();
    }

    public function destroy(Reserva $reserva, $id) {
        $row = $reserva->servicios()->where('id_reserva_serv',$id)->firstOrFail();
        $row->delete();
        return response()->noContent();
    }

    public function storeBatch(Request $r, Reserva $reserva) {
        $payload = $r->validate([
            'servicios' => 'required|array|min:1',
            'servicios.*.id_servicio' => 'required|integer|exists:servicio,id_servicio',
            'servicios.*.cantidad' => 'required|integer|min:1',
            'servicios.*.precio_unitario' => 'required|numeric|min:0',
            'servicios.*.descripcion' => 'nullable|string|max:200',
        ]);
        $result = [];
        foreach ($payload['servicios'] as $data) {
            $existe = ReservaServicio::where('id_reserva', $reserva->id_reserva)
                ->where('id_servicio', $data['id_servicio'])->first();
            if ($existe) {
                $existe->update([
                    'cantidad' => $existe->cantidad + $data['cantidad'],
                    'precio_unitario' => $data['precio_unitario'],
                    'descripcion' => $data['descripcion'] ?? $existe->descripcion
                ]);
                $result[] = $existe->fresh();
            } else {
                $row = $reserva->servicios()->create($data);
                $result[] = $row->fresh('servicio');
            }
        }
        return response()->json($result, 201);
    }
}
