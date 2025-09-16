<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'     => ['required', 'string', 'max:60'],
            'apellido1'  => ['required', 'string', 'max:60'],
            'apellido2'  => ['nullable', 'string', 'max:60'],
            'email'      => ['required', 'email', 'max:120', 'unique:users,email'],
            'password'   => ['required', 'string', 'min:6', 'confirmed'], // requiere password_confirmation
            'telefono'   => ['nullable', 'string', 'max:60'],
            'id_rol'     => ['required', 'exists:rols,id_rol'], // si lo asignas manual
        ];
    }
}
