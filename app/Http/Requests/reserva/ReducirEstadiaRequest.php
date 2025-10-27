<?php

namespace App\Http\Requests\reserva;

use Illuminate\Foundation\Http\FormRequest;

class ReducirEstadiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_reserva_habitacion' => 'required|exists:reserva_habitacions,id_reserva_hab',
            'nueva_fecha_salida' => 'required|date|after:today',
            'aplicar_politica' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'id_reserva_habitacion.required' => 'Debe especificar qué habitación desea modificar',
            'nueva_fecha_salida.required' => 'Debe especificar la nueva fecha de salida',
            'nueva_fecha_salida.date' => 'La fecha de salida no es válida',
            'nueva_fecha_salida.after' => 'La fecha de salida debe ser posterior a hoy',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('aplicar_politica') && is_null($this->aplicar_politica)) {
            $this->merge(['aplicar_politica' => true]);
        }
    }
}
