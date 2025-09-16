<?php
namespace App\Http\Requests\habitaciones;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHabitacionRequest extends FormRequest {
  public function authorize(): bool { return true; }
  public function rules(): array {
    $id = $this->route('habitacione')?->id_habitacion ?? $this->route('habitacione') ?? $this->route('id');
    return [
      'id_estado_hab'      => 'sometimes|exists:estado_habitacions,id_estado_hab',
      'tipo_habitacion_id' => 'sometimes|exists:tipos_habitacion,id_tipo_hab',
      'nombre'             => 'sometimes|string|max:50|unique:habitaciones,nombre,'.$id.',id_habitacion',
      'numero'             => 'sometimes|string|max:20|unique:habitaciones,numero,'.$id.',id_habitacion',
      'piso'               => 'sometimes|integer',
      'capacidad'          => 'sometimes|integer|min:1',
      'medida'             => 'sometimes|string|max:255',
      'descripcion'        => 'sometimes|string|max:255',
    ];
  }
}
