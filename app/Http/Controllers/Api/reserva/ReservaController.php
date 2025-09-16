<?php
namespace App\Http\Controllers\Api\reserva;

use App\Http\Controllers\Controller;
use App\Http\Requests\reserva\StoreReservaRequest;
use App\Http\Requests\reserva\UpdateReservaRequest;
use App\Http\Requests\reserva\CancelReservaRequest;
use App\Http\Requests\reserva\CotizarReservaRequest;
use App\Models\reserva\Reserva;
use App\Models\reserva\ReservaHabitacion;
use App\Models\reserva\ReservaServicio;
use App\Models\reserva\ReservaPolitica;
use App\Models\reserva\PoliticaCancelacion;
use App\Models\estadia\Estadia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservaController extends Controller
{
    public function index() {
        return Reserva::with(['cliente','estado','fuente'])
            ->latest('id_reserva')->paginate(20);
    }

    public function show(Reserva $reserva) {
        return $reserva->load(['cliente','estado','fuente','habitaciones','servicios','politicas']);
    }

    public function store(StoreReservaRequest $r) {
        $data = $r->validated();
        $data['fecha_creacion'] = now();
        $reserva = Reserva::create($data);
        return response()->json($reserva->fresh(), 201);
    }

    public function update(UpdateReservaRequest $r, Reserva $reserva) {
        $reserva->update($r->validated());
        return $reserva->fresh();
    }

    public function destroy(Reserva $reserva) {
        // si hay FKs dependientes, podrías impedir borrar o hacer soft delete
        $reserva->delete();
        return response()->noContent();
    }

    // ===== Acciones =====

    public function confirmar(Reserva $reserva) {
        // ajusta el ID del estado "confirmada"
        $reserva->update(['id_estado_res' => /* id estado confirmada */ 2]);
        return $reserva->fresh();
    }

    public function cancelar(CancelReservaRequest $r, Reserva $reserva) {
        // 1) marcar estado cancelada
        $reserva->update(['id_estado_res' => /* id estado cancelada */ 3]);

        // 2) (Opcional) aplicar política si existe y si corresponde la ventana:
        // - Busca reserva_politica -> politica_cancelacion
        // - Evalúa ventana vs fecha_llegada más próxima en reserva_habitacions
        // - Calcula penalidad (porcentaje | noches) y *si quieres* registra un cargo en folio/reserva_pago

        return response()->json(['ok' => true]);
    }

    public function noShow(Reserva $reserva) {
        $reserva->update(['id_estado_res' => /* id estado no_show */ 4]);
        // (Opcional) aplicar penalidad de no-show según política
        return $reserva->fresh();
    }

    public function cotizar(CotizarReservaRequest $r, Reserva $reserva) {
        $data = $r->validated();

        // Ejemplo simple: suma de servicios + (aquí iría tarifa * noches por habitación)
        $totalServicios = 0;
        if (!empty($data['servicios'])) {
            foreach ($data['servicios'] as $s) {
                $totalServicios += $s['cantidad'] * $s['precio_unitario'];
            }
        }

        // TODO: calcular tarifas por habitación según tu lógica (temporadas, tipo hab, etc.)
        $totalHabitaciones = 0;

        $total = round($totalHabitaciones + $totalServicios, 2);

        return response()->json([
            'total_habitaciones' => $totalHabitaciones,
            'total_servicios'    => $totalServicios,
            'total'              => $total
        ]);
    }

    public function generarEstadia(Reserva $reserva, Request $req) {
        // Crea una estadía derivada de la reserva (walk-in usa EstadiaController@store)
        $data = $req->validate([
            'id_cliente_titular' => 'required|integer|exists:clientes,id_cliente',
            'fecha_llegada'      => 'required|date',
            'fecha_salida'       => 'required|date|after:fecha_llegada',
            'adultos'            => 'required|integer|min:1',
            'ninos'              => 'required|integer|min:0',
            'bebes'              => 'required|integer|min:0',
            'id_fuente'          => 'nullable|integer|exists:fuentes,id_fuente',
        ]);

        $estadia = Estadia::create($data + ['id_reserva' => $reserva->id_reserva]);
        return response()->json($estadia->fresh(), 201);
    }
}
