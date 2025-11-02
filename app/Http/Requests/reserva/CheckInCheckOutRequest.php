<?php

namespace App\Http\Requests\reserva;

use Illuminate\Foundation\Http\FormRequest;

class CheckInCheckOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notas' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'notas.max' => 'Las notas no pueden exceder los 500 caracteres',
        ];
    }
}