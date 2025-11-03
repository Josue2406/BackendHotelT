<?php

namespace App\Http\Requests\reserva;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CambiarEstadoReservaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_estado_res' => [
                'required',
                'integer',
                Rule::exists('catalogo_estado_reservas', 'id_estado_res')
            ],
            'notas' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'id_estado_res.required' => 'El estado de la reserva es obligatorio',
            'id_estado_res.exists' => 'El estado de la reserva no es válido',
            'notas.max' => 'Las notas no pueden exceder los 500 caracteres',
        ];
    }
}