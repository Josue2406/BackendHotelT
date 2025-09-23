<?php

// app/Http/Controllers/Api/AvailabilityController.php
namespace App\Http\Controllers\Api\reserva;

use App\Http\Controllers\Controller;
use App\Models\habitacion\Habitacione;
use App\Services\reserva\PricingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function __construct(private PricingService $pricing) {}

    public function search(Request $r)
    {
        $data = $r->validate([
            'checkin'  => 'required|date|before:checkout',
            'checkout' => 'required|date|after:checkin',
            'tipo_habitacion_id' => 'nullable|integer|exists:tipos_habitacion,id_tipo_hab',
            'qty' => 'nullable|integer|min:1',
        ]);

        $checkin  = Carbon::parse($data['checkin'])->startOfDay();
        $checkout = Carbon::parse($data['checkout'])->startOfDay();
        $qty = $data['qty'] ?? 1;

        // 1) Pool disponible por tipo (sin asignación específica)
        $query = Habitacione::query()
            ->where('id_estado_hab', 1) // Disponible (según tu catálogo). :contentReference[oaicite:2]{index=2}
            ->when(isset($data['tipo_habitacion_id']), fn($q) => $q->where('tipo_habitacion_id', $data['tipo_habitacion_id']))
            // Aquí podrías excluir habitaciones con bloqueos operativos solapados, mantenimientos, etc. (tu BD ya tiene estas tablas). :contentReference[oaicite:3]{index=3}
            ;

        $habitaciones = $query->get();

        // Si necesitas validar reservas solapadas por pool, haz tu conteo y filtra aquí.

        // 2) Elegir “desde”: la más barata por final_total en el rango
        $items = [];
        foreach ($habitaciones as $hab) {
            $calc = $this->pricing->precioRango($hab, $checkin, $checkout);
            $items[] = [
                'habitacion_id' => $hab->id_habitacion,
                'tipo_habitacion_id' => $hab->tipo_habitacion_id,
                'moneda' => $hab->moneda,
                'precio_base_desde' => (float) $hab->precio_base,                 // por noche (base)
                'precio_final_total' => $calc['final_total'],                     // total del rango
                'precio_base_total'  => $calc['base_total'],
                'noches' => $calc['noches'],
                'detalle' => $calc['detalle'], // opcional para UI transparente
            ];
        }

        // Agrupar por tipo y exigir stock >= qty si lo manejas por pool
        $porTipo = collect($items)->groupBy('tipo_habitacion_id')->map(function($grupo) {
            $mejor = $grupo->sortBy('precio_final_total')->first();
            return [
                'tipo_habitacion_id' => $mejor['tipo_habitacion_id'],
                'moneda' => $mejor['moneda'],
                'desde_base_x_noche' => $mejor['precio_base_desde'],
                'desde_final_total'  => $mejor['precio_final_total'],
                'noches' => $mejor['noches'],
                'ejemplo_detalle' => $mejor['detalle'], // opcional
                'disponibles' => $grupo->count(),       // si quieres devolver stock simple
            ];
        })->values();

        return response()->json($porTipo);
    }
}
