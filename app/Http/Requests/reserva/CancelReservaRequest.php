<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelReservaRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'motivo' => 'nullable|string|max:200',
            // si quieres forzar una fecha/llegada específica para evaluar ventana:
            'fecha_checkin_programada' => 'nullable|date',
        ];
    }
}
