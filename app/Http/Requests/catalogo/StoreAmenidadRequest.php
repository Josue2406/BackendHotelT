<?php // app/Http/Requests/StoreAmenidadRequest.php
namespace App\Http\Requests\catalogo;

use Illuminate\Foundation\Http\FormRequest;

class StoreAmenidadRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'nombre'      => 'required|string|max:60|unique:amenidads,nombre',
            'descripcion' => 'nullable|string|max:60',
        ];
    }
}
