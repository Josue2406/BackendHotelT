<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRolRequest extends FormRequest
{
    public function authorize(): bool { return true; } // rol solo informativo
    public function rules(): array {
        return [
            'nombre'      => 'required|string|max:50|unique:rols,nombre',
            'descripcion' => 'required|string|max:250',
        ];
    }
}
