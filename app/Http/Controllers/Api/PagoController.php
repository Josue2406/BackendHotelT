<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\reserva\Reserva;
use App\Models\reserva\ReservaPago;
use App\Models\catalago_pago\MetodoPago;
use App\Models\catalago_pago\Moneda;
use App\Models\catalago_pago\EstadoPago;
use App\Models\catalago_pago\TipoTransaccion;
use App\Services\ExchangeRateService;

class PagoController extends Controller
{
    protected $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    /**
     * POST /api/pagos/inicial
     * Procesa el pago inicial automático del 30% para confirmar la reserva
     */
    public function pagoInicial(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_reserva' => 'required|exists:reservas,id_reserva',
            'codigo_moneda' => 'required|size:3|in:USD,CRC,EUR',
            'codigo_metodo_pago' => 'required|size:2|exists:metodo_pago,codigo',
            'referencia' => 'nullable|string|max:100',
            'notas' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $reserva = Reserva::findOrFail($request->id_reserva);

            // Verificar que la reserva esté en estado que permita pagos
            if (in_array($reserva->estado_reserva->nombre ?? '', ['Cancelada', 'Finalizada'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede procesar pagos para una reserva cancelada o finalizada.'
                ], 400);
            }

            // Calcular 30% del total
            $porcentajeInicial = $reserva->porcentaje_minimo_pago ?? 30;
            $montoInicialUSD = ($reserva->total_monto_reserva * $porcentajeInicial) / 100;

            // Obtener tipo de cambio actual
            $tipoCambio = $this->exchangeRateService->obtenerTipoCambio($request->codigo_moneda);

            // Convertir a la moneda seleccionada
            $montoEnMonedaSeleccionada = $this->exchangeRateService->convertirDesdeUSD($montoInicialUSD, $request->codigo_moneda);

            // Obtener IDs necesarios
            $moneda = Moneda::where('codigo', $request->codigo_moneda)->firstOrFail();
            $metodoPago = MetodoPago::where('codigo', $request->codigo_metodo_pago)->where('activo', true)->firstOrFail();
            $estadoPago = EstadoPago::where('nombre', 'COMPLETADO')->firstOrFail();
            $tipoTransaccion = TipoTransaccion::where('nombre', 'Pago')->first();

            // Verificar si el método requiere autorización
            if ($metodoPago->requiere_autorizacion && !$request->has('autorizado_por')) {
                return response()->json([
                    'success' => false,
                    'message' => "El método de pago '{$metodoPago->nombre}' requiere autorización. Por favor, proporcione 'autorizado_por'."
                ], 400);
            }

            // Crear el pago
            $pago = ReservaPago::create([
                'id_reserva' => $reserva->id_reserva,
                'id_metodo_pago' => $metodoPago->id_metodo_pago,
                'id_tipo_transaccion' => $tipoTransaccion->id_tipo_transaccion ?? null,
                'id_estado_pago' => $estadoPago->id_estado_pago,
                'id_moneda' => $moneda->id_moneda,
                'monto' => $montoEnMonedaSeleccionada['monto'],
                'tipo_cambio' => $tipoCambio,
                'monto_usd' => $montoInicialUSD,
                'referencia' => $request->referencia,
                'notas' => $request->notas ?? "Pago inicial ({$porcentajeInicial}%) para confirmar reserva",
                'fecha_pago' => now(),
                'creado_por' => auth()->id() ?? 1,
            ]);

            // Actualizar montos de la reserva
            $reserva->actualizarMontosPago();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago inicial procesado exitosamente.',
                'data' => [
                    'pago' => $pago->load(['moneda', 'metodoPago', 'estadoPago']),
                    'detalles_conversion' => $pago->detalles_conversion,
                    'reserva' => [
                        'total_reserva' => $reserva->total_monto_reserva,
                        'monto_pagado' => $reserva->monto_pagado,
                        'monto_pendiente' => $reserva->monto_pendiente,
                        'porcentaje_pagado' => $reserva->porcentaje_pagado,
                        'pago_completo' => $reserva->pago_completo,
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pago inicial.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/pagos/restante
     * Procesa el pago del saldo restante de la reserva
     */
    public function pagoRestante(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_reserva' => 'required|exists:reservas,id_reserva',
            'codigo_moneda' => 'required|size:3|in:USD,CRC,EUR',
            'codigo_metodo_pago' => 'required|size:2|exists:metodo_pago,codigo',
            'referencia' => 'nullable|string|max:100',
            'notas' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $reserva = Reserva::findOrFail($request->id_reserva);

            // Verificar que la reserva tenga saldo pendiente
            if ($reserva->monto_pendiente <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'La reserva no tiene saldo pendiente.'
                ], 400);
            }

            // El monto restante es el saldo pendiente en USD
            $montoRestanteUSD = $reserva->monto_pendiente;

            // Obtener tipo de cambio ACTUAL (recalculado)
            $tipoCambio = $this->exchangeRateService->obtenerTipoCambio($request->codigo_moneda);

            // Convertir a la moneda seleccionada CON EL TIPO DE CAMBIO DEL DÍA
            $montoEnMonedaSeleccionada = $this->exchangeRateService->convertirDesdeUSD($montoRestanteUSD, $request->codigo_moneda);

            // Obtener IDs necesarios
            $moneda = Moneda::where('codigo', $request->codigo_moneda)->firstOrFail();
            $metodoPago = MetodoPago::where('codigo', $request->codigo_metodo_pago)->where('activo', true)->firstOrFail();
            $estadoPago = EstadoPago::where('nombre', 'COMPLETADO')->firstOrFail();
            $tipoTransaccion = TipoTransaccion::where('nombre', 'Pago')->first();

            // Verificar autorización si es necesario
            if ($metodoPago->requiere_autorizacion && !$request->has('autorizado_por')) {
                return response()->json([
                    'success' => false,
                    'message' => "El método de pago '{$metodoPago->nombre}' requiere autorización."
                ], 400);
            }

            // Crear el pago
            $pago = ReservaPago::create([
                'id_reserva' => $reserva->id_reserva,
                'id_metodo_pago' => $metodoPago->id_metodo_pago,
                'id_tipo_transaccion' => $tipoTransaccion->id_tipo_transaccion ?? null,
                'id_estado_pago' => $estadoPago->id_estado_pago,
                'id_moneda' => $moneda->id_moneda,
                'monto' => $montoEnMonedaSeleccionada['monto'],
                'tipo_cambio' => $tipoCambio,
                'monto_usd' => $montoRestanteUSD,
                'referencia' => $request->referencia,
                'notas' => $request->notas ?? "Pago del saldo restante",
                'fecha_pago' => now(),
                'creado_por' => auth()->id() ?? 1,
            ]);

            // Actualizar montos de la reserva
            $reserva->actualizarMontosPago();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago del saldo restante procesado exitosamente.',
                'data' => [
                    'pago' => $pago->load(['moneda', 'metodoPago', 'estadoPago']),
                    'detalles_conversion' => $pago->detalles_conversion,
                    'reserva' => [
                        'total_reserva' => $reserva->total_monto_reserva,
                        'monto_pagado' => $reserva->monto_pagado,
                        'monto_pendiente' => $reserva->monto_pendiente,
                        'porcentaje_pagado' => $reserva->porcentaje_pagado,
                        'pago_completo' => $reserva->pago_completo,
                    ],
                    'tipo_cambio_info' => [
                        'fecha' => now()->toDateString(),
                        'tasa' => $tipoCambio,
                        'moneda' => $request->codigo_moneda,
                        'nota' => 'Tipo de cambio recalculado al momento del pago'
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pago restante.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/pagos/completo
     * Procesa el pago completo (100%) de la reserva en una sola transacción
     */
    public function pagoCompleto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_reserva' => 'required|exists:reservas,id_reserva',
            'codigo_moneda' => 'required|size:3|in:USD,CRC,EUR',
            'codigo_metodo_pago' => 'required|size:2|exists:metodo_pago,codigo',
            'referencia' => 'nullable|string|max:100',
            'notas' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $reserva = Reserva::findOrFail($request->id_reserva);

            // Verificar que la reserva no esté completamente pagada
            if ($reserva->pago_completo) {
                return response()->json([
                    'success' => false,
                    'message' => 'La reserva ya está completamente pagada.'
                ], 400);
            }

            // El monto completo es el total de la reserva en USD
            $montoCompletoUSD = $reserva->total_monto_reserva;

            // Si ya hay pagos previos, solo cobrar la diferencia
            $montoPendienteUSD = $reserva->monto_pendiente;

            if ($montoPendienteUSD < $montoCompletoUSD) {
                // Ya hay pagos previos, usar el método pagoRestante
                return $this->pagoRestante($request);
            }

            // Obtener tipo de cambio actual
            $tipoCambio = $this->exchangeRateService->obtenerTipoCambio($request->codigo_moneda);

            // Convertir a la moneda seleccionada
            $montoEnMonedaSeleccionada = $this->exchangeRateService->convertirDesdeUSD($montoCompletoUSD, $request->codigo_moneda);

            // Obtener IDs necesarios
            $moneda = Moneda::where('codigo', $request->codigo_moneda)->firstOrFail();
            $metodoPago = MetodoPago::where('codigo', $request->codigo_metodo_pago)->where('activo', true)->firstOrFail();
            $estadoPago = EstadoPago::where('nombre', 'COMPLETADO')->firstOrFail();
            $tipoTransaccion = TipoTransaccion::where('nombre', 'Pago')->first();

            // Verificar autorización si es necesario
            if ($metodoPago->requiere_autorizacion && !$request->has('autorizado_por')) {
                return response()->json([
                    'success' => false,
                    'message' => "El método de pago '{$metodoPago->nombre}' requiere autorización."
                ], 400);
            }

            // Crear el pago
            $pago = ReservaPago::create([
                'id_reserva' => $reserva->id_reserva,
                'id_metodo_pago' => $metodoPago->id_metodo_pago,
                'id_tipo_transaccion' => $tipoTransaccion->id_tipo_transaccion ?? null,
                'id_estado_pago' => $estadoPago->id_estado_pago,
                'id_moneda' => $moneda->id_moneda,
                'monto' => $montoEnMonedaSeleccionada['monto'],
                'tipo_cambio' => $tipoCambio,
                'monto_usd' => $montoCompletoUSD,
                'referencia' => $request->referencia,
                'notas' => $request->notas ?? "Pago completo (100%) de la reserva",
                'fecha_pago' => now(),
                'creado_por' => auth()->id() ?? 1,
            ]);

            // Actualizar montos de la reserva
            $reserva->actualizarMontosPago();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago completo procesado exitosamente.',
                'data' => [
                    'pago' => $pago->load(['moneda', 'metodoPago', 'estadoPago']),
                    'detalles_conversion' => $pago->detalles_conversion,
                    'reserva' => [
                        'total_reserva' => $reserva->total_monto_reserva,
                        'monto_pagado' => $reserva->monto_pagado,
                        'monto_pendiente' => $reserva->monto_pendiente,
                        'porcentaje_pagado' => $reserva->porcentaje_pagado,
                        'pago_completo' => $reserva->pago_completo,
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pago completo.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/pagos/divisas-principales
     * Obtiene las divisas principales soportadas (USD, CRC, EUR)
     */
    public function divisasPrincipales()
    {
        $divisas = $this->exchangeRateService->obtenerMonedasSoportadas();

        // Filtrar solo las divisas principales
        $divisasPrincipales = array_filter($divisas, function($divisa) {
            return in_array($divisa['codigo'], ['USD', 'CRC', 'EUR']);
        });

        return response()->json([
            'success' => true,
            'data' => array_values($divisasPrincipales)
        ]);
    }

    /**
     * GET /api/pagos/metodos
     * Obtiene los métodos de pago activos
     */
    public function metodosPago()
    {
        $metodos = MetodoPago::where('activo', true)->get();

        return response()->json([
            'success' => true,
            'data' => $metodos->map(function($metodo) {
                return [
                    'id' => $metodo->id_metodo_pago,
                    'codigo' => $metodo->codigo,
                    'nombre' => $metodo->nombre,
                    'descripcion' => $metodo->descripcion,
                    'requiere_autorizacion' => $metodo->requiere_autorizacion,
                ];
            })
        ]);
    }

    /**
     * GET /api/pagos/tipo-cambio/{moneda}
     * Obtiene el tipo de cambio actual para una moneda
     */
    public function tipoCambio($moneda)
    {
        try {
            $tipoCambio = $this->exchangeRateService->obtenerTipoCambio(strtoupper($moneda));

            return response()->json([
                'success' => true,
                'data' => [
                    'moneda' => strtoupper($moneda),
                    'tipo_cambio' => $tipoCambio,
                    'fecha' => now()->toDateString(),
                    'formula' => "1 USD = {$tipoCambio} " . strtoupper($moneda)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipo de cambio.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/pagos/calcular-precio
     * Calcula el precio en las 3 divisas principales
     */
    public function calcularPrecio(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'monto_usd' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $precios = $this->exchangeRateService->calcularPrecioMultidivisa($request->monto_usd);

            return response()->json([
                'success' => true,
                'data' => $precios
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al calcular precios.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
