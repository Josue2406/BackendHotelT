<?php

namespace App\Http\Requests\reserva;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ExtenderEstadiaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id_reserva_hab' => 'required|integer|exists:reserva_habitacions,id_reserva_hab',
            'nueva_fecha_salida' => 'required|date|after:fecha_salida_actual',
            'id_habitacion_alternativa' => 'nullable|integer|exists:habitaciones,id_habitacion',
            'motivo' => 'nullable|string|max:300',
        ];
    }

    /**
     * Configurar validador para validaciones personalizadas
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $nuevaFechaSalida = $this->input('nueva_fecha_salida');
            $reservaHab = \App\Models\reserva\ReservaHabitacion::find($this->input('id_reserva_hab'));

            if ($reservaHab) {
                // Validar que la nueva fecha sea posterior a la actual
                if ($nuevaFechaSalida <= $reservaHab->fecha_salida) {
                    $validator->errors()->add(
                        'nueva_fecha_salida',
                        'La nueva fecha de salida debe ser posterior a la fecha de salida actual (' . $reservaHab->fecha_salida->format('Y-m-d') . ').'
                    );
                }

                // Validar que la extensión no sea excesivamente larga (máx 30 días adicionales)
                $diasExtension = $reservaHab->fecha_salida->diffInDays($nuevaFechaSalida);
                if ($diasExtension > 30) {
                    $validator->errors()->add(
                        'nueva_fecha_salida',
                        "La extensión de {$diasExtension} días excede el máximo permitido (30 días). Por favor, cree una nueva reserva."
                    );
                }
            }
        });
    }

    /**
     * Mensajes de error personalizados
     */
    public function messages(): array
    {
        return [
            'id_reserva_hab.required' => 'La reserva de habitación es obligatoria.',
            'id_reserva_hab.exists' => 'La reserva de habitación no existe.',
            'nueva_fecha_salida.required' => 'La nueva fecha de salida es obligatoria.',
            'nueva_fecha_salida.date' => 'La nueva fecha de salida debe ser una fecha válida.',
            'nueva_fecha_salida.after' => 'La nueva fecha de salida debe ser posterior a la fecha actual.',
            'id_habitacion_alternativa.exists' => 'La habitación alternativa seleccionada no existe.',
            'motivo.max' => 'El motivo no puede exceder 300 caracteres.',
        ];
    }
}