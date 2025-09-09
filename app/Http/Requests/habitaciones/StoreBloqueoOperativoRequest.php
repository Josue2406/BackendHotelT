<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StoreBloqueoOperativoRequest extends FormRequest {
  public function authorize(): bool { return true; }
  public function rules(): array {
    return [
      'id_habitacion' => 'required|exists:habitaciones,id_habitacion',
      'tipo'          => 'required|in:OOO,OOS,INSPECCION',
      'motivo'        => 'nullable|string|max:200',
      'fecha_ini'     => 'required|date',
      'fecha_fin'     => 'required|date|after:fecha_ini',
    ];
  }
}
