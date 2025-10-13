<?php
namespace App\Http\Requests\reserva;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array {
        return [
            //'id_cliente'   => 'required|integer|exists:clientes,id_cliente',
            'id_estado_res'=> 'required|integer|exists:estado_reserva,id_estado_res',
            'id_fuente'    => 'nullable|integer|exists:fuentes,id_fuente',
            'notas'        => 'nullable|string|max:300',

            // Array de habitaciones (REQUERIDO - al menos 1 habitación)
            'habitaciones' => 'required|array|min:1',
            'habitaciones.*.id_habitacion' => 'required|integer|exists:habitaciones,id_habitacion',
            'habitaciones.*.fecha_llegada' => 'required|date',
            'habitaciones.*.fecha_salida'  => 'required|date|after:habitaciones.*.fecha_llegada',
            'habitaciones.*.adultos'       => 'required|integer|min:1',
            'habitaciones.*.ninos'         => 'required|integer|min:0',
            'habitaciones.*.bebes'         => 'required|integer|min:0',
        ];
    }
}
