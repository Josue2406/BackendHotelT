<?php

namespace App\Http\Requests\reserva;

use Illuminate\Foundation\Http\FormRequest;

class StoreEstadoReservaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array {
        return [
            'nombre' => 'required|string|max:20|unique:estado_reserva,nombre',
        ];
    }
}
