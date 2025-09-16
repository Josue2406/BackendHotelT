<?php

namespace App\Http\Controllers\Api\frontdesk;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\frontdesk\Concerns\HabitacionAvailability;
use Illuminate\Support\Facades\DB;

use App\Http\Requests\frontdesk\StoreWalkInRequest;
use App\Models\estadia\Estadia;
use App\Models\check_in\AsignacionHabitacion;
use App\Models\check_in\CheckIn;

class WalkInsController extends Controller
{
    use HabitacionAvailability;

    /** POST /frontdesk/walkin */
    public function store(StoreWalkInRequest $req)
    {
        $data  = $req->validated();
        $desde = $data['fecha_llegada'];
        $hasta = $data['fecha_salida'];

        if ($this->hayChoqueHab($data['id_hab'], $desde, $hasta)) {
            return response()->json(['message' => 'La habitación no está disponible en el rango.'], 422);
        }

        return DB::transaction(function () use ($data) {
            $estadia = Estadia::create([
                'id_reserva'         => null,
                'id_cliente_titular' => $data['id_cliente_titular'],
                'id_fuente'          => $data['id_fuente'] ?? null,
                'fecha_llegada'      => $data['fecha_llegada'],
                'fecha_salida'       => $data['fecha_salida'],
                'adultos'            => $data['adultos'],
                'ninos'              => $data['ninos'] ?? 0,
                'bebes'              => $data['bebes'] ?? 0,
                'id_estado_estadia'  => $data['id_estado_estadia'] ?? null,
            ]);

            $asign = AsignacionHabitacion::create([
                'id_hab'           => $data['id_hab'],
                'id_reserva'       => null,
                'id_estadia'       => $estadia->id_estadia,
                'origen'           => 'frontdesk',
                'nombre'           => $data['nombre_asignacion'] ?? 'Asignación',
                'fecha_asignacion' => $data['fecha_llegada'],
                'adultos'          => $data['adultos'],
                'ninos'            => $data['ninos'] ?? 0,
                'bebes'            => $data['bebes'] ?? 0,
            ]);

            CheckIn::create([
                'id_asignacion' => $asign->id_asignacion,
                'fecha_hora'    => now(),
                'obervacion'    => $data['observacion_checkin'] ?? null, // campo en DB
            ]);

            return response()->json([
                'estadia'    => $estadia->fresh(),
                'asignacion' => $asign->fresh(),
                'checkin_at' => now()->toDateTimeString(),
            ], 201);
        });
    }
}
