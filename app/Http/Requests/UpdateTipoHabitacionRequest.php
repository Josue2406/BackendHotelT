<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTipoHabitacionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array {
        $id = $this->route('tipo_habitacion');
        return [
            'nombre'      => "sometimes|required|string|max:80|unique:tipos_habitacion,nombre,{$id}",
            'codigo'      => "sometimes|required|string|max:20|alpha_dash|unique:tipos_habitacion,codigo,{$id}",
            'capacidad'   => 'sometimes|required|integer|min:1|max:10',
            'tarifa_base' => 'sometimes|required|numeric|min:0',
            'amenidades'  => 'nullable|array',
            'amenidades.*'=> 'string|max:40',
            'descripcion' => 'nullable|string',
        ];
    }
}

