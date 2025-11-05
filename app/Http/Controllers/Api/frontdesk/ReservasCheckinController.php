<?php

namespace App\Http\Controllers\Api\frontdesk;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\frontdesk\Concerns\HabitacionAvailability;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\frontdesk\CheckinFromReservaRequest;

use App\Models\reserva\Reserva;
use App\Models\estadia\Estadia;
use App\Models\check_in\AsignacionHabitacion;
use App\Models\check_in\CheckIn;
use App\Models\cliente\Cliente;
use App\Models\estadia\EstadiaCliente;
use App\Models\reserva\EstadoReserva;
use App\Models\estadia\EstadoEstadia;
use App\Models\check_out\Folio;
use App\Models\check_out\EstadoFolio;

class ReservasCheckinController extends Controller
{
    use HabitacionAvailability;

    /**
     * POST /frontdesk/reserva/{reserva}/checkin
     */
    public function store(CheckinFromReservaRequest $req, Reserva $reserva)
    {
        $data  = $req->validated();
        $desde = $data['fecha_llegada'];
        $hasta = $data['fecha_salida'];

        // ============================
        // 0️⃣ Validar estado de la reserva
        
        if ($reserva->id_estado_res !== EstadoReserva::ESTADO_CONFIRMADA) {
      return response()->json([
        'message' => "Solo las reservas con estado 'Confirmada' pueden realizar el check-in.",
        'estado_actual' => EstadoReserva::getNombreEstado($reserva->id_estado_res),
       ], 422);
        }


        // ============================
        // 🔹 Verificar disponibilidad de habitación
        // ============================
        if ($this->hayChoqueHab((int)$data['id_hab'], $desde, $hasta, $reserva->id_reserva)) {
            return response()->json(['message' => 'La habitación no está disponible en el rango.'], 422);
        }

        // ============================
        // 💾 Transacción principal
        // ============================
        return DB::transaction(function () use ($reserva, $data) {

            // 1️⃣ Crear estadía principal
            $estadia = Estadia::create([
                'id_reserva'         => $reserva->id_reserva,
                'id_cliente_titular' => $data['id_cliente_titular'],
                'id_fuente'          => $data['id_fuente'] ?? $reserva->id_fuente,
                'fecha_llegada'      => $data['fecha_llegada'],
                'fecha_salida'       => $data['fecha_salida'],
                'adultos'            => $data['adultos'] ?? 1,
                'ninos'              => $data['ninos'] ?? 0,
                'bebes'              => $data['bebes'] ?? 0,
                'id_estado_estadia'  => EstadoEstadia::ACTIVA,
            ]);

            // 2️⃣ Actualizar estados
            $reserva->update(['id_estado_res' => EstadoReserva::ESTADO_CHECKIN]);
            $estadia->update(['id_estado_estadia' => EstadoEstadia::ACTIVA]);

            // 3️⃣ Registrar titular
            EstadiaCliente::create([
                'id_estadia' => $estadia->id_estadia,
                'id_cliente' => $data['id_cliente_titular'],
                'rol'        => 'TITULAR'
            ]);

            // 4️⃣ Registrar acompañantes
            $acompanantesCreados = [];

            if (!empty($data['acompanantes'])) {
                foreach ($data['acompanantes'] as $a) {
                    $cliente = Cliente::where('numero_doc', $a['documento'])->first();

                    if (!$cliente) {
                        $cliente = Cliente::create([
                            'numero_doc' => $a['documento'],
                            'nombre'     => $a['nombre'],
                            'apellido1'  => $a['apellido1'] ?? 'Sin apellido',
                            'apellido2'  => $a['apellido2'] ?? '',
                            'email'      => $a['email'] ?? 'sin-email@hotel.local',
                            'telefono'   => $a['telefono'] ?? null,
                        ]);
                    }

                    EstadiaCliente::updateOrCreate(
                        ['id_estadia' => $estadia->id_estadia, 'id_cliente' => $cliente->id_cliente],
                        ['rol' => 'ACOMPANANTE']
                    );

                    $acompanantesCreados[] = [
                        'id_cliente' => $cliente->id_cliente,
                        'nombre'     => $cliente->nombre,
                        'apellido1'  => $cliente->apellido1,
                        'email'      => $cliente->email,
                        'folio_asociado' => null,
                    ];
                }
            }

            // 5️⃣ Crear asignación de habitación
           $asign = AsignacionHabitacion::create([
    'id_hab'           => $data['id_hab'],
    'id_reserva'       => $reserva->id_reserva,
    'id_estadia'       => $estadia->id_estadia,
    'origen'           => 'frontdesk',
    'nombre'           => $data['nombre_asignacion'] ?? 'Asignación desde FrontDesk',
    'fecha_asignacion' => $data['fecha_llegada'],
    'adultos'          => $data['adultos'] ?? 1,
    'ninos'            => $data['ninos'] ?? 0,
    'bebes'            => $data['bebes'] ?? 0, // ✅ evita null
         ]);


            // 6️⃣ Registrar Check-in
            CheckIn::create([
                'id_asignacion' => $asign->id_asignacion,
                'fecha_hora'    => now(),
                'observacion'   => $data['observacion_checkin'] ?? null,
              ]);

            // ================================================
            // 7️⃣ Crear Folio asociado
            // ================================================
            $idReservaHab = DB::table('reserva_habitacions')
    ->where('id_reserva', $reserva->id_reserva)
    ->value('id_reserva_hab');

$folio = Folio::firstOrCreate(
    [
        'id_estadia'     => $estadia->id_estadia,
        'id_reserva_hab' => $idReservaHab
    ],
    [
        'id_estado_folio' => EstadoFolio::ABIERTO,
        'total'           => 0.0,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]
);

// ================================================
// 8️⃣ Registrar crédito inicial o depósito de respaldo
// ================================================
$creditoInicial = $data['credito_inicial'] ?? 0.0; // opcional desde el frontend
$metodoPago     = $data['metodo_pago'] ?? 'Tarjeta'; // opcional (Tarjeta / Efectivo / Transferencia)

if ($creditoInicial > 0) {
    // Insertar movimiento en folio_linea (saldo inicial)
    DB::table('folio_linea')->insert([
        'id_folio'    => $folio->id_folio,
        'id_cliente'  => $data['id_cliente_titular'],
        'descripcion' => 'Crédito inicial o depósito de respaldo',
        'monto'       => $creditoInicial,
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    // Registrar operación contable (folio_operacion)
    DB::table('folio_operacion')->insert([
        'id_folio'      => $folio->id_folio,
        'operacion_uid' => 'credito-' . uniqid(),
        'tipo'          => 'credito_inicial',
        'total'         => $creditoInicial,
        'payload'       => json_encode([
            'metodo' => $metodoPago,
            'nota'   => 'Depósito inicial de respaldo registrado en check-in',
        ], JSON_UNESCAPED_UNICODE),
        'created_at'    => now(),
        'updated_at'    => now(),
    ]);

    // Actualizar total del folio
    $folio->update([
        'total' => DB::raw("total + $creditoInicial")
    ]);
}

// ================================================
// 9️⃣ Aplicar distribución automática de cargos
// ================================================
$pagoModo = $data['pago_modo'] ?? 'general'; // 'general' o 'por_persona'

if ($pagoModo === 'por_persona' && !empty($acompanantesCreados)) {
    $resumen = DB::table('vw_folio_resumen')->where('id_folio', $folio->id_folio)->first();

    if ($resumen && (float)$resumen->cargos_sin_persona > 0) {
        $clientes = collect($acompanantesCreados)
            ->pluck('id_cliente')
            ->prepend($data['id_cliente_titular'])
            ->map(fn($id) => ['id_cliente' => $id])
            ->values()
            ->toArray();

        $distribucionRequest = new \Illuminate\Http\Request([
            'operacion_uid' => 'auto-dist-' . uniqid(),
            'strategy'      => 'equal',
            'responsables'  => $clientes
        ]);

        app(\App\Http\Controllers\Api\folio\FolioDistribucionController::class)
            ->distribuir($distribucionRequest, $folio->id_folio);
    }
} else {
    // Caso general: todo al titular
    $resumen = DB::table('vw_folio_resumen')->where('id_folio', $folio->id_folio)->first();

    if ($resumen && (float)$resumen->cargos_sin_persona > 0) {
        $distribucionRequest = new \Illuminate\Http\Request([
            'operacion_uid' => 'auto-general-' . uniqid(),
            'strategy'      => 'single',
            'responsables'  => [['id_cliente' => $data['id_cliente_titular']]]
        ]);

        app(\App\Http\Controllers\Api\folio\FolioDistribucionController::class)
            ->distribuir($distribucionRequest, $folio->id_folio);
    }
}

// ================================================
// 🔟 Respuesta final
// ================================================
return response()->json([
    'message'      => 'Check-in realizado correctamente.',
    'estadia'      => $estadia->fresh(['estado']),
    'acompanantes' => $acompanantesCreados,
    'asignacion'   => $asign->fresh(),
    'folio'        => $folio->id_folio,
    'checkin_at'   => now()->toDateTimeString(),
], 201);

}); // Cierre del bloque de transacción principal

        // === Registrar crédito inicial si el huésped deja depósito o respaldo ===
        $creditoInicial = $data['credito_inicial'] ?? 0.0; // puede venir del front

        if ($creditoInicial > 0) {
            // Actualizamos el folio con ese crédito
            DB::table('folio')
                ->where('id_folio', $folio->id_folio)
                ->update(['credito_disponible' => $creditoInicial]);

            // Insertamos movimiento de crédito en el historial contable
            DB::table('folio_linea')->insert([
                'id_folio'    => $folio->id_folio,
                'id_cliente'  => $data['id_cliente_titular'],
                'descripcion' => 'Crédito inicial o depósito de respaldo',
                'monto'       => $creditoInicial,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            // Registramos la operación en folio_operacion (opcional)
            DB::table('folio_operacion')->insert([
                'id_folio'      => $folio->id_folio,
                'operacion_uid' => 'credito-' . uniqid(),
                'tipo'          => 'credito_inicial',
                'total'         => $creditoInicial,
                'payload'       => json_encode([
                    'metodo' => $data['metodo_pago'] ?? 'Tarjeta',
                    'nota' => 'Depósito inicial de respaldo',
                ], JSON_UNESCAPED_UNICODE),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

    }
}