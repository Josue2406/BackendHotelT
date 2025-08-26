<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHabitacionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array {
        return [
            'tipo_habitacion_id' => 'required|exists:tipos_habitacion,id',
            'numero'    => 'required|string|max:20|unique:habitaciones,numero',
            'piso'      => 'required|integer|min:0|max:200',
            'estado'    => 'nullable|in:disponible,ocupada,sucia,mantenimiento,bloqueada',
            'tarifa_noche' => 'nullable|numeric|min:0',
            'habilitada'   => 'boolean',
        ];
    }
}
