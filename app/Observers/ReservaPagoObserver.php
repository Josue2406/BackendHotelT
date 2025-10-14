<?php

namespace App\Observers;

use App\Models\reserva\ReservaPago;
use App\Models\reserva\EstadoReserva;
use App\Models\catalago_pago\EstadoPago;
use Illuminate\Support\Facades\Log;

class ReservaPagoObserver
{
    /**
     * Handle the ReservaPago "created" event.
     */
    public function created(ReservaPago $pago): void
    {
        // Solo actualizar si el pago está completado o parcial
        if (in_array($pago->id_estado_pago, [EstadoPago::ESTADO_COMPLETADO, EstadoPago::ESTADO_PARCIAL])) {
            $this->actualizarReserva($pago);
        }
    }

    /**
     * Handle the ReservaPago "updated" event.
     */
    public function updated(ReservaPago $pago): void
    {
        // Si cambió el estado del pago, actualizar la reserva
        if ($pago->wasChanged('id_estado_pago') || $pago->wasChanged('monto')) {
            $this->actualizarReserva($pago);
        }
    }

    /**
     * Handle the ReservaPago "deleted" event.
     */
    public function deleted(ReservaPago $pago): void
    {
        // Recalcular montos al eliminar un pago
        $this->actualizarReserva($pago);
    }

    /**
     * Actualizar los montos de pago de la reserva y cambiar su estado si es necesario
     */
    protected function actualizarReserva(ReservaPago $pago): void
    {
        $reserva = $pago->id_reserva; // relación belongsTo

        if (!$reserva) {
            return;
        }

        // Recalcular montos de pago
        $reserva->actualizarMontosPago();

        // Recargar la reserva para obtener los valores actualizados
        $reserva->refresh();

        // Cambiar estado de la reserva según el pago
        $estadoActual = $reserva->id_estado_res;

        // Si está en Pendiente y se alcanzó el pago mínimo → Cambiar a Confirmada
        if ($estadoActual == EstadoReserva::ESTADO_PENDIENTE && $reserva->alcanzoPagoMinimo()) {
            $reserva->update(['id_estado_res' => EstadoReserva::ESTADO_CONFIRMADA]);

            Log::info("Reserva confirmada automáticamente por pago", [
                'id_reserva' => $reserva->id_reserva,
                'id_pago' => $pago->id_reserva_pago,
                'monto_pago' => $pago->monto,
                'total_pagado' => $reserva->monto_pagado,
                'porcentaje_pagado' => $reserva->porcentaje_pagado,
            ]);
        }

        Log::info("Montos de reserva actualizados", [
            'id_reserva' => $reserva->id_reserva,
            'id_pago' => $pago->id_reserva_pago,
            'total_reserva' => $reserva->total_monto_reserva,
            'monto_pagado' => $reserva->monto_pagado,
            'monto_pendiente' => $reserva->monto_pendiente,
            'pago_completo' => $reserva->pago_completo,
        ]);
    }
}