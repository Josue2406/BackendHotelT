<?php

namespace App\Http\Requests\house_keeping;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLimpiezaRequest extends FormRequest
{
    // public function authorize(): bool
    // {
    //     return true;
    // }

    public function rules(): array
    {
        return [
            'nombre'              => ['sometimes','string','max:100'],
            'descripcion'         => ['nullable','string','max:500'],
            'notas'               => ['nullable','string','max:500'],
            'prioridad'           => ['nullable','in:baja,media,alta,urgente'],

            'fecha_inicio'        => ['sometimes','date'],
            'fecha_reporte'       => ['sometimes','date'],
            'fecha_final'         => ['nullable','date','after_or_equal:fecha_inicio'],

            'id_habitacion'       => ['nullable','integer'],
            'id_usuario_asigna'   => ['nullable','integer'],
            'id_usuario_reporta'  => ['nullable','integer'],
        ];
    }
}
