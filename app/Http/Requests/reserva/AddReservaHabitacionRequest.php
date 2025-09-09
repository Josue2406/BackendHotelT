<?php
namespace App\Http\Requests\reserva;

use Illuminate\Foundation\Http\FormRequest;

class AddReservaHabitacionRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'id_habitacion'  => 'required|integer|exists:habitaciones,id_habitacion',
            'fecha_llegada'  => 'required|date',
            'fecha_salida'   => 'required|date|after:fecha_llegada',
            'pax_total'      => 'required|integer|min:1',
        ];
    }
}
