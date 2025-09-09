<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StoreClienteRequest extends FormRequest {
  public function authorize(): bool { return true; }
  public function rules(): array {
    return [
      'nombre'           => 'required|string|max:60',
      'apellido1'        => 'required|string|max:60',
      'apellido2'        => 'nullable|string|max:60',
      'email'            => 'required|email|max:50|unique:clientes,email',
      'telefono'         => 'required|string|max:50|unique:clientes,telefono',
      'id_tipo_doc'      => 'nullable|exists:tipo_doc,id_tipo_doc',
      'numero_doc'       => 'nullable|string|max:40',
      'nacionalidad'     => 'required|string|max:60',
      'direccion'        => 'nullable|string|max:200',
      'fecha_nacimiento' => 'nullable|date',
      'genero'           => 'nullable|string|max:255',
    ];
  }
}
