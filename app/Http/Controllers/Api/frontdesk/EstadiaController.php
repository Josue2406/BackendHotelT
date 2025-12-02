<?php

namespace App\Http\Controllers\Api\frontdesk;

use App\Http\Controllers\Controller;
use App\Models\estadia\Estadia;
use App\Models\check_in\AsignacionHabitacion;
use App\Models\check_out\Folio;
use App\Models\estadia\EstadiaCliente;
use App\Models\cliente\Cliente;
use App\Models\habitacion\Habitacione;
use Illuminate\Http\Request;
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
            'cliente_titular' => $estadia->clienteTitular ? [
                'id_cliente' => $estadia->clienteTitular->id_cliente,
                'nombre' => $estadia->clienteTitular->nombre,
                'apellido1' => $estadia->clienteTitular->apellido1,
                'apellido2' => $estadia->clienteTitular->apellido2,
                'email' => $estadia->clienteTitular->email,
                'telefono' => $estadia->clienteTitular->telefono,
            ] : null,
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

    /**
     * GET /api/frontdesk/estadia/walkin/{codigo}
     * 
     * Busca una estadía de Walk-In por el código en el nombre de asignación
     * Ejemplo: WI-20251129-A1B2
     */
    public function showByWalkIn($codigo)
    {
        // 1️⃣ Buscar la asignación que contenga el código de walk-in
        $asignacion = AsignacionHabitacion::where('nombre', 'LIKE', "%{$codigo}%")
            ->where('origen', 'frontdesk')
            ->whereNull('id_reserva') // Walk-ins no tienen reserva
            ->first();

        if (!$asignacion) {
            return response()->json([
                'message' => "No se encontró un Walk-In con el código {$codigo}."
            ], 404);
        }

        // 2️⃣ Obtener la estadía asociada
        $estadia = Estadia::with([
            'estado:id_estado_estadia,nombre,codigo',
            'clienteTitular:id_cliente,nombre,apellido1,apellido2,email,telefono',
        ])->find($asignacion->id_estadia);

        if (!$estadia) {
            return response()->json([
                'message' => "No se encontró la estadía asociada al Walk-In."
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

        // 4️⃣ Buscar folio
        $folio = Folio::where('id_estadia', $estadia->id_estadia)->first();

        // 5️⃣ Respuesta
        return response()->json([
            'message' => 'Walk-In encontrado correctamente.',
            'codigo_walkin' => $codigo,
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
                'cliente_titular' => $estadia->clienteTitular ? [
                    'id_cliente' => $estadia->clienteTitular->id_cliente,
                    'nombre' => $estadia->clienteTitular->nombre,
                    'apellido1' => $estadia->clienteTitular->apellido1,
                    'apellido2' => $estadia->clienteTitular->apellido2,
                    'email' => $estadia->clienteTitular->email,
                    'telefono' => $estadia->clienteTitular->telefono,
                ] : null,
            ],
            'acompanantes' => $acompanantes,
            'asignacion' => [
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
            ],
            'folio' => $folio?->id_folio,
            'checkin_at' => $asignacion->created_at->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * GET /api/frontdesk/estadias
     * 
     * Lista estadías con filtros opcionales:
     * - fecha: fecha para filtrar (default: hoy)
     * - estado: in_house|arribos|salidas (default: in_house)
     * - search: búsqueda por código, nombre de cliente, email
     * - habitacion: filtro por número de habitación
     */
    public function index(Request $request)
    {
        $fecha = $request->input('fecha', now()->format('Y-m-d'));
        $estado = $request->input('estado', 'in_house');
        $search = $request->input('search', '');
        $habitacion = $request->input('habitacion', '');
        $origen = $request->input('origen', ''); // nuevo filtro: 'walkin', 'reserva', o vacío para todos

        // Query base: todas las estadías con sus relaciones
        $query = Estadia::with([
            'estado:id_estado_estadia,nombre,codigo',
            'clienteTitular:id_cliente,nombre,apellido1,apellido2,email,telefono',
        ]);

        // Filtro por origen
        if ($origen === 'walkin') {
            $query->whereNull('id_reserva');
        } elseif ($origen === 'reserva') {
            $query->whereNotNull('id_reserva');
        }

        // Aplicar filtro de estado
        switch ($estado) {
            case 'arribos':
                // Llegadas en la fecha seleccionada
                $query->whereDate('fecha_llegada', $fecha);
                break;
            case 'salidas':
                // Salidas en la fecha seleccionada
                $query->whereDate('fecha_salida', $fecha);
                break;
            case 'in_house':
                // In-house: cualquier estadía que esté activa
                // Opción 1: Mostrar solo las que están realmente en el hotel hoy
                // $query->whereDate('fecha_llegada', '<=', $fecha)
                //       ->whereDate('fecha_salida', '>=', $fecha);
                
                // Opción 2: Mostrar todas las estadías sin importar fecha (más permisivo)
                // No aplicar filtro de fecha para ver todas
                break;
            case 'todas':
                // Sin filtros de fecha
                break;
            default:
                // Por defecto mostrar in-house reales
                $query->whereDate('fecha_llegada', '<=', $fecha)
                      ->whereDate('fecha_salida', '>=', $fecha);
                break;
        }

        // Búsqueda por texto (código, nombre, email)
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->whereHas('clienteTitular', function($subQ) use ($search) {
                    $subQ->where('nombre', 'LIKE', "%{$search}%")
                         ->orWhere('apellido1', 'LIKE', "%{$search}%")
                         ->orWhere('email', 'LIKE', "%{$search}%");
                })
                // Buscar en códigos de walk-in (en asignación)
                ->orWhereHas('asignaciones', function($subQ) use ($search) {
                    $subQ->where('nombre', 'LIKE', "%{$search}%");
                })
                // Buscar en código de reserva
                ->orWhereHas('reserva', function($subQ) use ($search) {
                    $subQ->where('codigo_reserva', 'LIKE', "%{$search}%");
                });
            });
        }

        $estadias = $query->get();

        // Enriquecer con datos adicionales
        $estadias = $estadias->map(function($estadia) use ($habitacion) {
            // Obtener última asignación
            $asignacion = AsignacionHabitacion::where('id_estadia', $estadia->id_estadia)
                ->with('habitacion:id_habitacion,numero')
                ->latest('fecha_asignacion')
                ->first();

            // Si hay filtro de habitación y no coincide, retornar null (lo filtraremos después)
            if ($habitacion && $asignacion && $asignacion->habitacion) {
                if ($asignacion->habitacion->numero != $habitacion) {
                    return null;
                }
            } elseif ($habitacion) {
                return null;
            }

            // Obtener folio
            $folio = Folio::where('id_estadia', $estadia->id_estadia)->first();

            // Determinar origen y código de referencia
            if (!$estadia->id_reserva) {
                // Es un walk-in
                $origen = 'walkin';
                // El código está en asignacion.nombre
                $codigoReferencia = $asignacion && $asignacion->nombre 
                    ? $asignacion->nombre 
                    : 'EST-' . $estadia->id_estadia;
            } else {
                // Es una reserva
                $origen = 'reserva';
                $reserva = \App\Models\reserva\Reserva::find($estadia->id_reserva);
                $codigoReferencia = $reserva && $reserva->codigo_reserva 
                    ? $reserva->codigo_reserva 
                    : 'EST-' . $estadia->id_estadia;
            }

            return [
                'id_estadia' => $estadia->id_estadia,
                'id_reserva' => $estadia->id_reserva,
                'origen' => $origen,
                'codigo_referencia' => $codigoReferencia,
                'fecha_llegada' => $estadia->fecha_llegada,
                'fecha_salida' => $estadia->fecha_salida,
                'adultos' => $estadia->adultos,
                'ninos' => $estadia->ninos,
                'bebes' => $estadia->bebes,
                'estado' => $estadia->estado,
                'cliente' => $estadia->clienteTitular ? [
                    'id_cliente' => $estadia->clienteTitular->id_cliente,
                    'nombre' => $estadia->clienteTitular->nombre,
                    'apellido1' => $estadia->clienteTitular->apellido1,
                    'apellido2' => $estadia->clienteTitular->apellido2,
                    'nombre_completo' => trim("{$estadia->clienteTitular->nombre} {$estadia->clienteTitular->apellido1} {$estadia->clienteTitular->apellido2}"),
                    'email' => $estadia->clienteTitular->email,
                    'telefono' => $estadia->clienteTitular->telefono,
                ] : null,
                'habitacion' => $asignacion && $asignacion->habitacion ? [
                    'id_habitacion' => $asignacion->habitacion->id_habitacion,
                    'numero' => $asignacion->habitacion->numero,
                ] : null,
                'asignacion' => $asignacion ? [
                    'id_asignacion' => $asignacion->id_asignacion,
                    'fecha_asignacion' => $asignacion->fecha_asignacion,
                    'checkin_at' => $asignacion->created_at,
                ] : null,
                'folio_id' => $folio?->id_folio,
            ];
        })->filter()->values(); // Eliminar nulos y re-indexar

        return response()->json([
            'message' => 'Estadías obtenidas correctamente.',
            'estadias' => $estadias,
            'filtros' => [
                'fecha' => $fecha,
                'estado' => $estado,
                'search' => $search,
                'habitacion' => $habitacion,
            ],
            'total' => $estadias->count(),
        ]);
    }


}
