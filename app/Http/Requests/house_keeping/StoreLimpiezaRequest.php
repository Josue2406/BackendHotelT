<?php

namespace App\Http\Requests\house_keeping;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLimpiezaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ajusta según políticas o roles
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'            => ['required', 'string', 'max:100'],
            'descripcion'       => ['nullable', 'string', 'max:500'],
            'notas'             => ['nullable', 'string', 'max:500'],
            'prioridad'         => ['nullable', Rule::in(['baja', 'media', 'alta', 'urgente'])],

            'fecha_inicio'      => ['required', 'date'],
            'fecha_final'       => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            //'fecha_reporte'     => ['required', 'date'],

            // Claves foráneas con validación de existencia
            'id_habitacion'     => ['nullable', 'integer', 'exists:habitaciones,id_habitacion'],
            'id_usuario_asigna' => ['nullable', 'integer', 'exists:users,id_usuario'],
            'id_usuario_reporta'=> ['nullable', 'integer', 'exists:users,id_usuario'],

            // 👉 Estado debe existir en la tabla de estados
            'id_estado_hab'     => ['nullable', 'integer', 'exists:estado_habitacions,id_estado_hab'],
        ];
    }
}
