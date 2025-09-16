<?php // app/Http/Requests/UpdateTipoDocRequest.php
namespace App\Http\Requests\catalogo;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTipoDocRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'nombre' => 'sometimes|string|max:50',
        ];
    }
}
