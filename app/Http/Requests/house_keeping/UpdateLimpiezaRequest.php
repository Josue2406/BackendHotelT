<?php

namespace App\Http\Requests\house_keeping;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLimpiezaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ajusta según políticas/roles si aplica
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'              => ['sometimes', 'string', 'max:100'],

            'descripcion'         => ['sometimes', 'nullable', 'string', 'max:500'],
            'notas'               => ['sometimes', 'nullable', 'string', 'max:500'],
            'prioridad'           => ['sometimes', 'nullable', Rule::in(['baja','media','alta','urgente'])],

            'fecha_inicio'        => ['sometimes', 'date'],
            'fecha_final'         => ['sometimes', 'nullable', 'date', 'after_or_equal:fecha_inicio'],
            //'fecha_reporte'       => ['sometimes', 'date'],

            // Claves foráneas
            'id_habitacion'       => ['sometimes', 'nullable', 'integer', 'exists:habitaciones,id_habitacion'],
            'id_usuario_asigna'   => ['sometimes', 'nullable', 'integer', 'exists:users,id_usuario'],
            //'id_usuario_reporta'  => ['sometimes', 'nullable', 'integer', 'exists:users,id_usuario'],

            // Estado proveniente de la tabla estado_habitacions
            'id_estado_hab'       => ['sometimes', 'nullable', 'integer', 'exists:estado_habitacions,id_estado_hab'],
        ];
    }
}
