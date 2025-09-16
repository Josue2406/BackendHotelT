<?php
namespace App\Http\Requests\frontdesk;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFechasEstadiaRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'fecha_llegada' => 'sometimes|date',
            'fecha_salida'  => 'sometimes|date|after:fecha_llegada',
            'adultos'       => 'sometimes|integer|min:1',
            'ninos'         => 'sometimes|integer|min:0',
            'bebes'         => 'sometimes|integer|min:0',
        ];
    }
}
