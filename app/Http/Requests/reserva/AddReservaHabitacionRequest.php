<?php
namespace App\Http\Requests\reserva;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\habitacion\Habitacione;

class AddReservaHabitacionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array {
        return [
            'id_habitacion'  => 'required|integer|exists:habitaciones,id_habitacion',
            'fecha_llegada'  => 'required|date|after_or_equal:today',
            'fecha_salida'   => 'required|date|after:fecha_llegada',
            'adultos'        => 'required|integer|min:1',
            'ninos'          => 'required|integer|min:0',
            'bebes'          => 'required|integer|min:0',
        ];
    }

    /**
     * Configurar validador para validaciones personalizadas
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Validar capacidad máxima de la habitación
            if ($this->has('id_habitacion')) {
                $habitacion = Habitacione::find($this->input('id_habitacion'));

                if ($habitacion) {
                    $totalOcupantes = ($this->input('adultos') ?? 0) +
                                      ($this->input('ninos') ?? 0) +
                                      ($this->input('bebes') ?? 0);

                    if ($totalOcupantes > $habitacion->capacidad) {
                        $validator->errors()->add(
                            'capacidad',
                            "La habitación '{$habitacion->nombre}' tiene capacidad máxima de {$habitacion->capacidad} personas. Total solicitado: {$totalOcupantes}."
                        );
                    }

                    // Validar que haya al menos 1 ocupante
                    if ($totalOcupantes < 1) {
                        $validator->errors()->add(
                            'ocupantes',
                            'Debe haber al menos 1 ocupante en la habitación.'
                        );
                    }
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
            'id_habitacion.required' => 'La habitación es obligatoria.',
            'id_habitacion.exists' => 'La habitación seleccionada no existe.',
            'fecha_llegada.required' => 'La fecha de llegada es obligatoria.',
            'fecha_llegada.date' => 'La fecha de llegada debe ser una fecha válida.',
            'fecha_llegada.after_or_equal' => 'La fecha de llegada debe ser hoy o posterior.',
            'fecha_salida.required' => 'La fecha de salida es obligatoria.',
            'fecha_salida.date' => 'La fecha de salida debe ser una fecha válida.',
            'fecha_salida.after' => 'La fecha de salida debe ser posterior a la fecha de llegada.',
            'adultos.required' => 'El número de adultos es obligatorio.',
            'adultos.min' => 'Debe haber al menos 1 adulto.',
            'ninos.min' => 'El número de niños no puede ser negativo.',
            'bebes.min' => 'El número de bebés no puede ser negativo.',
        ];
    }
}
