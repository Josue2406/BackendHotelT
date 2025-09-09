<?php
namespace App\Http\Requests\clientes;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClienteRequest extends FormRequest {
  public function authorize(): bool { return true; }
  public function rules(): array {
    $id = $this->route('cliente')?->id_cliente ?? $this->route('cliente') ?? $this->route('id');
    return [
      'nombre'           => 'sometimes|string|max:60',
      'apellido1'        => 'sometimes|string|max:60',
      'apellido2'        => 'nullable|string|max:60',
      'email'            => 'sometimes|email|max:50|unique:clientes,email,'.$id.',id_cliente',
      'telefono'         => 'sometimes|string|max:50|unique:clientes,telefono,'.$id.',id_cliente',
      'id_tipo_doc'      => 'nullable|exists:tipo_doc,id_tipo_doc',
      'numero_doc'       => 'nullable|string|max:40',
      'nacionalidad'     => 'sometimes|string|max:60',
      'direccion'        => 'nullable|string|max:200',
      'fecha_nacimiento' => 'nullable|date',
      'genero'           => 'nullable|string|max:255',
    ];
  }
}
