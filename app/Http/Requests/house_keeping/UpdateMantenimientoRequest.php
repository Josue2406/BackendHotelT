<?php

namespace App\Http\Requests\house_keeping;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMantenimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ajusta si usas políticas/roles
    }

    public function rules(): array
    {
        return [
            'notas'               => ['sometimes', 'nullable', 'string', 'max:500'],
            'prioridad'           => ['sometimes', 'nullable', Rule::in(['baja','media','alta','urgente'])],

            'fecha_inicio'        => ['sometimes', 'date'],
            'fecha_final'         => ['sometimes', 'nullable', 'date', 'after_or_equal:fecha_inicio'],
            //'fecha_reporte'       => ['sometimes', 'date'],

            // FKs con validación de existencia
            'id_habitacion'       => ['sometimes', 'nullable', 'integer', 'exists:habitaciones,id_habitacion'],
            'id_usuario_asigna'   => ['sometimes', 'nullable', 'integer', 'exists:users,id_usuario'],
            //'id_usuario_reporta'  => ['sometimes', 'nullable', 'integer', 'exists:users,id_usuario'],

            // 👉 Estado de mantenimiento debe existir en tabla estados
            'id_estado_hab'       => ['sometimes', 'nullable', 'integer', 'exists:estado_habitacions,id_estado_hab'],
        ];
    }
}
