<?php
namespace App\Http\Controllers\Api\reserva;

use App\Http\Controllers\Controller;
use App\Http\Requests\reserva\StoreReservaRequest;
use App\Http\Requests\reserva\UpdateReservaRequest;
use App\Http\Requests\reserva\CancelReservaRequest;
use App\Http\Requests\reserva\CotizarReservaRequest;
use App\Http\Requests\reserva\ProcesarPagoRequest;
use App\Http\Requests\reserva\ExtenderEstadiaRequest;
use App\Http\Requests\reserva\CambiarHabitacionRequest;
use App\Http\Requests\reserva\ModificarFechasRequest;
use App\Http\Requests\reserva\ReducirEstadiaRequest;
use App\Http\Requests\reserva\CambiarEstadoReservaRequest;
use App\Http\Requests\reserva\CheckInCheckOutRequest;
use App\Models\reserva\Reserva;
use App\Models\reserva\ReservaHabitacion;
use App\Models\reserva\ReservaServicio;
use App\Models\reserva\ReservaPolitica;
use App\Models\reserva\ReservaPago;
use App\Models\reserva\PoliticaCancelacion;
use App\Models\reserva\EstadoReserva;
use App\Models\estadia\Estadia;
use App\Services\CodigoReservaService;
use App\Services\ExtensionEstadiaService;
use App\Services\ExchangeRateService;
use App\Services\reserva\ModificacionReservaService;
use App\Models\catalago_pago\Moneda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Notifications\ReservaCreada;
use App\Notifications\ReservaActualizada;
use App\Notifications\ReservaCancelada;
class ReservaController extends Controller
{
    public function index(Request $request) {
        $query = Reserva::with(['cliente','estado','fuente','habitaciones.habitacion']);

        // Filtro: search (búsqueda general)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('id_reserva', 'like', "%{$search}%")
                  ->orWhere('notas', 'like', "%{$search}%")
                  ->orWhereHas('cliente', function($qCliente) use ($search) {
                      $qCliente->where('nombre', 'like', "%{$search}%")
                               ->orWhere('apellido1', 'like', "%{$search}%")
                               ->orWhere('apellido2', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filtro: estado (por nombre del estado)
        if ($request->filled('estado')) {
            $nombreEstado = $request->input('estado');
            $query->whereHas('estado', function($qEstado) use ($nombreEstado) {
                $qEstado->where('nombre', 'like', "%{$nombreEstado}%");
            });
        }

        // Filtro: desde/hasta (rango de fechas de creación o llegada)
        if ($request->filled('desde')) {
            $desde = $request->input('desde');
            $query->where(function($q) use ($desde) {
                $q->where('fecha_creacion', '>=', $desde)
                  ->orWhereHas('habitaciones', function($qHab) use ($desde) {
                      $qHab->where('fecha_llegada', '>=', $desde);
                  });
            });
        }

        if ($request->filled('hasta')) {
            $hasta = $request->input('hasta');
            $query->where(function($q) use ($hasta) {
                $q->where('fecha_creacion', '<=', $hasta)
                  ->orWhereHas('habitaciones', function($qHab) use ($hasta) {
                      $qHab->where('fecha_llegada', '<=', $hasta);
                  });
            });
        }

        // Filtro: fuente (por nombre del canal)
        if ($request->filled('fuente')) {
            $nombreFuente = $request->input('fuente');
            $query->whereHas('fuente', function($qFuente) use ($nombreFuente) {
                $qFuente->where('nombre', 'like', "%{$nombreFuente}%");
            });
        }

        return $query->latest('id_reserva')->paginate(20);
    }

    public function show(Reserva $reserva) {
        return $reserva->load(['cliente','estado','fuente','habitaciones.habitacion','servicios','politicas']);
    }

    // public function store(StoreReservaRequest $r) {
    //     $data = $r->validated();

    //     // Devolvemos el ID para poder notificar tras el commit
    //     $reservaId = DB::transaction(function () use ($data) {
    //         $habitaciones = $data['habitaciones'];
    //         unset($data['habitaciones']);

    //         $data['fecha_creacion'] = now();
    //         $data['total_monto_reserva'] = 0;
    //         $reserva = Reserva::create($data);

    //         $totalReserva = 0;

    //         foreach ($habitaciones as $hab) {
    //             $choqueReserva = ReservaHabitacion::where('id_habitacion', $hab['id_habitacion'])
    //                 ->where('fecha_llegada', '<', $hab['fecha_salida'])
    //                 ->where('fecha_salida', '>', $hab['fecha_llegada'])
    //                 ->exists();

    //             if ($choqueReserva) {
    //                 throw new \Exception("La habitación {$hab['id_habitacion']} no está disponible en el rango especificado.");
    //             }

    //             $reservaHab = $reserva->habitaciones()->create([
    //                 'id_habitacion' => $hab['id_habitacion'],
    //                 'fecha_llegada' => $hab['fecha_llegada'],
    //                 'fecha_salida'  => $hab['fecha_salida'],
    //                 'adultos'       => $hab['adultos'],
    //                 'ninos'         => $hab['ninos'],
    //                 'bebes'         => $hab['bebes'],
    //                 'subtotal'      => 0,
    //             ]);

    //             $reservaHab->load('habitacion');
    //             $subtotal = $reservaHab->calcularSubtotal();
    //             $reservaHab->update(['subtotal' => $subtotal]);

    //             $totalReserva += $subtotal;
    //         }

    //         $reserva->update(['total_monto_reserva' => $totalReserva]);

    //         // Notificar SOLO después de que la transacción haya sido confirmada
    //         DB::afterCommit(function () use ($reserva) {
    //             $fresh = $reserva->fresh()->load(['cliente','estado','fuente','habitaciones.habitacion.tipoHabitacion']);
    //             if ($fresh->cliente?->email) {
    //                 $fresh->cliente->notify(new ReservaCreada($fresh));
    //             }
    //         });

    //         return $reserva->id_reserva;
    //     });

    //     // Respuesta consistente con lo que ya retornabas
    //     $reserva = Reserva::with(['cliente','estado','fuente','habitaciones.habitacion.tipoHabitacion'])
    //         ->findOrFail($reservaId);

    //     return response()->json($reserva, 201);
    // }
    public function store(StoreReservaRequest $r)
{
    $data = $r->validated();

    // 1) Determinar id_cliente según el contexto
    // Intentar autenticar con el guard 'cliente' primero
    $clienteAutenticado = auth('sanctum')->user();

    // Si no hay usuario autenticado con sanctum, intentar con el guard cliente
    if (!$clienteAutenticado) {
        $clienteAutenticado = auth()->guard('cliente')->user();
    }

    if ($clienteAutenticado) {
        // CASO WEB: Hay token autenticado (cliente)
        // Siempre usar el cliente del token (seguridad: no puede crear reservas para otros)
        $data['id_cliente'] = $clienteAutenticado->id_cliente;

        Log::info('Reserva creada desde WEB (cliente autenticado)', [
            'id_cliente' => $data['id_cliente'],
            'email' => $clienteAutenticado->email ?? null,
            'modelo' => get_class($clienteAutenticado)
        ]);
    } else {
        // CASO RECEPCIÓN: No hay token
        // Usar id_cliente del request (ya validado que existe por StoreReservaRequest)
        if (!isset($data['id_cliente'])) {
            return response()->json([
                'success' => false,
                'message' => 'Se requiere id_cliente cuando no hay autenticación'
            ], 400);
        }

        Log::info('Reserva creada desde RECEPCIÓN (sin autenticación)', [
            'id_cliente' => $data['id_cliente']
        ]);
    }

    // 2) Preparar datos de la reserva
    $data['fecha_creacion']      = now();
    $data['total_monto_reserva'] = 0;

    // 3) Crear reserva + habitaciones dentro de transacción
    $reservaId = DB::transaction(function () use ($data) {
        $habitaciones = $data['habitaciones'];
        unset($data['habitaciones']);

        $reserva = Reserva::create($data);

        $totalReserva = 0;
        foreach ($habitaciones as $hab) {
            // disponibilidad
            $choqueReserva = \App\Models\reserva\ReservaHabitacion::where('id_habitacion', $hab['id_habitacion'])
                ->where('fecha_llegada', '<', $hab['fecha_salida'])
                ->where('fecha_salida', '>', $hab['fecha_llegada'])
                ->exists();

            if ($choqueReserva) {
                throw new \Exception("La habitación {$hab['id_habitacion']} no está disponible en el rango especificado.");
            }

            // crear item
            $reservaHab = $reserva->habitaciones()->create([
                'id_habitacion' => $hab['id_habitacion'],
                'fecha_llegada' => $hab['fecha_llegada'],
                'fecha_salida'  => $hab['fecha_salida'],
                'adultos'       => $hab['adultos'],
                'ninos'         => $hab['ninos'],
                'bebes'         => $hab['bebes'],
                'subtotal'      => 0,
            ]);

            // calcular subtotal
            $reservaHab->load('habitacion');
            $subtotal = $reservaHab->calcularSubtotal();
            $reservaHab->update(['subtotal' => $subtotal]);
            $totalReserva += $subtotal;
        }

        // total de la reserva
        $reserva->update(['total_monto_reserva' => $totalReserva]);

        // 4) Enviar el correo SOLO después de commit
        DB::afterCommit(function () use ($reserva) {
            $fresh = $reserva->fresh()->load([
                'cliente',
                'estado',
                'fuente',
                'habitaciones.habitacion.tipoHabitacion'
            ]);

            if ($fresh->cliente?->email) {
                $fresh->cliente->notify(new \App\Notifications\ReservaCreada($fresh));
            }
        });

        return $reserva->id_reserva;
    });

    // 5) Respuesta
    $reserva = Reserva::with(['cliente','estado','fuente','habitaciones.habitacion.tipoHabitacion'])
        ->findOrFail($reservaId);

    return response()->json($reserva, 201);
}


    public function update(UpdateReservaRequest $r, Reserva $reserva) {
        $reserva->update($r->validated());
        return $reserva->fresh();
    }
    /* 
    public function update(UpdateReservaRequest $r, Reserva $reserva) {
        // Guardamos cambios y notificamos
        $original = $reserva->replicate();
        $reserva->update($r->validated());

        $cambios = $reserva->getChanges();
        unset($cambios['updated_at']);

        $reservaFresh = $reserva->fresh()->load(['cliente']);

        if ($reservaFresh->cliente?->email) {
            $reservaFresh->cliente->notify(new \App\Notifications\ReservaActualizada($reservaFresh, $cambios));
        }

        return $reservaFresh;
    }
    */

    public function destroy(Reserva $reserva) {
        // si hay FKs dependientes, podrías impedir borrar o hacer soft delete
        $reserva->delete();
        return response()->noContent();
    }

    // ===== Acciones =====

    public function confirmar(Reserva $reserva) {
        // Confirmar la reserva - El Observer manejará el estado de las habitaciones
        $reserva->update(['id_estado_res' => EstadoReserva::ESTADO_CONFIRMADA]);
        return $reserva->fresh(['habitaciones.habitacion.estado']);
    }

public function checkIn($codigo)
{
    $reserva = Reserva::where('codigo', $codigo)->first();

    if (!$reserva) {
        return response()->json(['message' => 'Reserva no encontrada'], 404);
    }

    // Validar estado actual
    if ($reserva->estado !== 'confirmada') {
        return response()->json([
            'message' => 'No se puede realizar el check-in porque la reserva no está confirmada.'
        ], 400);
    }

    // Actualizar estado
    $reserva->estado = 'check_in';
    $reserva->fecha_check_in = now();
    $reserva->save();

    return response()->json([
        'message' => 'Check-in realizado exitosamente.',
        'reserva' => $reserva
    ], 200);
}







    public function cancelar(CancelReservaRequest $r, Reserva $reserva) {
        // 1) marcar estado cancelada - El Observer se encargará de liberar las habitaciones
        $reserva->update(['id_estado_res' => EstadoReserva::ESTADO_CANCELADA]);

        // 2) (Opcional) aplicar política si existe y si corresponde la ventana:
        // - Busca reserva_politica -> politica_cancelacion
        // - Evalúa ventana vs fecha_llegada más próxima en reserva_habitacions
        // - Calcula penalidad (porcentaje | noches) y *si quieres* registra un cargo en folio/reserva_pago

        // Recargar la reserva con las habitaciones actualizadas
        $reserva->load('habitaciones.habitacion.estado');

        return response()->json([
            'ok' => true,
            'message' => 'Reserva cancelada exitosamente. Las habitaciones han sido liberadas.',
            'reserva' => $reserva
        ]);
    }
    /* 
    public function cancelar(CancelReservaRequest $r, Reserva $reserva) {
        // Cambia estado a cancelada (ajusta el ID a tu catálogo)
        $reserva->update(['id_estado_res' =>  id estado cancelada  3]);

        // (Opcional) aplicar política de cancelación aquí...

        $reservaFresh = $reserva->fresh()->load(['cliente','habitaciones.habitacion','estado']);

        if ($reservaFresh->cliente?->email) {
            $reservaFresh->cliente->notify(new \App\Notifications\ReservaCancelada($reservaFresh));
        }

        return response()->json(['ok' => true]);
    }
    */

    public function noShow(Reserva $reserva) {
        $reserva->update(['id_estado_res' => EstadoReserva::ESTADO_NO_SHOW]);
        // (Opcional) aplicar penalidad de no-show según política
        // El Observer liberará las habitaciones automáticamente
        return $reserva->fresh(['habitaciones.habitacion.estado']);
    }

    public function cotizar(CotizarReservaRequest $r, Reserva $reserva) {
        $data = $r->validated();

        // Ejemplo simple: suma de servicios + (aquí iría tarifa * noches por habitación)
        $totalServicios = 0;
        if (!empty($data['servicios'])) {
            foreach ($data['servicios'] as $s) {
                $totalServicios += $s['cantidad'] * $s['precio_unitario'];
            }
        }

        // TODO: calcular tarifas por habitación según tu lógica (temporadas, tipo hab, etc.)
        $totalHabitaciones = 0;

        $total = round($totalHabitaciones + $totalServicios, 2);

        return response()->json([
            'total_habitaciones' => $totalHabitaciones,
            'total_servicios'    => $totalServicios,
            'total'              => $total
        ]);
    }

    /**
     * Realizar check-in de una reserva existente
     * POST /api/reservas/{reserva}/checkin
     */
    public function generarEstadia(Reserva $reserva, Request $req) {
        $data = $req->validate([
            'fecha_entrada' => 'nullable|date',
            'notas' => 'nullable|string|max:500',
        ]);

        try {
            return DB::transaction(function () use ($reserva, $data) {
                // Validaciones
                if ($reserva->id_estado_res != EstadoReserva::ESTADO_CONFIRMADA) {
                    throw new \Exception('Solo se puede hacer check-in a reservas confirmadas');
                }

                // Verificar que tiene pago mínimo
                if (!$reserva->alcanzoPagoMinimo()) {
                    throw new \Exception('La reserva no ha alcanzado el pago mínimo del 30%');
                }

                // Obtener la primera habitación de la reserva para datos de estadía
                $primeraHabitacion = $reserva->habitaciones()->first();

                if (!$primeraHabitacion) {
                    throw new \Exception('La reserva no tiene habitaciones asignadas');
                }

                // Crear estadía con datos de la reserva
                $estadia = Estadia::create([
                    'id_reserva' => $reserva->id_reserva,
                    'id_cliente_titular' => $reserva->id_cliente,
                    'fecha_llegada' => $primeraHabitacion->fecha_llegada,
                    'fecha_salida' => $primeraHabitacion->fecha_salida,
                    'fecha_entrada' => $data['fecha_entrada'] ?? now(),
                    'adultos' => $primeraHabitacion->adultos,
                    'ninos' => $primeraHabitacion->ninos,
                    'bebes' => $primeraHabitacion->bebes,
                    'id_fuente' => $reserva->id_fuente,
                ]);

                // Cambiar estado de reserva a Check-in
                $reserva->update(['id_estado_res' => EstadoReserva::ESTADO_CHECKIN]);

                // El Observer se encargará de cambiar las habitaciones a Ocupadas

                return response()->json([
                    'success' => true,
                    'message' => 'Check-in realizado exitosamente',
                    'data' => [
                        'id_estadia' => $estadia->id_estadia,
                        'id_reserva' => $reserva->id_reserva,
                        'codigo_reserva' => $reserva->codigo_formateado,
                        'fecha_entrada' => $estadia->fecha_entrada,
                        'fecha_salida_prevista' => $estadia->fecha_salida,
                        'cliente' => [
                            'id_cliente' => $reserva->cliente->id_cliente ?? null,
                            'nombre' => $reserva->cliente->nombre ?? null,
                            'apellido' => ($reserva->cliente->apellido1 ?? '') . ' ' . ($reserva->cliente->apellido2 ?? ''),
                        ],
                        'habitaciones' => $reserva->habitaciones->map(function ($rh) {
                            return [
                                'id_habitacion' => $rh->habitacion->id_habitacion ?? null,
                                'nombre' => $rh->habitacion->nombre ?? null,
                                'numero' => $rh->habitacion->numero ?? null,
                                'estado' => 'Ocupada',
                            ];
                        }),
                        'notas' => $data['notas'] ?? null,
                    ]
                ], 201);
            });

        } catch (\Exception $e) {
            Log::error('Error al hacer check-in', [
                'id_reserva' => $reserva->id_reserva,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al realizar check-in',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ===== Sistema de Pagos =====

    /**
     * Procesar un pago para la reserva
     * POST /api/reservas/{reserva}/pagos
     */
    public function procesarPago(ProcesarPagoRequest $request, Reserva $reserva)
    {
        try {
            $data = $request->validated();
            $exchangeService = app(ExchangeRateService::class);

            // Obtener usuario ANTES de la transacción
            $usuarioActual = $request->user();
            $creadoPor = $usuarioActual ? $usuarioActual->id : null; // null si no hay usuario autenticado

            $pago = DB::transaction(function () use ($reserva, $data, $exchangeService, $creadoPor) {
                // 1. Buscar la moneda por código
                $moneda = Moneda::where('codigo', strtoupper($data['codigo_moneda']))->first();

                if (!$moneda) {
                    throw new \Exception("Moneda con código {$data['codigo_moneda']} no encontrada en el sistema");
                }

                // 2. Obtener tipo de cambio actual
                $tipoCambio = $exchangeService->obtenerTipoCambio($data['codigo_moneda']);

                // 3. Calcular montos según la moneda
                $montoPago = $data['monto']; // Monto en la moneda especificada
                $montoUSD = null;

                if (strtoupper($data['codigo_moneda']) === 'USD') {
                    // Si ya es USD, no hay conversión
                    $montoUSD = $montoPago;
                    $tipoCambio = 1.000000;
                } else {
                    // Convertir de la moneda especificada a USD
                    $montoUSD = $exchangeService->convertirAUSD($montoPago, $data['codigo_moneda']);
                }

                // 4. Crear el pago con toda la información de moneda
                $pago = ReservaPago::create([
                    'id_reserva' => $reserva->id_reserva,
                    'id_metodo_pago' => $data['id_metodo_pago'],
                    'monto' => $montoPago,
                    'id_moneda' => $moneda->id_moneda,
                    'tipo_cambio' => $tipoCambio,
                    'monto_usd' => $montoUSD,
                    'id_estado_pago' => $data['id_estado_pago'],
                    'referencia' => $data['referencia'] ?? null,
                    'notas' => $data['notas'] ?? null,
                    'fecha_pago' => now(),
                    'creado_por' => $creadoPor,
                ]);

                // El Observer ReservaPagoObserver se encargará de:
                // 1. Actualizar monto_pagado y monto_pendiente en la reserva (usando monto_usd)
                // 2. Cambiar estado a Confirmada si alcanza el 30%

                return $pago;
            });

            $reserva->refresh()->load(['cliente','estado']);
        $pago->load('moneda', 'metodoPago', 'estadoPago');

        // Armamos payload para correo
        $porcentaje = ($reserva->total_monto_reserva > 0)
            ? round(($reserva->monto_pagado / $reserva->total_monto_reserva) * 100, 2)
            : 0;

        $hitos = [];
        // Marca los hitos según tu negocio
        if ($porcentaje >= 30 && $porcentaje < 100) {
            $hitos[] = '✅ Se alcanzó el anticipo mínimo del 30%. Tu reserva queda confirmada.';
        }
        if ($porcentaje >= 100 || ($reserva->monto_pendiente ?? 0) <= 0) {
            $hitos[] = '🎉 Pago completado al 100%. ¡Gracias!';
        }

        $payload = [
            'id_reserva_pago' => $pago->id_reserva_pago,
            'monto'           => $pago->monto,
            'moneda'          => [
                'codigo' => $pago->moneda->codigo,
                'nombre' => $pago->moneda->nombre,
            ],
            'tipo_cambio'           => $pago->tipo_cambio,
            'tipo_cambio_formateado'=> "1 USD = " . number_format($pago->tipo_cambio, 6) . " {$pago->moneda->codigo}",
            'monto_usd'             => $pago->monto_usd,
            'metodo_pago'           => $pago->metodoPago->nombre ?? null,
            'estado_pago'           => $pago->estadoPago->nombre ?? null,
            'referencia'            => $pago->referencia,
            'notas'                 => $pago->notas,
            'fecha_pago'            => $pago->fecha_pago,

            'reserva' => [
                'total_monto_reserva' => $reserva->total_monto_reserva,
                'monto_pagado'        => $reserva->monto_pagado,
                'monto_pendiente'     => $reserva->monto_pendiente,
                'porcentaje_pagado'   => $porcentaje,
            ],
            'hitos' => $hitos,
        ];

        // Enviar correo DESPUÉS del commit
        DB::afterCommit(function () use ($reserva, $payload) {
            $fresh = $reserva->fresh()->load(['cliente','estado','fuente','habitaciones.habitacion']);
            if ($fresh->cliente?->email) {
                try {
                    $fresh->cliente->notify(new ReservaPagoRecibido($payload, $fresh));
                } catch (\Throwable $e) {
                    Log::error('Error enviando correo de pago recibido: '.$e->getMessage(), [
                        'reserva_id' => $fresh->id_reserva,
                    ]);
                }
            }
        });

            return response()->json([
                'success' => true,
                'message' => 'Pago procesado exitosamente',
                'data' => [
                    'id_reserva_pago' => $pago->id_reserva_pago,
                    'monto' => $pago->monto,
                    'moneda' => [
                        'codigo' => $pago->moneda->codigo,
                        'nombre' => $pago->moneda->nombre,
                    ],
                    'tipo_cambio' => $pago->tipo_cambio,
                    'tipo_cambio_formateado' => "1 USD = " . number_format($pago->tipo_cambio, 6) . " {$pago->moneda->codigo}",
                    'monto_usd' => $pago->monto_usd,
                    'metodo_pago' => $pago->metodoPago->nombre ?? null,
                    'estado_pago' => $pago->estadoPago->nombre ?? null,
                    'referencia' => $pago->referencia,
                    'notas' => $pago->notas,
                    'fecha_pago' => $pago->fecha_pago,
                ],
                'reserva' => [
                    'id_reserva' => $reserva->id_reserva,
                    'id_estado_res' => $reserva->id_estado_res,
                    'estado' => $reserva->estado->nombre ?? null,
                    'total_monto_reserva' => $reserva->total_monto_reserva,
                    'monto_pagado' => $reserva->monto_pagado,
                    'monto_pendiente' => $reserva->monto_pendiente,
                    'pago_completo' => $reserva->pago_completo,
                    'porcentaje_pagado' => $reserva->total_monto_reserva > 0
                        ? round(($reserva->monto_pagado / $reserva->total_monto_reserva) * 100, 2)
                        : 0,
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al procesar pago', [
                'id_reserva' => $reserva->id_reserva,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar todos los pagos de una reserva
     * GET /api/reservas/{reserva}/pagos
     */
    public function listarPagos(Reserva $reserva)
    {
        $pagos = $reserva->pagos()
            ->with(['metodoPago', 'estadoPago', 'moneda'])
            ->orderBy('fecha_pago', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'reserva_id' => $reserva->id_reserva,
                'total_reserva' => $reserva->total_monto_reserva,
                'monto_pagado' => $reserva->monto_pagado,
                'monto_pendiente' => $reserva->monto_pendiente,
                'pago_completo' => $reserva->pago_completo,
                'porcentaje_pagado' => $reserva->total_monto_reserva > 0
                    ? round(($reserva->monto_pagado / $reserva->total_monto_reserva) * 100, 2)
                    : 0,
                'pagos' => $pagos->map(function ($pago) {
                    return [
                        'id_reserva_pago' => $pago->id_reserva_pago,
                        'monto' => $pago->monto,
                        'moneda' => [
                            'codigo' => $pago->moneda->codigo ?? 'USD',
                            'nombre' => $pago->moneda->nombre ?? 'Dólar Estadounidense',
                        ],
                        'tipo_cambio' => $pago->tipo_cambio,
                        'monto_usd' => $pago->monto_usd,
                        'fecha' => $pago->fecha_pago,
                        'metodo_pago' => $pago->metodoPago->nombre ?? null,
                        'estado' => $pago->estadoPago->nombre ?? null,
                        'referencia' => $pago->referencia,
                        'notas' => $pago->notas,
                    ];
                })
            ]
        ]);
    }

    // ===== Sistema de Cancelación =====

    /**
     * Preview de cancelación (muestra reembolso sin confirmar)
     * GET /api/reservas/{reserva}/cancelacion/preview
     */
    // public function previewCancelacion(Reserva $reserva)
    // {
    //     try {
    //         // Obtener la fecha de llegada más próxima
    //         $primeraLlegada = $reserva->habitaciones()
    //             ->orderBy('fecha_llegada', 'asc')
    //             ->first();

    //         if (!$primeraLlegada) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'No se encontraron habitaciones en esta reserva'
    //             ], 400);
    //         }

    //         $fechaLlegada = Carbon::parse($primeraLlegada->fecha_llegada);
    //         $hoy = Carbon::now();
    //         $diasAnticipacion = $hoy->diffInDays($fechaLlegada, false);

    //         // Si la fecha ya pasó, días negativos
    //         if ($diasAnticipacion < 0) {
    //             $diasAnticipacion = 0; // Tratarlo como no-show
    //         }

    //         // Calcular reembolso según política
    //         $resultado = PoliticaCancelacion::calcularReembolso(
    //             $reserva->monto_pagado,
    //             (int) $diasAnticipacion
    //         );

    //         return response()->json([
    //             'success' => true,
    //             'data' => [
    //                 'puede_cancelar' => true,
    //                 'dias_anticipacion' => (int) $diasAnticipacion,
    //                 'fecha_llegada' => $fechaLlegada->format('Y-m-d'),
    //                 'politica_aplicada' => [
    //                     'id_politica' => $resultado['politica']->id_politica,
    //                     'nombre' => $resultado['politica']->nombre,
    //                     'descripcion' => $resultado['politica']->descripcion,
    //                 ],
    //                 'monto_pagado' => $reserva->monto_pagado,
    //                 'reembolso' => $resultado['reembolso'],
    //                 'penalidad' => $resultado['penalidad'],
    //                 'porcentaje_reembolso' => $reserva->monto_pagado > 0
    //                     ? round(($resultado['reembolso'] / $reserva->monto_pagado) * 100, 2)
    //                     : 0,
    //                 'mensaje' => $resultado['mensaje'],
    //             ]
    //         ]);

    //     } catch (\Exception $e) {
    //         Log::error('Error en preview de cancelación', [
    //             'id_reserva' => $reserva->id_reserva,
    //             'error' => $e->getMessage()
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al calcular preview de cancelación',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // /**
    //  * Confirmar cancelación con cálculo de reembolso
    //  * POST /api/reservas/{reserva}/cancelar-con-politica
    //  */
    // public function cancelarConPolitica(Request $request, Reserva $reserva)
    // {
    //     $request->validate([
    //         'motivo' => 'nullable|string|max:500',
    //         'solicitar_reembolso' => 'boolean',
    //     ]);

    //     try {
    //         return DB::transaction(function () use ($request, $reserva) {
    //             // Obtener preview de cancelación
    //             $previewResponse = $this->previewCancelacion($reserva);
    //             $preview = $previewResponse->getData(true)['data'];

    //             // Guardar estado anterior
    //             $estadoAnterior = $reserva->estado->nombre ?? 'Desconocido';

    //             // Cambiar estado a cancelada (el Observer liberará las habitaciones)
    //             $reserva->update([
    //                 'id_estado_res' => EstadoReserva::ESTADO_CANCELADA
    //             ]);

    //             // Recargar reserva con relaciones
    //             $reserva->load('habitaciones.habitacion.estado');

    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'Reserva cancelada exitosamente',
    //                 'data' => [
    //                     'id_reserva' => $reserva->id_reserva,
    //                     'estado_anterior' => $estadoAnterior,
    //                     'estado_actual' => 'Cancelada',
    //                     'fecha_cancelacion' => now()->format('Y-m-d H:i:s'),
    //                     'dias_anticipacion' => $preview['dias_anticipacion'],
    //                     'politica' => $preview['politica_aplicada']['nombre'],
    //                     'monto_pagado' => $preview['monto_pagado'],
    //                     'reembolso' => $preview['reembolso'],
    //                     'penalidad' => $preview['penalidad'],
    //                     'habitaciones_liberadas' => $reserva->habitaciones->pluck('id_habitacion')->toArray(),
    //                     'motivo' => $request->input('motivo'),
    //                 ]
    //             ]);
    //         });

    //     } catch (\Exception $e) {
    //         Log::error('Error al cancelar reserva', [
    //             'id_reserva' => $reserva->id_reserva,
    //             'error' => $e->getMessage()
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al cancelar la reserva',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }
    public function previewCancelacion(Reserva $reserva)
    {
        try {
            $preview = $this->buildPreviewCancelacion($reserva);

            return response()->json([
                'success' => true,
                'data'    => $preview,
            ]);

        } catch (\Throwable $e) {
            Log::error('Error en preview de cancelación', [
                'id_reserva' => $reserva->id_reserva,
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al calcular preview de cancelación',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirmar cancelación con cálculo de reembolso
     * POST /api/reservas/{reserva}/cancelar-con-politica
     */
    public function cancelarConPolitica(Request $request, Reserva $reserva)
    {
        $request->validate([
            'motivo'               => 'nullable|string|max:500',
            'solicitar_reembolso'  => 'boolean',
        ]);

        try {
            return DB::transaction(function () use ($request, $reserva) {

                // 1) Calcula preview (array puro, sin Response)
                $preview = $this->buildPreviewCancelacion($reserva);

                // 2) Guarda estado anterior y actualiza a Cancelada
                $estadoAnterior = $reserva->estado->nombre ?? 'Desconocido';

                $reserva->update([
                    'id_estado_res' => EstadoReserva::ESTADO_CANCELADA,
                    // opcional: 'motivo_cancelacion' => $request->input('motivo'),
                ]);

                // 3) Recarga relaciones (el Observer liberará habitaciones)
                $reserva->load('cliente','estado','habitaciones.habitacion.estado');

                // 4) Construye payload final para notificación / respuesta
                $payload = [
                    'id_reserva'        => $reserva->id_reserva,
                    'estado_anterior'   => $estadoAnterior,
                    'estado_actual'     => 'Cancelada',
                    'fecha_cancelacion' => now()->format('Y-m-d H:i:s'),
                    'dias_anticipacion' => $preview['dias_anticipacion'],
                    'politica'          => $preview['politica_aplicada'],
                    'monto_pagado'      => $preview['monto_pagado'],
                    'reembolso'         => $preview['reembolso'],
                    'penalidad'         => $preview['penalidad'],
                    'porcentaje_reembolso' => $preview['porcentaje_reembolso'],
                    'mensaje'           => $preview['mensaje'],
                    'habitaciones_liberadas' => $reserva->habitaciones->pluck('id_habitacion')->toArray(),
                    'motivo'            => $request->input('motivo'),
                    'solicitar_reembolso' => (bool)$request->boolean('solicitar_reembolso'),
                ];

                // 5) Notificar después del commit para evitar correos en rollback
                DB::afterCommit(function () use ($reserva, $payload) {
                    $fresh = $reserva->fresh()->load(['cliente','estado','fuente','habitaciones.habitacion']);
                    if ($fresh->cliente?->email) {
                        try {
                            $fresh->cliente->notify(new ReservaCancelada($payload, $fresh));
                        } catch (\Throwable $e) {
                            Log::error('Error enviando correo de cancelación: '.$e->getMessage(), [
                                'reserva_id' => $fresh->id_reserva,
                            ]);
                        }
                    }
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Reserva cancelada exitosamente',
                    'data'    => $payload,
                ]);
            });

        } catch (\Throwable $e) {
            Log::error('Error al cancelar reserva', [
                'id_reserva' => $reserva->id_reserva,
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar la reserva',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * --- PRIVADO ---
     * Cálculo de preview (array puro) para reutilizar en ambos flujos.
     */
    private function buildPreviewCancelacion(Reserva $reserva): array
    {
        // 1) Primera llegada
        $primeraLlegada = $reserva->habitaciones()
            ->orderBy('fecha_llegada', 'asc')
            ->first();

        if (!$primeraLlegada) {
            throw new \RuntimeException('No se encontraron habitaciones en esta reserva');
        }

        // 2) Anticipación: si ya pasó, tratar como 0 (no-show)
        $fechaLlegada     = Carbon::parse($primeraLlegada->fecha_llegada);
        $hoy              = Carbon::now();
        $diasAnticipacion = $hoy->diffInDays($fechaLlegada, false);
        if ($diasAnticipacion < 0) {
            $diasAnticipacion = 0;
        }

        // 3) Política: usa el método que manejes (coherente con tu proyecto)
        // Si en otros lados usas calcularReembolsoHotelLanaku(...), unifica aquí.
        $resultado = PoliticaCancelacion::calcularReembolso(
            $reserva->monto_pagado,
            (int) $diasAnticipacion
        );

        return [
            'puede_cancelar'   => true,
            'dias_anticipacion'=> (int) $diasAnticipacion,
            'fecha_llegada'    => $fechaLlegada->format('Y-m-d'),
            'politica_aplicada'=> [
                'id_politica' => $resultado['politica']->id_politica ?? null,
                'nombre'      => $resultado['politica']->nombre     ?? 'N/D',
                'descripcion' => $resultado['politica']->descripcion ?? null,
            ],
            'monto_pagado'     => $reserva->monto_pagado,
            'reembolso'        => $resultado['reembolso'],
            'penalidad'        => $resultado['penalidad'],
            'porcentaje_reembolso' => $reserva->monto_pagado > 0
                ? round(($resultado['reembolso'] / $reserva->monto_pagado) * 100, 2)
                : 0,
            'mensaje'          => $resultado['mensaje'] ?? null,
        ];
    }

    // ===== Sistema de Extensión de Estadía =====

    /**
     * Extender estadía
     * POST /api/reservas/{reserva}/extender
     */
    public function extenderEstadia(ExtenderEstadiaRequest $request, Reserva $reserva)
    {
        try {
            $data = $request->validated();
            $service = app(ExtensionEstadiaService::class);

            // Buscar la reserva de habitación
            $reservaHab = ReservaHabitacion::findOrFail($data['id_reserva_habitacion']);

            // Verificar que pertenece a esta reserva
            if ($reservaHab->id_reserva != $reserva->id_reserva) {
                return response()->json([
                    'success' => false,
                    'message' => 'La habitación no pertenece a esta reserva'
                ], 400);
            }

            $nuevaFechaSalida = Carbon::parse($data['nueva_fecha_salida']);

            // Primero verificar disponibilidad en la misma habitación
            $disponibilidad = $service->verificarDisponibilidadMismaHabitacion(
                $reservaHab,
                $nuevaFechaSalida
            );

            if ($disponibilidad['disponible']) {
                // Extender en la misma habitación
                $resultado = $service->procesarExtension(
                    $reserva,
                    $reservaHab,
                    $data['noches_adicionales'],
                    $nuevaFechaSalida
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Estadía extendida exitosamente en la misma habitación',
                    'data' => $resultado
                ]);

            } else {
                // Buscar alternativas
                $alternativas = $service->buscarHabitacionesAlternativas(
                    $reservaHab,
                    Carbon::parse($reservaHab->fecha_salida),
                    $nuevaFechaSalida
                );

                return response()->json([
                    'success' => false,
                    'message' => 'La habitación actual no está disponible. Se encontraron alternativas.',
                    'data' => [
                        'tipo_extension' => 'requiere_cambio',
                        'habitacion_actual' => [
                            'id_habitacion' => $reservaHab->habitacion->id_habitacion,
                            'nombre' => $reservaHab->habitacion->nombre,
                            'disponible' => false,
                            'fecha_conflicto' => $disponibilidad['fecha_conflicto'] ?? null,
                        ],
                        'habitaciones_alternativas' => $alternativas->map(function ($hab) use ($data, $reservaHab) {
                            $fechaInicio = Carbon::parse($reservaHab->fecha_salida);
                            $fechaFin = Carbon::parse($data['nueva_fecha_salida']);
                            $noches = $fechaInicio->diffInDays($fechaFin);

                            return [
                                'id_habitacion' => $hab->id_habitacion,
                                'nombre' => $hab->nombre,
                                'numero' => $hab->numero,
                                'tipo' => $hab->tipoHabitacion->nombre ?? null,
                                'tarifa_noche' => $hab->tipoHabitacion->precio_base ?? 0,
                                'disponible_desde' => $fechaInicio->format('Y-m-d'),
                                'disponible_hasta' => $fechaFin->format('Y-m-d'),
                                'noches' => $noches,
                                'monto_adicional' => $noches * ($hab->tipoHabitacion->precio_base ?? 0),
                            ];
                        })
                    ]
                ], 422);
            }

        } catch (\Exception $e) {
            Log::error('Error al extender estadía', [
                'id_reserva' => $reserva->id_reserva,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al extender la estadía',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirmar extensión con cambio de habitación
     * POST /api/reservas/{reserva}/extender/confirmar
     */
    public function confirmarExtensionCambioHabitacion(Request $request, Reserva $reserva)
    {
        $data = $request->validate([
            'id_reserva_habitacion_original' => 'required|exists:reserva_habitacions,id_reserva_hab',
            'id_habitacion_nueva' => 'required|exists:habitaciones,id_habitacion',
            'noches_adicionales' => 'required|integer|min:1',
            'nueva_fecha_salida' => 'required|date',
            'tarifa_noche' => 'required|numeric|min:0',
        ]);

        try {
            return DB::transaction(function () use ($data, $reserva) {
                $service = app(ExtensionEstadiaService::class);

                $reservaHabOriginal = ReservaHabitacion::findOrFail($data['id_reserva_habitacion_original']);

                $resultado = $service->procesarExtension(
                    $reserva,
                    $reservaHabOriginal,
                    $data['noches_adicionales'],
                    Carbon::parse($data['nueva_fecha_salida']),
                    $data['id_habitacion_nueva']
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Extensión confirmada con cambio de habitación',
                    'data' => $resultado
                ]);
            });

        } catch (\Exception $e) {
            Log::error('Error al confirmar extensión con cambio', [
                'id_reserva' => $reserva->id_reserva,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar la extensión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ===== Sistema de Códigos de Reserva =====

    /**
     * Buscar reserva por código
     * GET /api/reservas/buscar?codigo=XXXX-XXXX
     */
    public function buscarPorCodigo(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string|min:8|max:20'
        ]);

        try {
            $service = app(CodigoReservaService::class);
            $reserva = $service->buscarPorCodigo($request->input('codigo'));

            if (!$reserva) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró ninguna reserva con ese código'
                ], 404);
            }

            $reserva->load([
                'cliente',
                'estado',
                'fuente',
                'habitaciones.habitacion',
                'pagos'
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id_reserva' => $reserva->id_reserva,
                    'codigo_reserva' => $reserva->codigo_reserva,
                    'codigo_formateado' => $reserva->codigo_formateado,
                    'cliente' => [
                        'id_cliente' => $reserva->cliente->id_cliente,
                        'nombre' => $reserva->cliente->nombre,
                        'apellido' => $reserva->cliente->apellido1 . ' ' . $reserva->cliente->apellido2,
                        'email' => $reserva->cliente->email,
                    ],
                    'estado' => $reserva->estado->nombre ?? null,
                    'fecha_creacion' => $reserva->fecha_creacion,
                    'habitaciones' => $reserva->habitaciones->map(function ($rh) {
                        return [
                            'habitacion' => $rh->habitacion->nombre ?? null,
                            'numero' => $rh->habitacion->numero ?? null,
                            'fecha_llegada' => $rh->fecha_llegada,
                            'fecha_salida' => $rh->fecha_salida,
                            'adultos' => $rh->adultos,
                            'ninos' => $rh->ninos,
                        ];
                    }),
                    'total_monto_reserva' => $reserva->total_monto_reserva,
                    'monto_pagado' => $reserva->monto_pagado,
                    'monto_pendiente' => $reserva->monto_pendiente,
                    'pago_completo' => $reserva->pago_completo,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al buscar reserva por código', [
                'codigo' => $request->input('codigo'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al buscar la reserva',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas del sistema de códigos
     * GET /api/reservas/codigos/estadisticas
     */
    public function estadisticasCodigos()
    {
        try {
            $service = app(CodigoReservaService::class);
            $stats = $service->obtenerEstadisticas();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas de códigos', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ===== Sistema de Monedas y Tipos de Cambio =====

    /**
     * Obtener lista de monedas soportadas
     * GET /api/monedas/soportadas
     */
    public function monedasSoportadas()
    {
        try {
            $exchangeService = app(ExchangeRateService::class);
            $monedasSoportadas = $exchangeService->obtenerMonedasSoportadas();

            // Obtener las monedas que existen en la base de datos
            $monedasDB = Moneda::all()->keyBy('codigo');

            $monedas = [];
            foreach ($monedasSoportadas as $codigo => $nombre) {
                $monedas[] = [
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'en_base_datos' => isset($monedasDB[$codigo]),
                    'id_moneda' => $monedasDB[$codigo]->id_moneda ?? null,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $monedas
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener monedas soportadas', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener monedas soportadas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener tipos de cambio actuales
     * GET /api/monedas/tipos-cambio
     */
    public function tiposDeCambio()
    {
        try {
            $exchangeService = app(ExchangeRateService::class);
            $tasas = $exchangeService->obtenerTiposDeCambio();

            return response()->json([
                'success' => true,
                'data' => [
                    'moneda_base' => 'USD',
                    'fecha_actualizacion' => now()->format('Y-m-d H:i:s'),
                    'cache_valido_hasta' => now()->addHours(12)->format('Y-m-d H:i:s'),
                    'tipos_cambio' => $tasas,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener tipos de cambio', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de cambio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convertir monto entre monedas
     * GET /api/monedas/convertir?monto=100&desde=USD&hasta=CRC
     */
    public function convertirMoneda(Request $request)
    {
        $request->validate([
            'monto' => 'required|numeric|min:0',
            'desde' => 'required|string|size:3',
            'hasta' => 'required|string|size:3',
        ]);

        try {
            $exchangeService = app(ExchangeRateService::class);

            $resultado = $exchangeService->convertir(
                $request->input('monto'),
                $request->input('desde'),
                $request->input('hasta')
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'monto_original' => $request->input('monto'),
                    'moneda_origen' => $request->input('desde'),
                    'monto_convertido' => $resultado['monto_convertido'],
                    'moneda_destino' => $request->input('hasta'),
                    'tipo_cambio' => $resultado['tipo_cambio'],
                    'formula' => "1 {$request->input('desde')} = {$resultado['tipo_cambio']} {$request->input('hasta')}",
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al convertir moneda', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al convertir moneda',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    // ===== Sistema de Modificación de Reservas =====

    /**
     * Cambiar habitación sin extensión de fechas
     * POST /api/reservas/{reserva}/modificar/cambiar-habitacion
     */
    public function cambiarHabitacion(CambiarHabitacionRequest $request, Reserva $reserva)
    {
        try {
            $service = app(ModificacionReservaService::class);

            $resultado = $service->cambiarHabitacion(
                $reserva,
                $request->id_reserva_habitacion,
                $request->id_habitacion_nueva,
                $request->motivo
            );

            return response()->json([
                'success' => true,
                'message' => 'Habitación cambiada exitosamente',
                'data' => $resultado
            ]);

        } catch (\Exception $e) {
            Log::error('Error al cambiar habitación', [
                'id_reserva' => $reserva->id_reserva,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar la habitación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Modificar fechas de reserva (check-in y/o check-out)
     * POST /api/reservas/{reserva}/modificar/cambiar-fechas
     */
    public function modificarFechas(ModificarFechasRequest $request, Reserva $reserva)
    {
        try {
            $service = app(ModificacionReservaService::class);

            $resultado = $service->modificarFechas(
                $reserva,
                $request->id_reserva_habitacion,
                $request->nueva_fecha_llegada ? Carbon::parse($request->nueva_fecha_llegada) : null,
                $request->nueva_fecha_salida ? Carbon::parse($request->nueva_fecha_salida) : null,
                $request->aplicar_politica ?? true
            );

            return response()->json([
                'success' => true,
                'message' => 'Fechas modificadas exitosamente',
                'data' => $resultado
            ]);

        } catch (\Exception $e) {
            Log::error('Error al modificar fechas', [
                'id_reserva' => $reserva->id_reserva,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al modificar las fechas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reducir estadía (checkout anticipado)
     * POST /api/reservas/{reserva}/modificar/reducir-estadia
     */
    public function reducirEstadia(ReducirEstadiaRequest $request, Reserva $reserva)
    {
        try {
            $service = app(ModificacionReservaService::class);

            $resultado = $service->reducirEstadia(
                $reserva,
                $request->id_reserva_habitacion,
                Carbon::parse($request->nueva_fecha_salida),
                $request->aplicar_politica ?? true
            );

            return response()->json([
                'success' => true,
                'message' => 'Estadía reducida exitosamente',
                'data' => $resultado
            ]);

        } catch (\Exception $e) {
            Log::error('Error al reducir estadía', [
                'id_reserva' => $reserva->id_reserva,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al reducir la estadía',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar estado de la reserva (check-in, check-out, etc.)
     * PUT /api/reservas/{reserva}/estado
     *
     * @param CambiarEstadoReservaRequest $request
     * @param Reserva $reserva
     * @return \Illuminate\Http\JsonResponse
     */
    public function cambiarEstado(CambiarEstadoReservaRequest $request, Reserva $reserva)
    {
        try {
            $data = $request->validated();

            // Guardar estado anterior para el log
            $estadoAnterior = $reserva->estado->nombre ?? 'Desconocido';
            $idEstadoAnterior = $reserva->id_estado_res;

            return DB::transaction(function () use ($reserva, $data, $estadoAnterior, $idEstadoAnterior) {
                // Actualizar el estado
                $reserva->update([
                    'id_estado_res' => $data['id_estado_res'],
                ]);

                // Si se proporcionaron notas, agregarlas
                if (!empty($data['notas'])) {
                    $notaActual = $reserva->notas ?? '';
                    $timestamp = now()->format('Y-m-d H:i:s');
                    $nuevaNota = "[{$timestamp}] Cambio de estado: {$estadoAnterior} → {$reserva->estado->nombre}. {$data['notas']}";

                    $reserva->update([
                        'notas' => $notaActual ? $notaActual . "\n\n" . $nuevaNota : $nuevaNota
                    ]);
                }

                // Recargar con relaciones
                $reserva->load(['cliente', 'estado', 'fuente', 'habitaciones.habitacion.estado']);

                // Log del cambio
                Log::info('Estado de reserva actualizado', [
                    'id_reserva' => $reserva->id_reserva,
                    'estado_anterior' => $estadoAnterior,
                    'id_estado_anterior' => $idEstadoAnterior,
                    'estado_nuevo' => $reserva->estado->nombre,
                    'id_estado_nuevo' => $data['id_estado_res'],
                    'notas' => $data['notas'] ?? null,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Estado actualizado de '{$estadoAnterior}' a '{$reserva->estado->nombre}'",
                    'data' => [
                        'id_reserva' => $reserva->id_reserva,
                        'estado_anterior' => [
                            'id' => $idEstadoAnterior,
                            'nombre' => $estadoAnterior
                        ],
                        'estado_actual' => [
                            'id' => $reserva->id_estado_res,
                            'nombre' => $reserva->estado->nombre ?? 'Desconocido'
                        ],
                        'fecha_cambio' => now()->format('Y-m-d H:i:s'),
                        'reserva' => $reserva
                    ]
                ]);
            });

        } catch (\Exception $e) {
            Log::error('Error al cambiar estado de reserva', [
                'id_reserva' => $reserva->id_reserva,
                'id_estado_solicitado' => $request->id_estado_res ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado de la reserva',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Realizar check-in de una reserva
     * POST /api/reservas/{reserva}/realizar-checkin
     *
     * @param CheckInCheckOutRequest $request
     * @param Reserva $reserva
     * @return \Illuminate\Http\JsonResponse
     */
    public function realizarCheckIn(CheckInCheckOutRequest $request, Reserva $reserva)
    {
        try {
            $data = $request->validated();

            return DB::transaction(function () use ($reserva, $data) {
                // Guardar estado anterior
                $estadoAnterior = $reserva->estado->nombre ?? 'Desconocido';

                // Cambiar estado a Check-in (ID 4)
                $reserva->update([
                    'id_estado_res' => 4, // Check-in
                ]);

                // Agregar notas si se proporcionaron
                if (!empty($data['notas'])) {
                    $notaActual = $reserva->notas ?? '';
                    $timestamp = now()->format('Y-m-d H:i:s');
                    $nuevaNota = "[{$timestamp}] Check-in realizado. {$data['notas']}";

                    $reserva->update([
                        'notas' => $notaActual ? $notaActual . "\n\n" . $nuevaNota : $nuevaNota
                    ]);
                }

                // Recargar con relaciones
                $reserva->load(['cliente', 'estado', 'fuente', 'habitaciones.habitacion.estado']);

                // Log del cambio
                Log::info('Check-in realizado', [
                    'id_reserva' => $reserva->id_reserva,
                    'estado_anterior' => $estadoAnterior,
                    'notas' => $data['notas'] ?? null,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Check-in realizado exitosamente',
                    'data' => [
                        'id_reserva' => $reserva->id_reserva,
                        'estado_anterior' => $estadoAnterior,
                        'estado_actual' => 'Check-in',
                        'fecha_checkin' => now()->format('Y-m-d H:i:s'),
                        'reserva' => $reserva
                    ]
                ]);
            });

        } catch (\Exception $e) {
            Log::error('Error al realizar check-in', [
                'id_reserva' => $reserva->id_reserva,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al realizar check-in',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Realizar check-out de una reserva
     * POST /api/reservas/{reserva}/realizar-checkout
     *
     * @param CheckInCheckOutRequest $request
     * @param Reserva $reserva
     * @return \Illuminate\Http\JsonResponse
     */
    public function realizarCheckOut(CheckInCheckOutRequest $request, Reserva $reserva)
    {
        try {
            $data = $request->validated();

            return DB::transaction(function () use ($reserva, $data) {
                // Guardar estado anterior
                $estadoAnterior = $reserva->estado->nombre ?? 'Desconocido';

                // Cambiar estado a Check-out (ID 5)
                $reserva->update([
                    'id_estado_res' => 5, // Check-out
                ]);

                // Agregar notas si se proporcionaron
                if (!empty($data['notas'])) {
                    $notaActual = $reserva->notas ?? '';
                    $timestamp = now()->format('Y-m-d H:i:s');
                    $nuevaNota = "[{$timestamp}] Check-out realizado. {$data['notas']}";

                    $reserva->update([
                        'notas' => $notaActual ? $notaActual . "\n\n" . $nuevaNota : $nuevaNota
                    ]);
                }

                // Recargar con relaciones
                $reserva->load(['cliente', 'estado', 'fuente', 'habitaciones.habitacion.estado']);

                // Log del cambio
                Log::info('Check-out realizado', [
                    'id_reserva' => $reserva->id_reserva,
                    'estado_anterior' => $estadoAnterior,
                    'notas' => $data['notas'] ?? null,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Check-out realizado exitosamente',
                    'data' => [
                        'id_reserva' => $reserva->id_reserva,
                        'estado_anterior' => $estadoAnterior,
                        'estado_actual' => 'Check-out',
                        'fecha_checkout' => now()->format('Y-m-d H:i:s'),
                        'reserva' => $reserva
                    ]
                ]);
            });

        } catch (\Exception $e) {
            Log::error('Error al realizar check-out', [
                'id_reserva' => $reserva->id_reserva,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al realizar check-out',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
