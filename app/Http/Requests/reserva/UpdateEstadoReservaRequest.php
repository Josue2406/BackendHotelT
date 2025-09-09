<?php

namespace App\Http\Requests\reserva;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEstadoReservaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array {
        $id = $this->route('estado_reserva')->id_estado_res ?? null;

        return [
            'nombre' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('estado_reserva','nombre')->ignore($id,'id_estado_res'),
            ],
        ];
    }
}
