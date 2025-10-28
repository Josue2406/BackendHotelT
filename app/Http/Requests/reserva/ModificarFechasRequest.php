<?php

namespace App\Http\Requests\reserva;

use Illuminate\Foundation\Http\FormRequest;

class ModificarFechasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_reserva_habitacion' => 'required|exists:reserva_habitacions,id_reserva_hab',
            'nueva_fecha_llegada' => 'nullable|date|after_or_equal:today',
            'nueva_fecha_salida' => 'nullable|date|after:nueva_fecha_llegada',
            'aplicar_politica' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'id_reserva_habitacion.required' => 'Debe especificar qué habitación desea modificar',
            'nueva_fecha_llegada.date' => 'La fecha de llegada no es válida',
            'nueva_fecha_llegada.after_or_equal' => 'La fecha de llegada no puede ser en el pasado',
            'nueva_fecha_salida.date' => 'La fecha de salida no es válida',
            'nueva_fecha_salida.after' => 'La fecha de salida debe ser posterior a la fecha de llegada',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('aplicar_politica') && is_null($this->aplicar_politica)) {
            $this->merge(['aplicar_politica' => true]);
        }
    }
}
