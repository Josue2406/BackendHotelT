<?php
namespace App\Http\Requests\reserva;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array {
        return [
            'id_estado_res'=> 'sometimes|integer|exists:estado_reserva,id_estado_res',
            'id_fuente'    => 'sometimes|nullable|integer|exists:fuentes,id_fuente',
            'id_habitacion'=> 'sometimes|nullable|integer|exists:habitaciones,id_habitacion',
            'fecha_llegada'=> 'sometimes|date',
            'fecha_salida' => 'sometimes|date|after:fecha_llegada',
            'adultos'      => 'sometimes|integer|min:1',
            'ninos'        => 'sometimes|integer|min:0',
            'bebes'        => 'sometimes|integer|min:0',
            'total_monto_reserva' => 'sometimes|numeric|min:0',
            'notas'        => 'nullable|string|max:300',
        ];
    }
}
