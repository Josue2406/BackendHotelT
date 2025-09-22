<?php
namespace App\Http\Requests\reserva;

use Illuminate\Foundation\Http\FormRequest;

class StoreServicioRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'nombre'      => 'required|string|max:120',
            'precio'      => 'required|numeric|min:0',
            'descripcion' => 'nullable|string|max:500',
        ];
    }
}
