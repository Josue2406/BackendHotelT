<?php
namespace App\Http\Requests\frontdesk;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'fecha_hora' => 'nullable|date',
            'resultado'  => 'nullable|string|max:30',
            // 'id_estado_estadia' => 'nullable|integer|exists:estado_estadia,id_estado_estadia', // si decides actualizarlo
        ];
    }
}
