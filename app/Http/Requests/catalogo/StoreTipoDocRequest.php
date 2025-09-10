<?php // app/Http/Requests/StoreTipoDocRequest.php
namespace App\Http\Requests\catalogo;

use Illuminate\Foundation\Http\FormRequest;

class StoreTipoDocRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'nombre' => 'required|string|max:50',
        ];
    }
}
