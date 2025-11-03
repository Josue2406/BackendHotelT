<?php
namespace App\Http\Requests\reserva\web;

use Illuminate\Foundation\Http\FormRequest;

class CancelReservaWebRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Verificar que el usuario esté autenticado como cliente
        return auth('sanctum')->check() && auth('sanctum')->user() instanceof \App\Models\cliente\Cliente;
    }

    public function rules(): array
    {
        return [
            'notas' => 'nullable|string|max:500',
        ];
    }

    /**
     * Mensajes de error personalizados
     */
    public function messages(): array
    {
        return [
            'notas.max' => 'Las notas de cancelación no pueden exceder 500 caracteres.',
        ];
    }
}