<?php // app/Http/Requests/UpdateAmenidadRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAmenidadRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        $id = $this->route('amenidade')->id_amenidad ?? null;
        return [
            'nombre'      => ['sometimes','string','max:60', Rule::unique('amenidads','nombre')->ignore($id,'id_amenidad')],
            'descripcion' => 'nullable|string|max:60',
        ];
    }
}
