<?php // app/Http/Requests/UpdateTipoHabitacionRequest.php
namespace App\Http\Requests\catalogo;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTipoHabitacionRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'nombre'      => 'sometimes|string|max:60',
            'descripcion' => 'nullable|string|max:255',
        ];
    }
}
