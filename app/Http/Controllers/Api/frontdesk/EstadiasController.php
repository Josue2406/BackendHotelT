<?php

namespace App\Http\Controllers\Api\frontdesk;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\frontdesk\Concerns\HabitacionAvailability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Http\Requests\frontdesk\RoomMoveRequest;
use App\Http\Requests\frontdesk\UpdateFechasEstadiaRequest;
use App\Http\Requests\frontdesk\CheckoutRequest;

use App\Http\Resources\frontdesk\EstadiaResource;

use App\Models\estadia\Estadia;
use App\Models\check_in\AsignacionHabitacion;
use App\Models\check_out\CheckOut;

class EstadiasController extends Controller
{
    use HabitacionAvailability;

    /** POST /frontdesk/estadia/{estadia}/room-move */
    public function roomMove(RoomMoveRequest $req, Estadia $estadia)
{
    $data  = $req->validated();

    // Usa SOLO fecha si tus columnas son DATE
    $desde = \Carbon\Carbon::parse($data['desde'] ?? now())->toDateString();
    $hasta = \Carbon\Carbon::parse($estadia->fecha_salida)->toDateString();

    // EXCLUIR la reserva actual para no “chocarte” contigo mismo
    if ($this->hayChoqueHab((int)$data['id_hab_nueva'], $desde, $hasta, $estadia->id_reserva)) {
        return response()->json(['message' => 'La nueva habitación no está disponible en el rango.'], 422);
    }

    return DB::transaction(function () use ($estadia, $data, $desde) {
        $asign = AsignacionHabitacion::create([
            'id_hab'           => $data['id_hab_nueva'],
            'id_reserva'       => $estadia->id_reserva,
            'id_estadia'       => $estadia->id_estadia,
            'origen'           => 'frontdesk',
            'nombre'           => 'Room Move',
            'fecha_asignacion' => $desde, // si tu col es DATE, guarda 'Y-m-d'
            'adultos'          => $data['adultos'] ?? $estadia->adultos,
            'ninos'            => $data['ninos'] ?? $estadia->ninos,
            'bebes'            => $data['bebes'] ?? $estadia->bebes,
        ]);

        return response()->json([
            'estadia'          => $estadia->fresh(),
            'nueva_asignacion' => $asign->fresh(),
        ], 201);
    });
}


    /** PATCH /frontdesk/estadia/{estadia}/fechas */
    public function updateFechas(UpdateFechasEstadiaRequest $req, Estadia $estadia)
{
    $data  = $req->validated();

    $desde = \Carbon\Carbon::parse($data['fecha_llegada'] ?? $estadia->fecha_llegada)->toDateString();
    $hasta = \Carbon\Carbon::parse($data['fecha_salida']  ?? $estadia->fecha_salida)->toDateString();

    $asignaciones = AsignacionHabitacion::where('id_estadia', $estadia->id_estadia)->get();
    foreach ($asignaciones as $a) {
        if ($this->hayChoqueHab((int)$a->id_hab, $desde, $hasta, $estadia->id_reserva)) {
            return response()->json(['message' => 'Alguna habitación asignada no está disponible en el nuevo rango.'], 422);
        }
    }

    $estadia->update([
        'fecha_llegada' => $desde,
        'fecha_salida'  => $hasta,
        'adultos'       => $data['adultos'] ?? $estadia->adultos,
        'ninos'         => $data['ninos']   ?? $estadia->ninos,
        'bebes'         => $data['bebes']   ?? $estadia->bebes,
    ]);

    return $estadia->fresh();
}

    /** POST /frontdesk/estadia/{estadia}/checkout */
   public function checkout(CheckoutRequest $req, Estadia $estadia)
{
    $data = $req->validated();

    $asign = AsignacionHabitacion::where('id_estadia', $estadia->id_estadia)
        ->orderByDesc('fecha_asignacion') // mejor que latest por id
        ->first();

    $checkout = CheckOut::create([
        'id_asignacion' => $asign->id_asignacion ?? null,
        'fecha_hora'    => $data['fecha_hora'] ?? now(),
        'resultado'     => $data['resultado'] ?? 'OK',
    ]);

    return response()->json(['checkout' => $checkout->fresh()], 201);
}


    /** GET /frontdesk/estadia/{estadia} */
    public function show(Estadia $estadia)
    {
        $estadia->load([
  'clienteTitular',
  'fuente',
  'reserva.estado',
  'asignaciones.habitacion.estado',
  'asignaciones.habitacion.tipo',
  'asignaciones.checkIns',   // camelCase del método
  'asignaciones.checkOuts',  // camelCase del método
  'estadoEstadia',
  'folios.estado',
]);


        return new EstadiaResource($estadia);
    }

    /** GET /frontdesk/estadias?fecha=YYYY-MM-DD&estado=in_house|arribos|salidas */
    public function index(Request $req)
    {
        $fecha = $req->input('fecha');
        
        $vista = $req->input('estado');

        $q = Estadia::query();

       if ($fecha) {
  if ($vista === 'arribos') {
    $q->whereDate('fecha_llegada', $fecha);
  } elseif ($vista === 'salidas') {
    $q->whereDate('fecha_salida', $fecha);
  } elseif ($vista === 'in_house') {
    $q->where('fecha_llegada', '<=', $fecha)
      ->where('fecha_salida',  '>',  $fecha); // fin exclusivo
  }
}

        $q->with([
            'clienteTitular',
            'fuente',
            'reserva.estado',
            'asignaciones.habitacion.estado',
            'asignaciones.habitacion.tipo',
            'asignaciones.checkins',
            'asignaciones.checkouts',
            'estadoEstadia',
            'folios.estado',
        ])->latest('id_estadia');

        return EstadiaResource::collection($q->paginate(20));
    }
}
