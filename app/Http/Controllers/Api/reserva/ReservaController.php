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
use App\Models\reserva\EstadoReserva;
use App\Models\estadia\Estadia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Notifications\ReservaCreada;
use App\Notifications\ReservaActualizada;
use App\Notifications\ReservaCancelada;
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

    // public function store(StoreReservaRequest $r) {
    //     $data = $r->validated();

    //     // Devolvemos el ID para poder notificar tras el commit
    //     $reservaId = DB::transaction(function () use ($data) {
    //         $habitaciones = $data['habitaciones'];
    //         unset($data['habitaciones']);

    //         $data['fecha_creacion'] = now();
    //         $data['total_monto_reserva'] = 0;
    //         $reserva = Reserva::create($data);

    //         $totalReserva = 0;

    //         foreach ($habitaciones as $hab) {
    //             $choqueReserva = ReservaHabitacion::where('id_habitacion', $hab['id_habitacion'])
    //                 ->where('fecha_llegada', '<', $hab['fecha_salida'])
    //                 ->where('fecha_salida', '>', $hab['fecha_llegada'])
    //                 ->exists();

    //             if ($choqueReserva) {
    //                 throw new \Exception("La habitación {$hab['id_habitacion']} no está disponible en el rango especificado.");
    //             }

    //             $reservaHab = $reserva->habitaciones()->create([
    //                 'id_habitacion' => $hab['id_habitacion'],
    //                 'fecha_llegada' => $hab['fecha_llegada'],
    //                 'fecha_salida'  => $hab['fecha_salida'],
    //                 'adultos'       => $hab['adultos'],
    //                 'ninos'         => $hab['ninos'],
    //                 'bebes'         => $hab['bebes'],
    //                 'subtotal'      => 0,
    //             ]);

    //             $reservaHab->load('habitacion');
    //             $subtotal = $reservaHab->calcularSubtotal();
    //             $reservaHab->update(['subtotal' => $subtotal]);

    //             $totalReserva += $subtotal;
    //         }

    //         $reserva->update(['total_monto_reserva' => $totalReserva]);

    //         // Notificar SOLO después de que la transacción haya sido confirmada
    //         DB::afterCommit(function () use ($reserva) {
    //             $fresh = $reserva->fresh()->load(['cliente','estado','fuente','habitaciones.habitacion.tipoHabitacion']);
    //             if ($fresh->cliente?->email) {
    //                 $fresh->cliente->notify(new ReservaCreada($fresh));
    //             }
    //         });

    //         return $reserva->id_reserva;
    //     });

    //     // Respuesta consistente con lo que ya retornabas
    //     $reserva = Reserva::with(['cliente','estado','fuente','habitaciones.habitacion.tipoHabitacion'])
    //         ->findOrFail($reservaId);

    //     return response()->json($reserva, 201);
    // }
    public function store(StoreReservaRequest $r)
{
    // 1) Cliente autenticado vía Sanctum
    $cliente = $r->user(); // o $r->user('cliente') si usas varios providers
    if (!$cliente) {
        return response()->json(['message' => 'No autenticado'], 401);
    }

    // 2) Validación y forzar que la reserva pertenezca al cliente autenticado
    $data = $r->validated();
    unset($data['id_cliente']);                 // no confiar en el body
    $data['id_cliente']   = $cliente->id_cliente;
    $data['fecha_creacion']      = now();
    $data['total_monto_reserva'] = 0;

    // 3) Crear reserva + habitaciones dentro de transacción
    $reservaId = DB::transaction(function () use ($data) {
        $habitaciones = $data['habitaciones'];
        unset($data['habitaciones']);

        $reserva = Reserva::create($data);

        $totalReserva = 0;
        foreach ($habitaciones as $hab) {
            // disponibilidad
            $choqueReserva = \App\Models\reserva\ReservaHabitacion::where('id_habitacion', $hab['id_habitacion'])
                ->where('fecha_llegada', '<', $hab['fecha_salida'])
                ->where('fecha_salida', '>', $hab['fecha_llegada'])
                ->exists();

            if ($choqueReserva) {
                throw new \Exception("La habitación {$hab['id_habitacion']} no está disponible en el rango especificado.");
            }

            // crear item
            $reservaHab = $reserva->habitaciones()->create([
                'id_habitacion' => $hab['id_habitacion'],
                'fecha_llegada' => $hab['fecha_llegada'],
                'fecha_salida'  => $hab['fecha_salida'],
                'adultos'       => $hab['adultos'],
                'ninos'         => $hab['ninos'],
                'bebes'         => $hab['bebes'],
                'subtotal'      => 0,
            ]);

            // calcular subtotal
            $reservaHab->load('habitacion');
            $subtotal = $reservaHab->calcularSubtotal();
            $reservaHab->update(['subtotal' => $subtotal]);
            $totalReserva += $subtotal;
        }

        // total de la reserva
        $reserva->update(['total_monto_reserva' => $totalReserva]);

        // 4) Enviar el correo SOLO después de commit
        DB::afterCommit(function () use ($reserva) {
            $fresh = $reserva->fresh()->load([
                'cliente',
                'estado',
                'fuente',
                'habitaciones.habitacion.tipoHabitacion'
            ]);

            if ($fresh->cliente?->email) {
                $fresh->cliente->notify(new \App\Notifications\ReservaCreada($fresh));
            }
        });

        return $reserva->id_reserva;
    });

    // 5) Respuesta
    $reserva = Reserva::with(['cliente','estado','fuente','habitaciones.habitacion.tipoHabitacion'])
        ->findOrFail($reservaId);

    return response()->json($reserva, 201);
}


    public function update(UpdateReservaRequest $r, Reserva $reserva) {
        $reserva->update($r->validated());
        return $reserva->fresh();
    }
    /* 
    public function update(UpdateReservaRequest $r, Reserva $reserva) {
        // Guardamos cambios y notificamos
        $original = $reserva->replicate();
        $reserva->update($r->validated());

        $cambios = $reserva->getChanges();
        unset($cambios['updated_at']);

        $reservaFresh = $reserva->fresh()->load(['cliente']);

        if ($reservaFresh->cliente?->email) {
            $reservaFresh->cliente->notify(new \App\Notifications\ReservaActualizada($reservaFresh, $cambios));
        }

        return $reservaFresh;
    }
    */

    public function destroy(Reserva $reserva) {
        // si hay FKs dependientes, podrías impedir borrar o hacer soft delete
        $reserva->delete();
        return response()->noContent();
    }

    // ===== Acciones =====

    public function confirmar(Reserva $reserva) {
        // Confirmar la reserva - El Observer manejará el estado de las habitaciones
        $reserva->update(['id_estado_res' => EstadoReserva::ESTADO_CONFIRMADA]);
        return $reserva->fresh(['habitaciones.habitacion.estado']);
    }

    public function cancelar(CancelReservaRequest $r, Reserva $reserva) {
        // 1) marcar estado cancelada - El Observer se encargará de liberar las habitaciones
        $reserva->update(['id_estado_res' => EstadoReserva::ESTADO_CANCELADA]);

        // 2) (Opcional) aplicar política si existe y si corresponde la ventana:
        // - Busca reserva_politica -> politica_cancelacion
        // - Evalúa ventana vs fecha_llegada más próxima en reserva_habitacions
        // - Calcula penalidad (porcentaje | noches) y *si quieres* registra un cargo en folio/reserva_pago

        // Recargar la reserva con las habitaciones actualizadas
        $reserva->load('habitaciones.habitacion.estado');

        return response()->json([
            'ok' => true,
            'message' => 'Reserva cancelada exitosamente. Las habitaciones han sido liberadas.',
            'reserva' => $reserva
        ]);
    }
    /* 
    public function cancelar(CancelReservaRequest $r, Reserva $reserva) {
        // Cambia estado a cancelada (ajusta el ID a tu catálogo)
        $reserva->update(['id_estado_res' =>  id estado cancelada  3]);

        // (Opcional) aplicar política de cancelación aquí...

        $reservaFresh = $reserva->fresh()->load(['cliente','habitaciones.habitacion','estado']);

        if ($reservaFresh->cliente?->email) {
            $reservaFresh->cliente->notify(new \App\Notifications\ReservaCancelada($reservaFresh));
        }

        return response()->json(['ok' => true]);
    }
    */

    public function noShow(Reserva $reserva) {
        $reserva->update(['id_estado_res' => EstadoReserva::ESTADO_NO_SHOW]);
        // (Opcional) aplicar penalidad de no-show según política
        // El Observer liberará las habitaciones automáticamente
        return $reserva->fresh(['habitaciones.habitacion.estado']);
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
