<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHabitacionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array {
        $id = $this->route('habitacion');
        return [
            'tipo_habitacion_id' => 'sometimes|required|exists:tipos_habitacion,id',
            'numero'    => "sometimes|required|string|max:20|unique:habitaciones,numero,{$id}",
            'piso'      => 'sometimes|required|integer|min:0|max:200',
            'estado'    => 'nullable|in:disponible,ocupada,sucia,mantenimiento,bloqueada',
            'tarifa_noche' => 'nullable|numeric|min:0',
            'habilitada'   => 'boolean',
        ];
    }
}
