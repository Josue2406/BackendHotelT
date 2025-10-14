<?php
namespace App\Http\Requests\reserva;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\habitacion\Habitacione;

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
            'habitaciones.*.fecha_llegada' => 'required|date|after_or_equal:today',
            'habitaciones.*.fecha_salida'  => 'required|date|after:habitaciones.*.fecha_llegada',
            'habitaciones.*.adultos'       => 'required|integer|min:1',
            'habitaciones.*.ninos'         => 'required|integer|min:0',
            'habitaciones.*.bebes'         => 'required|integer|min:0',
        ];
    }

    /**
     * Configurar validador para agregar reglas personalizadas
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Validar capacidad máxima por habitación
            if ($this->has('habitaciones')) {
                foreach ($this->habitaciones as $index => $hab) {
                    $habitacion = Habitacione::find($hab['id_habitacion']);

                    if ($habitacion) {
                        $totalOcupantes = ($hab['adultos'] ?? 0) + ($hab['ninos'] ?? 0) + ($hab['bebes'] ?? 0);

                        if ($totalOcupantes > $habitacion->capacidad) {
                            $validator->errors()->add(
                                "habitaciones.{$index}.capacidad",
                                "La habitación '{$habitacion->nombre}' tiene capacidad máxima de {$habitacion->capacidad} personas. Total solicitado: {$totalOcupantes}."
                            );
                        }

                        // Validar que haya al menos 1 ocupante
                        if ($totalOcupantes < 1) {
                            $validator->errors()->add(
                                "habitaciones.{$index}.ocupantes",
                                "Debe haber al menos 1 ocupante en la habitación."
                            );
                        }
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
            'id_estado_res.required' => 'El estado de la reserva es obligatorio.',
            'id_estado_res.exists' => 'El estado de reserva seleccionado no es válido.',
            'habitaciones.required' => 'Debe incluir al menos una habitación.',
            'habitaciones.min' => 'Debe incluir al menos una habitación.',
            'habitaciones.*.id_habitacion.required' => 'El ID de habitación es obligatorio.',
            'habitaciones.*.id_habitacion.exists' => 'La habitación seleccionada no existe.',
            'habitaciones.*.fecha_llegada.required' => 'La fecha de llegada es obligatoria.',
            'habitaciones.*.fecha_llegada.date' => 'La fecha de llegada debe ser una fecha válida.',
            'habitaciones.*.fecha_llegada.after_or_equal' => 'La fecha de llegada debe ser hoy o posterior.',
            'habitaciones.*.fecha_salida.required' => 'La fecha de salida es obligatoria.',
            'habitaciones.*.fecha_salida.date' => 'La fecha de salida debe ser una fecha válida.',
            'habitaciones.*.fecha_salida.after' => 'La fecha de salida debe ser posterior a la fecha de llegada.',
            'habitaciones.*.adultos.required' => 'El número de adultos es obligatorio.',
            'habitaciones.*.adultos.min' => 'Debe haber al menos 1 adulto.',
            'habitaciones.*.ninos.min' => 'El número de niños no puede ser negativo.',
            'habitaciones.*.bebes.min' => 'El número de bebés no puede ser negativo.',
        ];
    }
}
