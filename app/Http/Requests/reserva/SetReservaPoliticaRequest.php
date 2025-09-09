<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetReservaPoliticaRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'id_politica' => 'required|integer|exists:politica_cancelacion,id_politica',
            'motivo'      => 'nullable|string|max:200',
        ];
    }
}
