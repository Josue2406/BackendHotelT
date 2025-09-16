<?php
namespace App\Http\Requests\frontdesk;

use Illuminate\Foundation\Http\FormRequest;

class RoomMoveRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'id_hab_nueva' => 'required|integer|exists:habitaciones,id_habitacion',
            'desde'        => 'nullable|date',  // por defecto now()
            'adultos'      => 'nullable|integer|min:1',
            'ninos'        => 'nullable|integer|min:0',
            'bebes'        => 'nullable|integer|min:0',
        ];
    }
}
