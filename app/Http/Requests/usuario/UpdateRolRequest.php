<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRolRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        $id = $this->route('role') ?? $this->route('rol') ?? $this->route('id'); // por si cambia el nombre
        return [
            'nombre'      => ['sometimes','string','max:50', Rule::unique('rols','nombre')->ignore($id, 'id_rol')],
            'descripcion' => 'sometimes|string|max:250',
        ];
    }
}
