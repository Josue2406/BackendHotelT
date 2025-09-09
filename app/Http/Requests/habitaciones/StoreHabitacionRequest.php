<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StoreHabitacionRequest extends FormRequest {
  public function authorize(): bool { return true; }
  public function rules(): array {
    return [
      'id_estado_hab'      => 'required|exists:estado_habitacions,id_estado_hab',
      'tipo_habitacion_id' => 'required|exists:tipos_habitacion,id_tipo_hab',
      'nombre'             => 'required|string|max:50|unique:habitaciones,nombre',
      'numero'             => 'required|string|max:20|unique:habitaciones,numero',
      'piso'               => 'required|integer',
      'capacidad'          => 'required|integer|min:1',
      'medida'             => 'required|string|max:255',
      'descripcion'        => 'required|string|max:255',
    ];
  }
}
