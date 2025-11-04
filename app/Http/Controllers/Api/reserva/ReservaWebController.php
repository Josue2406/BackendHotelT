<?php
namespace App\Http\Controllers\Api\reserva;

use App\Http\Controllers\Controller;
use App\Http\Requests\reserva\web\StoreReservaWebRequest;
use App\Http\Requests\reserva\web\UpdateReservaWebRequest;
use App\Http\Requests\reserva\web\CancelReservaWebRequest;
use App\Models\reserva\Reserva;
use App\Models\reserva\ReservaHabitacion;
use App\Models\reserva\EstadoReserva;
use App\Models\cliente\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Notifications\ReservaCreada;
use App\Notifications\ReservaActualizada;
use App\Notifications\ReservaCancelada;

class ReservaWebController extends Controller
{
    /**
     * Listar todas las reservas del cliente autenticado
     * GET /api/reservas-web
     */
    public function index(Request $request)
    {
        // Obtener el cliente autenticado
        $cliente = auth('sanctum')->user();

        if (!$cliente || !($cliente instanceof Cliente)) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no autenticado'
            ], 401);
        }

        $query = Reserva::with(['cliente', 'estado', 'fuente', 'habitaciones.habitacion.tipoHabitacion'])
            ->where('id_cliente', $cliente->id_cliente);

        // Filtros opcionales
        if ($request->filled('estado')) {
            $nombreEstado = $request->input('estado');
            $query->whereHas('estado', function($qEstado) use ($nombreEstado) {
                $qEstado->where('nombre', 'like', "%{$nombreEstado}%");
            });
        }

        if ($request->filled('desde')) {
            $desde = $request->input('desde');
            $query->where('fecha_creacion', '>=', $desde);
        }

        if ($request->filled('hasta')) {
            $hasta = $request->input('hasta');
            $query->where('fecha_creacion', '<=', $hasta);
        }

        return $query->latest('id_reserva')->paginate(20);
    }

    /**
     * Ver detalle de una reserva específica del cliente
     * GET /api/reservas-web/{reserva}
     */
    public function show(Reserva $reserva)
    {
        // Obtener el cliente autenticado
        $cliente = auth('sanctum')->user();

        if (!$cliente || !($cliente instanceof Cliente)) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no autenticado'
            ], 401);
        }

        // Verificar que la reserva pertenece al cliente autenticado
        if ($reserva->id_cliente !== $cliente->id_cliente) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para ver esta reserva'
            ], 403);
        }

        return $reserva->load([
            'cliente',
            'estado',
            'fuente',
            'habitaciones.habitacion.tipoHabitacion',
            'servicios',
            'politicas',
            'pagos'
        ]);
    }

    /**
     * Crear una nueva reserva desde la web (cliente autenticado)
     * POST /api/reservas-web
     */
    public function store(StoreReservaWebRequest $request)
    {
        $data = $request->validated();

        // Obtener el cliente autenticado
        $cliente = auth('sanctum')->user();

        if (!$cliente || !($cliente instanceof Cliente)) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no autenticado'
            ], 401);
        }

        // SEGURIDAD: Siempre usar el id_cliente del token autenticado
        $data['id_cliente'] = $cliente->id_cliente;

        Log::info('Reserva web creada por cliente autenticado', [
            'id_cliente' => $data['id_cliente'],
            'email' => $cliente->email ?? null
        ]);

        // Preparar datos de la reserva
        $data['fecha_creacion'] = now();
        $data['total_monto_reserva'] = 0;

        // Las reservas web se crean directamente en estado "Confirmada" (ID 3)
        $data['id_estado_res'] = 3; // Estado Confirmada

        // Si no se especifica fuente, usar "Web" (ajustar según tu catálogo)
        if (!isset($data['id_fuente'])) {
            $data['id_fuente'] = 2; // Asumiendo que 2 es "Web", ajusta según tu BD
        }

        // Crear reserva + habitaciones dentro de transacción
        $reservaId = DB::transaction(function () use ($data) {
            $habitaciones = $data['habitaciones'];
            unset($data['habitaciones']);

            $reserva = Reserva::create($data);

            $totalReserva = 0;
            foreach ($habitaciones as $hab) {
                // Verificar disponibilidad
                $choqueReserva = ReservaHabitacion::where('id_habitacion', $hab['id_habitacion'])
                    ->where('fecha_llegada', '<', $hab['fecha_salida'])
                    ->where('fecha_salida', '>', $hab['fecha_llegada'])
                    ->exists();

                if ($choqueReserva) {
                    throw new \Exception("La habitación {$hab['id_habitacion']} no está disponible en el rango especificado.");
                }

                // Crear item de habitación
                $reservaHab = $reserva->habitaciones()->create([
                    'id_habitacion' => $hab['id_habitacion'],
                    'fecha_llegada' => $hab['fecha_llegada'],
                    'fecha_salida'  => $hab['fecha_salida'],
                    'adultos'       => $hab['adultos'],
                    'ninos'         => $hab['ninos'],
                    'bebes'         => $hab['bebes'],
                    'subtotal'      => 0,
                ]);

                // Calcular subtotal
                $reservaHab->load('habitacion');
                $subtotal = $reservaHab->calcularSubtotal();
                $reservaHab->update(['subtotal' => $subtotal]);
                $totalReserva += $subtotal;
            }

            // Actualizar total de la reserva
            $reserva->update(['total_monto_reserva' => $totalReserva]);

            // Enviar correo después del commit
            DB::afterCommit(function () use ($reserva) {
                $fresh = $reserva->fresh()->load([
                    'cliente',
                    'estado',
                    'fuente',
                    'habitaciones.habitacion.tipoHabitacion'
                ]);

                if ($fresh->cliente?->email) {
                    $fresh->cliente->notify(new ReservaCreada($fresh));
                }
            });

            return $reserva->id_reserva;
        });

        // Respuesta
        $reserva = Reserva::with([
            'cliente',
            'estado',
            'fuente',
            'habitaciones.habitacion.tipoHabitacion'
        ])->findOrFail($reservaId);

        return response()->json($reserva, 201);
    }

    /**
     * Modificar una reserva existente (solo del cliente autenticado)
     * PUT /api/reservas-web/{reserva}
     */
    public function update(UpdateReservaWebRequest $request, Reserva $reserva)
    {
        $data = $request->validated();

        // Obtener el cliente autenticado
        $cliente = auth('sanctum')->user();

        if (!$cliente || !($cliente instanceof Cliente)) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no autenticado'
            ], 401);
        }

        // SEGURIDAD: Verificar que la reserva pertenece al cliente
        if ($reserva->id_cliente !== $cliente->id_cliente) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para modificar esta reserva'
            ], 403);
        }

        // Verificar que la reserva esté en un estado modificable
        $estadosModificables = [
            EstadoReserva::ESTADO_PENDIENTE,
            EstadoReserva::ESTADO_CONFIRMADA
        ];

        if (!in_array($reserva->id_estado_res, $estadosModificables)) {
            return response()->json([
                'success' => false,
                'message' => 'La reserva no puede ser modificada en su estado actual'
            ], 400);
        }

        DB::transaction(function () use ($reserva, $data) {
            // Actualizar datos básicos de la reserva
            $camposActualizables = ['notas', 'numero_adultos', 'numero_ninos'];
            $datosReserva = array_intersect_key($data, array_flip($camposActualizables));

            if (!empty($datosReserva)) {
                $reserva->update($datosReserva);
            }

            // Si se modifican habitaciones, recalcular total
            if (isset($data['habitaciones'])) {
                // Eliminar habitaciones existentes
                $reserva->habitaciones()->delete();

                $totalReserva = 0;
                foreach ($data['habitaciones'] as $hab) {
                    // Verificar disponibilidad
                    $choqueReserva = ReservaHabitacion::where('id_habitacion', $hab['id_habitacion'])
                        ->where('id_reserva', '!=', $reserva->id_reserva)
                        ->where('fecha_llegada', '<', $hab['fecha_salida'])
                        ->where('fecha_salida', '>', $hab['fecha_llegada'])
                        ->exists();

                    if ($choqueReserva) {
                        throw new \Exception("La habitación {$hab['id_habitacion']} no está disponible.");
                    }

                    $reservaHab = $reserva->habitaciones()->create([
                        'id_habitacion' => $hab['id_habitacion'],
                        'fecha_llegada' => $hab['fecha_llegada'],
                        'fecha_salida'  => $hab['fecha_salida'],
                        'adultos'       => $hab['adultos'],
                        'ninos'         => $hab['ninos'],
                        'bebes'         => $hab['bebes'],
                        'subtotal'      => 0,
                    ]);

                    $reservaHab->load('habitacion');
                    $subtotal = $reservaHab->calcularSubtotal();
                    $reservaHab->update(['subtotal' => $subtotal]);
                    $totalReserva += $subtotal;
                }

                $reserva->update(['total_monto_reserva' => $totalReserva]);
            }

            // Notificar después del commit
            DB::afterCommit(function () use ($reserva) {
                $fresh = $reserva->fresh()->load([
                    'cliente',
                    'estado',
                    'habitaciones.habitacion'
                ]);

                if ($fresh->cliente?->email) {
                    $fresh->cliente->notify(new ReservaActualizada($fresh));
                }
            });
        });

        return response()->json([
            'success' => true,
            'message' => 'Reserva actualizada exitosamente',
            'data' => $reserva->fresh()->load([
                'cliente',
                'estado',
                'habitaciones.habitacion.tipoHabitacion'
            ])
        ]);
    }

    /**
     * Cancelar una reserva (solo del cliente autenticado)
     * POST /api/reservas-web/{reserva}/cancelar
     */
    public function cancelar(CancelReservaWebRequest $request, Reserva $reserva)
    {
        $data = $request->validated();

        // Obtener el cliente autenticado
        $cliente = auth('sanctum')->user();

        if (!$cliente || !($cliente instanceof Cliente)) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no autenticado'
            ], 401);
        }

        // SEGURIDAD: Verificar que la reserva pertenece al cliente
        if ($reserva->id_cliente !== $cliente->id_cliente) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para cancelar esta reserva'
            ], 403);
        }

        // Verificar que la reserva no esté ya cancelada
        if ($reserva->id_estado_res === EstadoReserva::ESTADO_CANCELADA) {
            return response()->json([
                'success' => false,
                'message' => 'La reserva ya está cancelada'
            ], 400);
        }

        DB::transaction(function () use ($reserva, $data) {
            // Agregar notas de cancelación si existen
            if (!empty($data['notas'])) {
                $notasActuales = $reserva->notas ?? '';
                $notaCancelacion = "\n[" . now()->format('Y-m-d H:i:s') . "] Cancelación: " . $data['notas'];
                $reserva->notas = $notasActuales . $notaCancelacion;
            }

            // Cambiar estado a cancelada
            $reserva->update([
                'id_estado_res' => EstadoReserva::ESTADO_CANCELADA,
                'notas' => $reserva->notas
            ]);

            Log::info('Reserva cancelada por cliente web', [
                'id_reserva' => $reserva->id_reserva,
                'id_cliente' => $reserva->id_cliente
            ]);

            // Notificar después del commit
            DB::afterCommit(function () use ($reserva) {
                $fresh = $reserva->fresh()->load([
                    'cliente',
                    'habitaciones.habitacion',
                    'estado'
                ]);

                if ($fresh->cliente?->email) {
                    $fresh->cliente->notify(new ReservaCancelada($fresh));
                }
            });
        });

        return response()->json([
            'success' => true,
            'message' => 'Reserva cancelada exitosamente',
            'data' => $reserva->fresh()->load([
                'cliente',
                'estado',
                'habitaciones.habitacion'
            ])
        ]);
    }
}