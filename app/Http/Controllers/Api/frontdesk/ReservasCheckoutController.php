<?php

namespace App\Http\Controllers\Api\frontdesk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\reserva\Reserva;
use App\Models\check_out\Folio;
use App\Models\check_out\EstadoFolio;
use App\Models\reserva\EstadoReserva;
use App\Models\estadia\EstadoEstadia;

class ReservasCheckoutController extends Controller
{
    /**
     * POST /frontdesk/reserva/{reserva}/checkout
     */
    public function store(Request $request, Reserva $reserva)
    {
        $data = $request->validate([
            'operacion_uid'       => 'required|string|max:100',
            'id_cliente_titular'  => 'required|integer|exists:clientes,id_cliente',
        ]);

        // 🔹 Verificar que la reserva exista y tenga folio
        $folio = Folio::where('id_reserva_hab', $reserva->id_reserva)
            ->orWhere('id_estadia', $reserva->id_reserva)
            ->first();

        if (!$folio) {
            return response()->json(['message' => 'No se encontró folio asociado a la reserva.'], 404);
        }

        // 🔹 Verificar si ya está cerrado
        $estadoActual = DB::table('estado_folio')
            ->where('id_estado_folio', $folio->id_estado_folio)
            ->value('nombre');

        if (strtoupper($estadoActual) === 'CERRADO') {
            return response()->json(['message' => 'El folio ya se encuentra cerrado.'], 409);
        }
           // 💾 Ejecutar cierre de folio vía controlador folio interno
        $folioCerrarCtrl = app(\App\Http\Controllers\Api\folio\FolioCerrarController::class);
        return $folioCerrarCtrl->store($request, $folio->id_folio);

        // ============================
        // 💾 Transacción principal
        // ============================
        return DB::transaction(function () use ($folio, $reserva, $data) {

            // 1️⃣ Calcular saldo global desde la vista resumen
            $resumen = DB::table('vw_folio_resumen')
                ->where('id_folio', $folio->id_folio)
                ->select('a_distribuir', 'pagos_generales', 'distribuido')
                ->first();

            if (!$resumen) {
                return response()->json(['message' => 'No se pudo obtener el resumen del folio.'], 422);
            }

            $saldoGlobal = (float)$resumen->a_distribuir
                - ((float)$resumen->distribuido + (float)$resumen->pagos_generales);

            if ($saldoGlobal > 0.01) {
                return response()->json([
                    'message' => 'No se puede realizar el Check-out. Aún existen saldos pendientes.',
                    'saldo_global' => round($saldoGlobal, 2)
                ], 422);
            }

            // 2️⃣ Cambiar estado del folio a CERRADO
            $estadoCerradoId = DB::table('estado_folio')
                ->where('nombre', 'CERRADO')
                ->value('id_estado_folio');

            DB::table('folio')
                ->where('id_folio', $folio->id_folio)
                ->update([
                    'id_estado_folio' => $estadoCerradoId,
                    'updated_at'      => now(),
                ]);

            // 3️⃣ Registrar evento en historial
            DB::table('folio_historial')->insert([
                'id_folio'      => $folio->id_folio,
                'operacion_uid' => $data['operacion_uid'],
                'tipo'          => 'cierre',
                'total'         => $resumen->a_distribuir,
                'payload'       => json_encode([
                    'titular' => $data['id_cliente_titular'],
                    'saldo_final' => round($saldoGlobal, 2)
                ], JSON_UNESCAPED_UNICODE),
                'summary'       => "Cierre de folio {$folio->id_folio} — Total: {$resumen->a_distribuir}",
                'created_at'    => now(),
            ]);

            // 4️⃣ Actualizar estado de reserva → CHECKOUT
            $reserva->update(['id_estado_res' => EstadoReserva::ESTADO_CHECKOUT]);

            // 5️⃣ Actualizar estado de estadía → TERMINADA
            if ($folio->id_estadia) {
                DB::table('estadia')
                    ->where('id_estadia', $folio->id_estadia)
                    ->update(['id_estado_estadia' => EstadoEstadia::TERMINADA]);
            }

            // 6️⃣ Retornar respuesta limpia
            return response()->json([
                'message'          => 'Check-out realizado correctamente.',
                'id_folio'         => $folio->id_folio,
                'estado_folio'     => 'CERRADO',
                'saldo_final'      => round($saldoGlobal, 2),
                'reserva_estado'   => 'CHECKOUT',
                'estadia_estado'   => 'TERMINADA',
                'cerrado_at'       => now()->toDateTimeString(),
            ], 200);
        });
    }
}
