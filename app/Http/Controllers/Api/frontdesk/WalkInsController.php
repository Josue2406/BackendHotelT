<?php
// App/Http/Controllers/Api/frontdesk/WalkInsController.php
namespace App\Http\Controllers\Api\frontdesk;

use App\Http\Controllers\Controller;
use App\Http\Requests\frontdesk\WalkinStoreRequest;
use Illuminate\Support\Facades\DB;
use App\Models\cliente\Cliente;
use App\Models\hotel\Habitacion;
use App\Models\estadia\Estadia;
use App\Models\check_in\AsignacionHabitacion;
use App\Models\check_in\CheckIn;

class WalkInsController extends Controller
{
    use \App\Http\Controllers\Api\frontdesk\Concerns\HabitacionAvailability;

    /** POST /frontdesk/walkin */
    public function store(WalkinStoreRequest $req)
    {
        $data  = $req->validated();

        // 1) Resolver huésped
        if (!empty($data['id_cliente'])) {
            $cliente = Cliente::findOrFail((int)$data['id_cliente']);
        } else {
            $cliente = Cliente::where('cedula', $data['cedula'])->first();
            if (!$cliente) {
                return response()->json(['message' => 'No se encontró un cliente con esa cédula.'], 404);
            }
        }

        // 2) Validar que la habitación pertenezca al tipo elegido
        $habitacion = Habitacion::findOrFail((int)$data['id_hab']);
        if ((int)$habitacion->id_tipo_hab !== (int)$data['id_tipo_hab']) {
            return response()->json(['message' => 'La habitación seleccionada no coincide con el tipo elegido.'], 422);
        }

        // 3) Anti-carrera: verificar disponibilidad en el rango
        $desde = $data['fecha_llegada'];
        $hasta = $data['fecha_salida'];
        if ($this->hayChoqueHab((int)$data['id_hab'], $desde, $hasta, /* id_reserva = */ null)) {
            return response()->json(['message' => 'La habitación no está disponible en el rango indicado.'], 422);
        }

        // 4) Crear todo en transacción
        return DB::transaction(function () use ($data, $cliente, $desde, $hasta) {

            $estadia = Estadia::create([
                'id_reserva'         => null, // Walk-in no viene de reserva
                'id_cliente_titular' => $cliente->id_cliente,
                'id_fuente'          => null,  // o fuente "Walk-in" si tienes catálogo
                'fecha_llegada'      => $desde,
                'fecha_salida'       => $hasta,
                'adultos'            => $data['adultos'] ?? null,
                'ninos'              => $data['ninos']   ?? null,
                'bebes'              => $data['bebes']   ?? null,
                'id_estado_estadia'  => null,
            ]);

            $asign = AsignacionHabitacion::create([
                'id_hab'           => $data['id_hab'],
                'id_reserva'       => null,
                'id_estadia'       => $estadia->id_estadia,
                'origen'           => 'frontdesk',
                'nombre'           => 'Walk-in', // ← sin nombre_asignacion del body
                'fecha_asignacion' => $desde,
                'adultos'          => $data['adultos'] ?? null,
                'ninos'            => $data['ninos']   ?? null,
                'bebes'            => $data['bebes']   ?? null,
            ]);

            CheckIn::create([
                'id_asignacion' => $asign->id_asignacion,
                'fecha_hora'    => now(),
                'obervacion'    => $data['observacion_checkin'] ?? null, // corrige nombre si es 'observacion'
            ]);

            return response()->json([
                'cliente'    => $cliente->only(['id_cliente','nombre','apellidos','cedula','correo']),
                'estadia'    => $estadia->fresh(),
                'asignacion' => $asign->fresh(),
                'checkin_at' => now()->toDateTimeString(),
            ], 201);
        });
    }
}
