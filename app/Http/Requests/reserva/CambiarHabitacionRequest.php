<?php

namespace App\Http\Requests\reserva;

use Illuminate\Foundation\Http\FormRequest;

class CambiarHabitacionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Autorización manejada por middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id_reserva_habitacion' => 'required|exists:reserva_habitacions,id_reserva_hab',
            'id_habitacion_nueva' => 'required|exists:habitaciones,id_habitacion',
            'motivo' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'id_reserva_habitacion.required' => 'Debe especificar qué habitación desea cambiar',
            'id_reserva_habitacion.exists' => 'La habitación de la reserva no existe',
            'id_habitacion_nueva.required' => 'Debe seleccionar la nueva habitación',
            'id_habitacion_nueva.exists' => 'La habitación seleccionada no existe',
            'motivo.max' => 'El motivo no puede exceder 500 caracteres',
        ];
    }
}