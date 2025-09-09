<?php // app/Http/Requests/UpdateEstadoHabitacionRequest.php
namespace App\Http\Requests\catalogo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEstadoHabitacionRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        $id = $this->route('estados_habitacion')->id_estado_hab ?? null;
        return [
            'nombre'      => ['sometimes','string','max:30', Rule::unique('estado_habitacions','nombre')->ignore($id,'id_estado_hab')],
            'descripcion' => 'nullable|string|max:100',
        ];
    }
}
