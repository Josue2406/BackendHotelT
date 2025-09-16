<?php // app/Http/Requests/StoreFuenteRequest.php
namespace App\Http\Requests\catalogo;

use Illuminate\Foundation\Http\FormRequest;

class StoreFuenteRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'nombre' => 'required|string|max:100|unique:fuentes,nombre',
            'codigo' => 'required|string|max:5|unique:fuentes,codigo',
        ];
    }
}
