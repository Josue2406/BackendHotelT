<?php

namespace App\Http\Requests\house_keeping;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMantenimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ajusta según tu sistema de roles/policies
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'            => ['required', 'string', 'max:100'],
            'descripcion'       => ['nullable', 'string', 'max:500'],
            'notas'             => ['nullable', 'string', 'max:500'],
            'prioridad'         => ['nullable', Rule::in(['baja', 'media', 'alta', 'urgente'])],

            'fecha_inicio'      => ['nullable', 'date'],
            'fecha_final'       => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            //'fecha_reporte'     => ['required', 'date'],

            // Relaciones
            'id_habitacion'     => ['nullable', 'integer', 'exists:habitaciones,id_habitacion'],
            'id_usuario_asigna' => ['nullable', 'integer', 'exists:users,id_usuario'],
            'id_usuario_reporta'=> ['nullable', 'integer', 'exists:users,id_usuario'],

            // 👉 Estado obligatorio si se asigna, debe existir
            'id_estado_hab'     => ['nullable', 'integer', 'exists:estado_habitacions,id_estado_hab'],
        ];
    }
}
