<?php

namespace App\Http\Requests\usuario;

use Illuminate\Foundation\Http\FormRequest;

class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool { return true; } // sin gates/policies
    public function rules(): array {
        return [
           'id_rol'    => 'nullable|exists:rols,id_rol',
            'nombre'    => 'required|string|max:60',
            'apellido1' => 'required|string|max:60',
            'apellido2' => 'nullable|string|max:60',
            'email'     => 'required|email|max:120|unique:users,email',
            'password'  => 'required|string|min:8',
            'telefono'  => 'nullable|string|max:60',
        ];
    }
}
