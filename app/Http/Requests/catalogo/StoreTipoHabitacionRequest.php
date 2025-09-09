<?php // app/Http/Requests/StoreTipoHabitacionRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTipoHabitacionRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'nombre'      => 'required|string|max:60',
            'descripcion' => 'nullable|string|max:255',
        ];
    }
}
