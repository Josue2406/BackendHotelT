<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTipoHabitacionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array {
        return [
            'nombre'      => 'required|string|max:80|unique:tipos_habitacion,nombre',
            'codigo'      => 'required|string|max:20|alpha_dash|unique:tipos_habitacion,codigo',
            'capacidad'   => 'required|integer|min:1|max:10',
            'tarifa_base' => 'required|numeric|min:0',
            'amenidades'  => 'nullable|array',
            'amenidades.*'=> 'string|max:40',
            'descripcion' => 'nullable|string',
        ];
    }
}
