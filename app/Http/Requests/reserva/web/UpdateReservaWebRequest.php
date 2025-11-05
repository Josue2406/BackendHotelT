<?php
namespace App\Http\Requests\reserva\web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\habitacion\Habitacione;

class UpdateReservaWebRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Verificar que el usuario esté autenticado como cliente
        return auth('sanctum')->check() && auth('sanctum')->user() instanceof \App\Models\cliente\Cliente;
    }

    public function rules(): array
    {
        return [
            'notas'            => 'nullable|string|max:500',
            'numero_adultos'   => 'nullable|integer|min:1',
            'numero_ninos'     => 'nullable|integer|min:0',

            // Habitaciones son opcionales en update (solo si se quieren cambiar)
            'habitaciones' => 'nullable|array|min:1',
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
            // Validar capacidad máxima por habitación si se están actualizando
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
            'habitaciones.min' => 'Debe incluir al menos una habitación si desea modificarlas.',
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
            'numero_adultos.min' => 'Debe haber al menos 1 adulto.',
            'numero_ninos.min' => 'El número de niños no puede ser negativo.',
        ];
    }
}