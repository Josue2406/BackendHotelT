<?php // app/Http/Requests/StoreEstadoHabitacionRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEstadoHabitacionRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'nombre'      => 'required|string|max:30|unique:estado_habitacions,nombre',
            'descripcion' => 'nullable|string|max:100',
        ];
    }
}
