<?php
namespace App\Http\Requests\reserva;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\reserva\EstadoReserva;

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

    /**
     * Configurar validador para validaciones personalizadas
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Validar transición de estado si se está actualizando
            if ($this->has('id_estado_res')) {
                $reserva = $this->route('reserva');

                if ($reserva) {
                    $estadoActual = $reserva->id_estado_res;
                    $estadoNuevo = $this->input('id_estado_res');

                    // Si el estado no cambió, no validar
                    if ($estadoActual != $estadoNuevo) {
                        if (!EstadoReserva::puedeCambiarEstado($estadoActual, $estadoNuevo)) {
                            $nombreActual = EstadoReserva::getNombreEstado($estadoActual);
                            $nombreNuevo = EstadoReserva::getNombreEstado($estadoNuevo);

                            $validator->errors()->add(
                                'id_estado_res',
                                "No se puede cambiar el estado de '{$nombreActual}' a '{$nombreNuevo}'."
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
            'id_estado_res.exists' => 'El estado de reserva seleccionado no es válido.',
            'fecha_salida.after' => 'La fecha de salida debe ser posterior a la fecha de llegada.',
            'adultos.min' => 'Debe haber al menos 1 adulto.',
            'ninos.min' => 'El número de niños no puede ser negativo.',
            'bebes.min' => 'El número de bebés no puede ser negativo.',
            'total_monto_reserva.min' => 'El monto total no puede ser negativo.',
        ];
    }
}
