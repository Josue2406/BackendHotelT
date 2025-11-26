<?php
namespace App\Http\Controllers\Api\reserva;

use App\Http\Controllers\Controller;
use App\Http\Requests\reserva\AddReservaServicioRequest;
use App\Models\reserva\Reserva;
use App\Models\reserva\ReservaServicio;

class ReservaServicioController extends Controller
{
    /**
     * List all services for a reservation
     * GET /api/reservas/{reserva}/servicios
     */
    public function index(Reserva $reserva) {
        return $reserva->servicios;
    }

    /**
     * Add a service to a reservation
     * POST /api/reservas/{reserva}/servicios
     */
    public function store(AddReservaServicioRequest $r, Reserva $reserva) {
        $data = $r->validated();

        // Obtener el servicio para obtener su precio
        $servicio = \App\Models\reserva\Servicio::findOrFail($data['id_servicio']);

        // Si no se proporciona precio_unitario, usar el precio del servicio
        $precioUnitario = $data['precio_unitario'] ?? $servicio->precio;

        // Calcular subtotal automáticamente
        $subtotal = $data['cantidad'] * $precioUnitario;

        // Check if this service is already on the reservation
        $pivot = $reserva->servicios()->where('servicio.id_servicio', $data['id_servicio'])->first();

        if ($pivot) {
            // Update existing: add quantity
            $newCantidad = $pivot->pivot->cantidad + $data['cantidad'];
            $newSubtotal = $newCantidad * $precioUnitario;

            $reserva->servicios()->updateExistingPivot($data['id_servicio'], [
                'cantidad' => $newCantidad,
                'precio_unitario' => $precioUnitario,
                'subtotal' => $newSubtotal,
                'fecha_servicio' => $data['fecha_servicio'] ?? $pivot->pivot->fecha_servicio,
                'descripcion' => $data['descripcion'] ?? $pivot->pivot->descripcion,
            ]);
            return response()->json($reserva->servicios()->where('servicio.id_servicio', $data['id_servicio'])->first());
        }

        // Attach new service
        $reserva->servicios()->attach($data['id_servicio'], [
            'cantidad' => $data['cantidad'],
            'precio_unitario' => $precioUnitario,
            'subtotal' => $subtotal,
            'fecha_servicio' => $data['fecha_servicio'] ?? now()->toDateString(),
            'descripcion' => $data['descripcion'] ?? null,
        ]);

        return response()->json(
            $reserva->servicios()->where('servicio.id_servicio', $data['id_servicio'])->first(),
            201
        );
    }

    /**
     * Update a service in a reservation
     * PUT/PATCH /api/reservas/{reserva}/servicios/{id}
     */
    public function update(AddReservaServicioRequest $r, Reserva $reserva, $id) {
        // Find by pivot table id
        $pivot = \DB::table('reserva_servicio')
            ->where('id_reserva_serv', $id)
            ->where('id_reserva', $reserva->id_reserva)
            ->first();

        if (!$pivot) {
            return response()->json(['message' => 'Servicio no encontrado en esta reserva'], 404);
        }

        $data = $r->validated();

        // Obtener el servicio para su precio si no se proporciona
        $servicio = \App\Models\reserva\Servicio::findOrFail($pivot->id_servicio);
        $precioUnitario = $data['precio_unitario'] ?? $servicio->precio;

        // Calcular subtotal
        $subtotal = $data['cantidad'] * $precioUnitario;

        $reserva->servicios()->updateExistingPivot($pivot->id_servicio, [
            'cantidad' => $data['cantidad'],
            'precio_unitario' => $precioUnitario,
            'subtotal' => $subtotal,
            'fecha_servicio' => $data['fecha_servicio'] ?? $pivot->fecha_servicio,
            'descripcion' => $data['descripcion'] ?? $pivot->descripcion,
        ]);

        return response()->json(
            $reserva->servicios()->where('servicio.id_servicio', $pivot->id_servicio)->first()
        );
    }

    /**
     * Remove a service from a reservation
     * DELETE /api/reservas/{reserva}/servicios/{id}
     */
    public function destroy(Reserva $reserva, $id) {
        // Find by pivot table id
        $pivot = \DB::table('reserva_servicio')
            ->where('id_reserva_serv', $id)
            ->where('id_reserva', $reserva->id_reserva)
            ->first();

        if (!$pivot) {
            return response()->json(['message' => 'Servicio no encontrado en esta reserva'], 404);
        }

        // Detach using the service id
        $reserva->servicios()->detach($pivot->id_servicio);
        return response()->noContent();
    }
}
