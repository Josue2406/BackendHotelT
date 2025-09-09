<?php // app/Http/Requests/StoreHabitacionAmenidadRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHabitacionAmenidadRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'id_habitacion' => [
                'required','integer','exists:habitaciones,id_habitacion'
            ],
            'id_amenidad'   => [
                'required','integer','exists:amenidads,id_amenidad',
                // Validación de unicidad compuesta (id_habitacion,id_amenidad)
                Rule::unique('habitacion_amenidads', 'id_amenidad')->where(function($q){
                    return $q->where('id_habitacion', $this->input('id_habitacion'));
                }),
            ],
        ];
    }
}
