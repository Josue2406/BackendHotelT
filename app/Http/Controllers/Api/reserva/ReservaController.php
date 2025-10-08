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
    public function index(Request $request) {
        $query = Reserva::with(['cliente','estado','fuente','habitaciones.habitacion']);

        // Filtro: search (búsqueda general)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('id_reserva', 'like', "%{$search}%")
                  ->orWhere('notas', 'like', "%{$search}%")
                  ->orWhereHas('cliente', function($qCliente) use ($search) {
                      $qCliente->where('nombre', 'like', "%{$search}%")
                               ->orWhere('apellido1', 'like', "%{$search}%")
                               ->orWhere('apellido2', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filtro: estado (por nombre del estado)
        if ($request->filled('estado')) {
            $nombreEstado = $request->input('estado');
            $query->whereHas('estado', function($qEstado) use ($nombreEstado) {
                $qEstado->where('nombre', 'like', "%{$nombreEstado}%");
            });
        }

        // Filtro: desde/hasta (rango de fechas de creación o llegada)
        if ($request->filled('desde')) {
            $desde = $request->input('desde');
            $query->where(function($q) use ($desde) {
                $q->where('fecha_creacion', '>=', $desde)
                  ->orWhereHas('habitaciones', function($qHab) use ($desde) {
                      $qHab->where('fecha_llegada', '>=', $desde);
                  });
            });
        }

        if ($request->filled('hasta')) {
            $hasta = $request->input('hasta');
            $query->where(function($q) use ($hasta) {
                $q->where('fecha_creacion', '<=', $hasta)
                  ->orWhereHas('habitaciones', function($qHab) use ($hasta) {
                      $qHab->where('fecha_llegada', '<=', $hasta);
                  });
            });
        }

        // Filtro: fuente (por nombre del canal)
        if ($request->filled('fuente')) {
            $nombreFuente = $request->input('fuente');
            $query->whereHas('fuente', function($qFuente) use ($nombreFuente) {
                $qFuente->where('nombre', 'like', "%{$nombreFuente}%");
            });
        }

        return $query->latest('id_reserva')->paginate(20);
    }

    public function show(Reserva $reserva) {
        return $reserva->load(['cliente','estado','fuente','habitaciones.habitacion','servicios','politicas']);
    }

    public function store(StoreReservaRequest $r) {
        $data = $r->validated();

        return DB::transaction(function () use ($data) {
            // 1) Extraer habitaciones del request
            $habitaciones = $data['habitaciones'];
            unset($data['habitaciones']);

            // 2) Crear la reserva (sin total_monto_reserva todavía)
            $data['fecha_creacion'] = now();
            $data['total_monto_reserva'] = 0; // Se calculará después
            $reserva = Reserva::create($data);

            $totalReserva = 0;

            // 3) Crear habitaciones y calcular precios
            foreach ($habitaciones as $hab) {
                // Validar disponibilidad
                $choqueReserva = ReservaHabitacion::where('id_habitacion', $hab['id_habitacion'])
                    ->where('fecha_llegada', '<', $hab['fecha_salida'])
                    ->where('fecha_salida', '>', $hab['fecha_llegada'])
                    ->exists();

                if ($choqueReserva) {
                    throw new \Exception("La habitación {$hab['id_habitacion']} no está disponible en el rango especificado.");
                }

                // Crear la habitación
                $reservaHab = $reserva->habitaciones()->create([
                    'id_habitacion' => $hab['id_habitacion'],
                    'fecha_llegada' => $hab['fecha_llegada'],
                    'fecha_salida'  => $hab['fecha_salida'],
                    'adultos'       => $hab['adultos'],
                    'ninos'         => $hab['ninos'],
                    'bebes'         => $hab['bebes'],
                    'subtotal'      => 0, // Se calculará a continuación
                ]);

                // Calcular subtotal de esta habitación
                $reservaHab->load('habitacion');
                $subtotal = $reservaHab->calcularSubtotal();
                $reservaHab->update(['subtotal' => $subtotal]);

                $totalReserva += $subtotal;
            }

            // 4) Actualizar total de la reserva
            $reserva->update(['total_monto_reserva' => $totalReserva]);

            // 5) Retornar reserva completa
            return response()->json(
                $reserva->fresh()->load(['cliente', 'estado', 'fuente', 'habitaciones.habitacion.tipoHabitacion']),
                201
            );
        });
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
