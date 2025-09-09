<?php

namespace App\Http\Requests\house_keeping;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMantenimientoRequest extends FormRequest
{
    // public function authorize(): bool
    // {
    //     return true; 
    // }

    public function rules(): array
    {
        return [
            'nombre'              => ['required','string','max:100'],
            'descripcion'         => ['nullable','string','max:500'],
            'notas'               => ['nullable','string','max:500'],
            'prioridad'           => ['nullable', Rule::in(['baja','media','alta','urgente'])],

            'fecha_inicio'        => ['nullable','date'], 
            //'fecha_reporte'       => ['required','date'],
            'fecha_final'         => ['nullable','date'],  // coherencia estricta se valida en "finalizar"

            // FKs laxas (sin exists:* por ahora)
            'id_habitacion'       => ['nullable','integer'],
            'id_usuario_asigna'   => ['nullable','integer'],
            //'id_usuario_reporta'  => ['nullable','integer'],
        ];
    }
}
