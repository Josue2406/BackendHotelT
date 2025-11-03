<?php

namespace App\Http\Controllers\Api\folio;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\check_out\Folio;
use App\Models\reserva\EstadoReserva;
use App\Models\estadia\EstadoEstadia;
use App\Models\check_out\EstadoFolio;

class FolioCerrarController extends Controller
{
    public function store(Request $request, int $idFolio)
    {
        // 1️⃣ Validación de entrada
        $data = $request->validate([
            'operacion_uid' => 'required|string|max:100',
            'id_cliente_titular' => 'required|integer|exists:clientes,id_cliente',
        ]);

        // 2️⃣ Buscar folio y verificar estado actual
        $folio = Folio::with('estadoFolio')->find($idFolio);
        if (!$folio) {
            return response()->json(['message' => 'Folio no encontrado'], 404);
        }

        if (strtoupper($folio->estadoFolio->nombre ?? '') === 'CERRADO') {
            return response()->json(['message' => 'El folio ya está cerrado.'], 409);
        }

        // 3️⃣ Idempotencia: si ya se cerró con el mismo operacion_uid
        $exists = DB::table('folio_operacion')
            ->where('operacion_uid', $data['operacion_uid'])
            ->where('id_folio', $idFolio)
            ->where('tipo', 'cierre')
            ->exists();

        if ($exists) {
            return app(FolioResumenController::class)->show($idFolio);
        }

        // 4️⃣ Verificar resumen del folio
        $resumen = DB::table('vw_folio_resumen')
            ->where('id_folio', $idFolio)
            ->select('a_distribuir', 'distribuido', 'pagos_generales', 'cargos_sin_persona')
            ->first();

        if (!$resumen) {
            return response()->json(['message' => 'No se pudo obtener resumen del folio.'], 422);
        }

        // Calcular saldo global
        $saldoGlobal = (float)$resumen->a_distribuir
            - ((float)$resumen->distribuido + (float)$resumen->pagos_generales);

        if ($saldoGlobal > 0.01) {
            return response()->json([
                'message' => 'No se puede cerrar el folio. Existen saldos pendientes.',
                'saldo_global' => round($saldoGlobal, 2)
            ], 422);
        }

        // 5️⃣ Transacción completa
       DB::transaction(function () use ($idFolio, $data, $folio, $resumen) {

    // ✅ Buscar el estado "CERRADO" sin importar mayúsculas o minúsculas
    $estadoCerradoId = EstadoFolio::whereRaw('UPPER(nombre) = ?', ['CERRADO'])
        ->value('id_estado_folio');

    // Si no se encuentra, lanza error explícito
    if (!$estadoCerradoId) {
        throw new \Exception('No se encontró el estado "CERRADO" en la tabla estado_folio');
    }

    // 🔹 Actualizar estado del folio
    DB::table('folio')
        ->where('id_folio', $idFolio)
        ->update([
            'id_estado_folio' => $estadoCerradoId,
            'updated_at' => now(),
        ]);

    // 🔹 Registrar operación
    DB::table('folio_operacion')->insert([
        'id_folio'      => $idFolio,
        'operacion_uid' => $data['operacion_uid'],
        'tipo'          => 'cierre',
        'total'         => $resumen->a_distribuir,
        'payload'       => json_encode([
            'titular' => $data['id_cliente_titular'],
            'cargos_sin_persona' => $resumen->cargos_sin_persona,
            'pagos_generales' => $resumen->pagos_generales,
        ], JSON_UNESCAPED_UNICODE),
        'created_at'    => now(),
        'updated_at'    => now(),
    ]);

    // 🔹 Insertar línea contable del cierre
    DB::table('folio_linea')->insert([
        'id_folio'    => $idFolio,
        'id_cliente'  => $data['id_cliente_titular'],
        'descripcion' => 'Cierre general del folio — traspaso total al titular',
        'monto'       => 0,
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    // 🔹 Registrar en historial
    DB::table('folio_historial')->insert([
        'id_folio'      => $idFolio,
        'operacion_uid' => $data['operacion_uid'],
        'tipo'          => 'cierre',
        'total'         => $resumen->a_distribuir,
        'payload'       => json_encode([
            'titular' => $data['id_cliente_titular'],
            'saldo_final' => 0,
            'pagos_generales' => $resumen->pagos_generales,
        ]),
        'summary'       => "Cierre del folio {$idFolio}: traspaso total al titular {$data['id_cliente_titular']}.",
        'created_at'    => now(),
    ]);

    // 🔹 Sincronizar estados de reserva y estadía
if ($folio->id_estadia) {
    DB::table('estadia')->where('id_estadia', $folio->id_estadia)->update([
        'id_estado_estadia' => EstadoEstadia::TERMINADA,
        'updated_at' => now(),
    ]);
}

// 🔹 Actualizar estado de la reserva principal (no reserva_habitacion)
if ($folio->id_reserva_hab) {
    DB::table('reserva')
        ->join('reserva_habitacions', 'reserva.id_reserva', '=', 'reserva_habitacions.id_reserva')
        ->where('reserva_habitacions.id_reserva_hab', $folio->id_reserva_hab)
        ->update([
            'id_estado_res' => EstadoReserva::ESTADO_CHECKOUT,
            'reserva.updated_at' => now(),
        ]);
}

}, 3);


        // 6️⃣ Respuesta final — resumen extendido con estado
        return app(FolioResumenController::class)->show($idFolio);
    }
}
