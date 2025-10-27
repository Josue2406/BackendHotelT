<?php

namespace App\Services\reserva;

use App\Models\reserva\Reserva;
use App\Models\reserva\ReservaHabitacion;
use App\Models\habitacion\Habitacion;
use App\Models\reserva\PoliticaCancelacion;
use App\Services\ExchangeRateService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ModificacionReservaService
{
    protected $pricingService;
    protected $exchangeRateService;

    public function __construct(
        PricingService $pricingService,
        ExchangeRateService $exchangeRateService
    ) {
        $this->pricingService = $pricingService;
        $this->exchangeRateService = $exchangeRateService;
    }

    /**
     * Cambiar habitación sin modificar fechas
     * Por preferencia del cliente (upgrade/downgrade)
     */
    public function cambiarHabitacion(
        Reserva $reserva,
        int $idReservaHabitacion,
        int $idHabitacionNueva,
        ?string $motivo = null
    ): array {
        return DB::transaction(function () use ($reserva, $idReservaHabitacion, $idHabitacionNueva, $motivo) {

            // 1. Obtener reserva de habitación actual
            $reservaHab = ReservaHabitacion::findOrFail($idReservaHabitacion);

            if ($reservaHab->id_reserva != $reserva->id_reserva) {
                throw new \Exception('La habitación no pertenece a esta reserva');
            }

            $habitacionAntigua = $reservaHab->habitacion;
            $habitacionNueva = Habitacion::findOrFail($idHabitacionNueva);

            // 2. Verificar disponibilidad de la nueva habitación
            $disponible = $this->verificarDisponibilidadHabitacion(
                $habitacionNueva,
                Carbon::parse($reservaHab->fecha_llegada),
                Carbon::parse($reservaHab->fecha_salida)
            );

            if (!$disponible) {
                throw new \Exception('La habitación seleccionada no está disponible para las fechas de la reserva');
            }

            // 3. Calcular precios
            $precioAntiguo = $reservaHab->precio_total;
            $precioNuevo = $this->pricingService->precioRango(
                $habitacionNueva,
                Carbon::parse($reservaHab->fecha_llegada),
                Carbon::parse($reservaHab->fecha_salida)
            );

            $diferenciaPrecio = $precioNuevo - $precioAntiguo;

            // 4. Actualizar reserva de habitación
            $reservaHab->update([
                'id_habitacion' => $idHabitacionNueva,
                'precio_total' => $precioNuevo,
                'notas' => ($reservaHab->notas ?? '') . "\n[Cambio de habitación: {$habitacionAntigua->nombre} → {$habitacionNueva->nombre}. Motivo: " . ($motivo ?? 'Preferencia del cliente') . "]"
            ]);

            // 5. Actualizar total de la reserva
            $reserva->total_monto_reserva += $diferenciaPrecio;
            $reserva->save();

            // 6. Recalcular montos pendientes
            $reserva->actualizarMontosPago();

            return [
                'success' => true,
                'habitacion_antigua' => [
                    'id' => $habitacionAntigua->id_habitacion,
                    'nombre' => $habitacionAntigua->nombre,
                    'precio' => $precioAntiguo
                ],
                'habitacion_nueva' => [
                    'id' => $habitacionNueva->id_habitacion,
                    'nombre' => $habitacionNueva->nombre,
                    'precio' => $precioNuevo
                ],
                'diferencia_precio' => $diferenciaPrecio,
                'tipo_ajuste' => $diferenciaPrecio > 0 ? 'cargo_adicional' : ($diferenciaPrecio < 0 ? 'reembolso' : 'sin_cambio'),
                'monto_ajuste' => abs($diferenciaPrecio),
                'reserva' => [
                    'total_nuevo' => $reserva->total_monto_reserva,
                    'monto_pagado' => $reserva->monto_pagado,
                    'monto_pendiente' => $reserva->monto_pendiente
                ]
            ];
        });
    }

    /**
     * Modificar fechas de la reserva (check-in y/o check-out)
     */
    public function modificarFechas(
        Reserva $reserva,
        int $idReservaHabitacion,
        ?Carbon $nuevaFechaLlegada = null,
        ?Carbon $nuevaFechaSalida = null,
        bool $aplicarPolitica = true
    ): array {
        return DB::transaction(function () use ($reserva, $idReservaHabitacion, $nuevaFechaLlegada, $nuevaFechaSalida, $aplicarPolitica) {

            $reservaHab = ReservaHabitacion::findOrFail($idReservaHabitacion);

            if ($reservaHab->id_reserva != $reserva->id_reserva) {
                throw new \Exception('La habitación no pertenece a esta reserva');
            }

            $fechaLlegadaOriginal = Carbon::parse($reservaHab->fecha_llegada);
            $fechaSalidaOriginal = Carbon::parse($reservaHab->fecha_salida);

            $fechaLlegadaNueva = $nuevaFechaLlegada ?? $fechaLlegadaOriginal;
            $fechaSalidaNueva = $nuevaFechaSalida ?? $fechaSalidaOriginal;

            // Validar que la nueva fecha de salida sea posterior a la de llegada
            if ($fechaSalidaNueva->lte($fechaLlegadaNueva)) {
                throw new \Exception('La fecha de salida debe ser posterior a la fecha de llegada');
            }

            // Verificar disponibilidad en las nuevas fechas
            $disponible = $this->verificarDisponibilidadHabitacion(
                $reservaHab->habitacion,
                $fechaLlegadaNueva,
                $fechaSalidaNueva,
                $reservaHab->id_reserva_hab // Excluir esta reserva de la verificación
            );

            if (!$disponible) {
                throw new \Exception('La habitación no está disponible para las nuevas fechas seleccionadas');
            }

            // Calcular precios con las nuevas fechas
            $precioAntiguo = $reservaHab->precio_total;
            $precioNuevo = $this->pricingService->precioRango(
                $reservaHab->habitacion,
                $fechaLlegadaNueva,
                $fechaSalidaNueva
            );

            $diferenciaPrecio = $precioNuevo - $precioAntiguo;

            // Aplicar política de modificación si es necesario
            $penalidad = 0;
            $politicaAplicada = null;

            if ($aplicarPolitica && $diferenciaPrecio < 0) {
                // Si es una reducción, aplicar política de cancelación
                $diasAnticipacion = now()->diffInDays($fechaLlegadaOriginal, false);

                $resultadoPolitica = PoliticaCancelacion::calcularReembolsoHotelLanaku(
                    abs($diferenciaPrecio),
                    (int) $diasAnticipacion,
                    false, // No es temporada alta por defecto
                    false  // No es tarifa no reembolsable
                );

                $penalidad = $resultadoPolitica['penalidad'];
                $politicaAplicada = $resultadoPolitica['mensaje'];
            }

            // Actualizar reserva de habitación
            $reservaHab->update([
                'fecha_llegada' => $fechaLlegadaNueva,
                'fecha_salida' => $fechaSalidaNueva,
                'precio_total' => $precioNuevo,
                'notas' => ($reservaHab->notas ?? '') . "\n[Modificación de fechas: {$fechaLlegadaOriginal->format('Y-m-d')} - {$fechaSalidaOriginal->format('Y-m-d')} → {$fechaLlegadaNueva->format('Y-m-d')} - {$fechaSalidaNueva->format('Y-m-d')}]"
            ]);

            // Actualizar total de la reserva
            $ajusteTotal = $diferenciaPrecio - $penalidad;
            $reserva->total_monto_reserva += $ajusteTotal;
            $reserva->save();

            // Recalcular montos pendientes
            $reserva->actualizarMontosPago();

            return [
                'success' => true,
                'fechas_originales' => [
                    'llegada' => $fechaLlegadaOriginal->format('Y-m-d'),
                    'salida' => $fechaSalidaOriginal->format('Y-m-d'),
                    'noches' => $fechaLlegadaOriginal->diffInDays($fechaSalidaOriginal)
                ],
                'fechas_nuevas' => [
                    'llegada' => $fechaLlegadaNueva->format('Y-m-d'),
                    'salida' => $fechaSalidaNueva->format('Y-m-d'),
                    'noches' => $fechaLlegadaNueva->diffInDays($fechaSalidaNueva)
                ],
                'precios' => [
                    'precio_anterior' => $precioAntiguo,
                    'precio_nuevo' => $precioNuevo,
                    'diferencia' => $diferenciaPrecio,
                    'penalidad' => $penalidad,
                    'ajuste_total' => $ajusteTotal
                ],
                'politica' => $politicaAplicada,
                'reserva' => [
                    'total_nuevo' => $reserva->total_monto_reserva,
                    'monto_pagado' => $reserva->monto_pagado,
                    'monto_pendiente' => $reserva->monto_pendiente
                ]
            ];
        });
    }

    /**
     * Reducir estadía (checkout anticipado)
     */
    public function reducirEstadia(
        Reserva $reserva,
        int $idReservaHabitacion,
        Carbon $nuevaFechaSalida,
        bool $aplicarPolitica = true
    ): array {
        return DB::transaction(function () use ($reserva, $idReservaHabitacion, $nuevaFechaSalida, $aplicarPolitica) {

            $reservaHab = ReservaHabitacion::findOrFail($idReservaHabitacion);

            if ($reservaHab->id_reserva != $reserva->id_reserva) {
                throw new \Exception('La habitación no pertenece a esta reserva');
            }

            $fechaSalidaOriginal = Carbon::parse($reservaHab->fecha_salida);
            $fechaLlegada = Carbon::parse($reservaHab->fecha_llegada);

            // Validar que la nueva fecha sea anterior a la original
            if ($nuevaFechaSalida->gte($fechaSalidaOriginal)) {
                throw new \Exception('La nueva fecha de salida debe ser anterior a la original para reducir la estadía');
            }

            // Validar que la nueva fecha sea posterior a la llegada
            if ($nuevaFechaSalida->lte($fechaLlegada)) {
                throw new \Exception('La nueva fecha de salida debe ser posterior a la fecha de llegada');
            }

            // Calcular noches canceladas
            $nochesCanceladas = $nuevaFechaSalida->diffInDays($fechaSalidaOriginal);
            $nochesOriginales = $fechaLlegada->diffInDays($fechaSalidaOriginal);
            $nochesNuevas = $fechaLlegada->diffInDays($nuevaFechaSalida);

            // Calcular precios
            $precioOriginal = $reservaHab->precio_total;
            $precioNuevo = $this->pricingService->precioRango(
                $reservaHab->habitacion,
                $fechaLlegada,
                $nuevaFechaSalida
            );

            $montoNochesCanceladas = $precioOriginal - $precioNuevo;

            // Aplicar política de cancelación para las noches no utilizadas
            $reembolso = 0;
            $penalidad = 0;
            $politicaAplicada = null;

            if ($aplicarPolitica) {
                $diasAnticipacion = now()->diffInDays($nuevaFechaSalida, false);

                $resultadoPolitica = PoliticaCancelacion::calcularReembolsoHotelLanaku(
                    $montoNochesCanceladas,
                    (int) $diasAnticipacion,
                    false,
                    false
                );

                $reembolso = $resultadoPolitica['reembolso'];
                $penalidad = $resultadoPolitica['penalidad'];
                $politicaAplicada = $resultadoPolitica['mensaje'];
            } else {
                // Sin política, reembolso completo
                $reembolso = $montoNochesCanceladas;
            }

            // Actualizar reserva de habitación
            $reservaHab->update([
                'fecha_salida' => $nuevaFechaSalida,
                'precio_total' => $precioNuevo,
                'notas' => ($reservaHab->notas ?? '') . "\n[Reducción de estadía: {$nochesCanceladas} noches canceladas. Checkout anticipado: {$nuevaFechaSalida->format('Y-m-d')}]"
            ]);

            // Actualizar total de la reserva (restar el monto no reembolsable)
            $reserva->total_monto_reserva -= $reembolso;
            $reserva->save();

            // Recalcular montos pendientes
            $reserva->actualizarMontosPago();

            return [
                'success' => true,
                'reduccion' => [
                    'noches_canceladas' => $nochesCanceladas,
                    'noches_originales' => $nochesOriginales,
                    'noches_nuevas' => $nochesNuevas,
                    'fecha_salida_original' => $fechaSalidaOriginal->format('Y-m-d'),
                    'fecha_salida_nueva' => $nuevaFechaSalida->format('Y-m-d')
                ],
                'montos' => [
                    'precio_original' => $precioOriginal,
                    'precio_nuevo' => $precioNuevo,
                    'monto_noches_canceladas' => $montoNochesCanceladas,
                    'reembolso' => $reembolso,
                    'penalidad' => $penalidad
                ],
                'politica' => $politicaAplicada,
                'reserva' => [
                    'total_nuevo' => $reserva->total_monto_reserva,
                    'monto_pagado' => $reserva->monto_pagado,
                    'monto_pendiente' => $reserva->monto_pendiente
                ]
            ];
        });
    }

    /**
     * Verificar disponibilidad de una habitación en un rango de fechas
     */
    private function verificarDisponibilidadHabitacion(
        Habitacion $habitacion,
        Carbon $fechaInicio,
        Carbon $fechaFin,
        ?int $excluirReservaHabitacion = null
    ): bool {
        $query = ReservaHabitacion::where('id_habitacion', $habitacion->id_habitacion)
            ->where(function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('fecha_llegada', [$fechaInicio, $fechaFin])
                  ->orWhereBetween('fecha_salida', [$fechaInicio, $fechaFin])
                  ->orWhere(function ($q2) use ($fechaInicio, $fechaFin) {
                      $q2->where('fecha_llegada', '<=', $fechaInicio)
                         ->where('fecha_salida', '>=', $fechaFin);
                  });
            })
            ->whereHas('reserva', function ($q) {
                // Excluir reservas canceladas
                $q->where('id_estado_res', '!=', \App\Models\reserva\EstadoReserva::ESTADO_CANCELADA);
            });

        if ($excluirReservaHabitacion) {
            $query->where('id_reserva_hab', '!=', $excluirReservaHabitacion);
        }

        return $query->count() === 0;
    }

    /**
     * Calcular precio en múltiples divisas para mostrar al cliente
     */
    public function calcularPrecioMultidivisa(float $montoUSD): array
    {
        return $this->exchangeRateService->calcularPrecioMultidivisa($montoUSD);
    }
}