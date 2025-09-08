<?php

namespace App\Http\Requests\house_keeping;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLimpiezaRequest extends FormRequest
{
    // public function authorize(): bool
    // {
    //     return true; // ajusta si usas políticas/roles
    // }

    public function rules(): array
    {
        return [
            'nombre'              => ['required','string','max:100'],
            'descripcion'         => ['nullable','string','max:500'],
            'notas'               => ['nullable','string','max:500'],
            'prioridad'           => ['nullable', Rule::in(['baja','media','alta','urgente'])],

            'fecha_inicio'        => ['required','date'],
            'fecha_reporte'       => ['required','date'],
            'fecha_final'         => ['nullable','date','after_or_equal:fecha_inicio'],

            // FKs laxas mientras no tengas tablas relacionadas listas
            'id_habitacion'       => ['nullable','integer'],
            'id_usuario_asigna'   => ['nullable','integer'],
            'id_usuario_reporta'  => ['nullable','integer'],
        ];
    }
}
