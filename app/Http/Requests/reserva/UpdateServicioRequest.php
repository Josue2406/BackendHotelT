<?php
namespace App\Http\Requests\reserva;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServicioRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'nombre'      => 'sometimes|required|string|max:120',
            'precio'      => 'sometimes|required|numeric|min:0',
            'descripcion' => 'nullable|string|max:500',
        ];
    }
}
