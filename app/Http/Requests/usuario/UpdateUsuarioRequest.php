<?php

namespace App\Http\Requests\usuario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        $id = $this->route('usuario')?->id_usuario ?? $this->route('usuario') ?? $this->route('id');
        return [
            'id_rol'    => 'sometimes|exists:rols,id_rol',
            'nombre'    => 'sometimes|string|max:60',
            'apellido1' => 'sometimes|string|max:60',
            'apellido2' => 'nullable|string|max:60',
            'email'     => ['sometimes','email','max:120', Rule::unique('users','email')->ignore($id, 'id_usuario')],
            'password'  => 'sometimes|nullable|string|min:8',
            'telefono'  => 'nullable|string|max:60',
        ];
    }
}
