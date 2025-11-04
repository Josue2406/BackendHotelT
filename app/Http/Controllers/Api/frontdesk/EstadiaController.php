<?php

namespace App\Http\Controllers\Api\frontdesk;

use App\Http\Controllers\Controller;
use App\Models\estadia\Estadia;
use App\Models\check_in\AsignacionHabitacion;
use App\Models\check_out\Folio;
use App\Models\estadia\EstadiaCliente;
use App\Models\cliente\Cliente;
use Illuminate\Support\Facades\DB;

class EstadiaController extends Controller
{
    /**
     * GET /api/frontdesk/estadia/{id}
     * 
     * Devuelve detalle completo de la estadía,
     * igual al formato del Check-in.
     */
    public function show($id)
    {
        $estadia = Estadia::with([
            'estado:id_estado_estadia,nombre,codigo',
            'clienteTitular:id_cliente,nombre,apellido1,apellido2,email,telefono',
        ])->find($id);

        if (!$estadia) {
            return response()->json([
                'message' => "Estadía no encontrada con ID $id."
            ], 404);
        }

        // 🔹 Obtener acompañantes
        $acompanantes = EstadiaCliente::where('id_estadia', $estadia->id_estadia)
            ->where('rol', 'ACOMPANANTE')
            ->with('cliente:id_cliente,nombre,apellido1,email')
            ->get()
            ->map(function ($a) {
                return [
                    'id_cliente' => $a->cliente->id_cliente,
                    'nombre' => $a->cliente->nombre,
                    'apellido1' => $a->cliente->apellido1,
                    'email' => $a->cliente->email,
                    'folio_asociado' => null,
                ];
            });

        // 🔹 Obtener última asignación
        $asignacion = AsignacionHabitacion::where('id_estadia', $estadia->id_estadia)
            ->latest('fecha_asignacion')
            ->first();

        // 🔹 Obtener folio
        $folio = Folio::where('id_estadia', $estadia->id_estadia)->first();

        return response()->json([
            'message' => 'Detalle de estadía obtenido correctamente.',
            'estadia' => [
                'id_estadia' => $estadia->id_estadia,
                'id_reserva' => $estadia->id_reserva,
                'id_cliente_titular' => $estadia->id_cliente_titular,
                'id_fuente' => $estadia->id_fuente,
                'fecha_llegada' => $estadia->fecha_llegada,
                'fecha_salida' => $estadia->fecha_salida,
                'adultos' => $estadia->adultos,
                'ninos' => $estadia->ninos,
                'bebes' => $estadia->bebes,
                'id_estado_estadia' => $estadia->id_estado_estadia,
                'created_at' => $estadia->created_at,
                'updated_at' => $estadia->updated_at,
                'estado' => $estadia->estado,
            ],
            'acompanantes' => $acompanantes,
            'asignacion' => $asignacion ? [
                'id_asignacion' => $asignacion->id_asignacion,
                'id_hab' => $asignacion->id_habitacion ?? $asignacion->id_hab, // tolera ambos nombres
                'id_reserva' => $asignacion->id_reserva,
                'id_estadia' => $asignacion->id_estadia,
                'origen' => $asignacion->origen,
                'nombre' => $asignacion->nombre,
                'fecha_asignacion' => $asignacion->fecha_asignacion,
                'adultos' => $asignacion->adultos,
                'ninos' => $asignacion->ninos,
                'bebes' => $asignacion->bebes,
                'created_at' => $asignacion->created_at,
                'updated_at' => $asignacion->updated_at,
            ] : null,
            'folio' => $folio?->id_folio,
            'checkin_at' => optional($asignacion)->created_at ? $asignacion->created_at->format('Y-m-d H:i:s') : null,
        ]);
    }

public function showByReserva($codigo)
{
    // 1️⃣ Buscar la reserva por código
    $reserva = \App\Models\reserva\Reserva::where('codigo_reserva', $codigo)->first();

    if (!$reserva) {
        return response()->json([
            'message' => "No se encontró una reserva con el código {$codigo}."
        ], 404);
    }

    // 2️⃣ Buscar la estadía vinculada a la reserva
    $estadia = \App\Models\estadia\Estadia::with([
        'estado:id_estado_estadia,nombre,codigo',
        'clienteTitular:id_cliente,nombre,apellido1,apellido2,email,telefono',
    ])->where('id_reserva', $reserva->id_reserva)->first();

    if (!$estadia) {
        return response()->json([
            'message' => "No existe una estadía generada aún para la reserva con código {$codigo}.",
            'id_reserva' => $reserva->id_reserva
        ], 404);
    }

    // 3️⃣ Buscar acompañantes
    $acompanantes = \App\Models\estadia\EstadiaCliente::where('id_estadia', $estadia->id_estadia)
        ->where('rol', 'ACOMPANANTE')
        ->with('cliente:id_cliente,nombre,apellido1,email')
        ->get()
        ->map(fn($a) => [
            'id_cliente' => $a->cliente->id_cliente,
            'nombre' => $a->cliente->nombre,
            'apellido1' => $a->cliente->apellido1,
            'email' => $a->cliente->email,
            'folio_asociado' => null,
        ]);

    // 4️⃣ Buscar asignación activa o más reciente
    $asignacion = \App\Models\check_in\AsignacionHabitacion::where('id_estadia', $estadia->id_estadia)
        ->latest('fecha_asignacion')
        ->first();

    // 5️⃣ Buscar el folio asociado de forma robusta
    $folio = \App\Models\check_out\Folio::where('id_estadia', $estadia->id_estadia)
        ->orWhere('id_reserva_hab', function ($q) use ($reserva) {
            $q->select('id_reserva_hab')
              ->from('reserva_habitacions')
              ->where('id_reserva', $reserva->id_reserva)
              ->limit(1);
        })
        ->first();

    // 6️⃣ Fallback — si no hay folio, crear uno automáticamente
    if (!$folio) {
        $idReservaHab = \DB::table('reserva_habitacions')
            ->where('id_reserva', $reserva->id_reserva)
            ->value('id_reserva_hab');

        $folio = \App\Models\check_out\Folio::create([
            'id_estadia'      => $estadia->id_estadia,
            'id_reserva_hab'  => $idReservaHab,
            'id_estado_folio' => \App\Models\check_out\EstadoFolio::ABIERTO,
            'total'           => 0.0,
        ]);
    }

    // 7️⃣ Respuesta final (mismo formato que el Check-In)
    return response()->json([
        'message' => 'Detalle de estadía obtenido correctamente.',
        'estadia' => [
            'id_estadia' => $estadia->id_estadia,
            'id_reserva' => $estadia->id_reserva,
            'id_cliente_titular' => $estadia->id_cliente_titular,
            'id_fuente' => $estadia->id_fuente,
            'fecha_llegada' => $estadia->fecha_llegada,
            'fecha_salida' => $estadia->fecha_salida,
            'adultos' => $estadia->adultos,
            'ninos' => $estadia->ninos,
            'bebes' => $estadia->bebes,
            'id_estado_estadia' => $estadia->id_estado_estadia,
            'created_at' => $estadia->created_at,
            'updated_at' => $estadia->updated_at,
            'estado' => $estadia->estado,
        ],
        'acompanantes' => $acompanantes,
        'asignacion' => $asignacion ? [
            'id_asignacion' => $asignacion->id_asignacion,
            'id_hab' => $asignacion->id_habitacion ?? $asignacion->id_hab,
            'id_reserva' => $asignacion->id_reserva,
            'id_estadia' => $asignacion->id_estadia,
            'origen' => $asignacion->origen,
            'nombre' => $asignacion->nombre,
            'fecha_asignacion' => $asignacion->fecha_asignacion,
            'adultos' => $asignacion->adultos,
            'ninos' => $asignacion->ninos,
            'bebes' => $asignacion->bebes,
            'created_at' => $asignacion->created_at,
            'updated_at' => $asignacion->updated_at,
        ] : null,
        'folio' => $folio?->id_folio,
        'checkin_at' => optional($asignacion)->created_at
            ? $asignacion->created_at->format('Y-m-d H:i:s')
            : null,
    ]);
}



}
