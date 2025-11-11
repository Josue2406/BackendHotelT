<?php
<<<<<<< HEAD
=======

>>>>>>> 82c6c4c15da2daa96d38c9004c2be44a663fa9d0
namespace App\Http\Requests\reserva;

use Illuminate\Foundation\Http\FormRequest;

class StoreServicioRequest extends FormRequest
{
<<<<<<< HEAD
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'nombre'      => 'required|string|max:120',
=======
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre'      => 'required|string|max:100|unique:servicio,nombre',
>>>>>>> 82c6c4c15da2daa96d38c9004c2be44a663fa9d0
            'precio'      => 'required|numeric|min:0',
            'descripcion' => 'nullable|string|max:500',
        ];
    }
<<<<<<< HEAD
=======

    public function messages(): array
    {
        return [
            'nombre.required'   => 'El nombre del servicio es obligatorio.',
            'nombre.unique'     => 'Ya existe un servicio con ese nombre.',
            'precio.required'   => 'El precio es obligatorio.',
            'precio.min'        => 'El precio debe ser mayor o igual a 0.',
        ];
    }
>>>>>>> 82c6c4c15da2daa96d38c9004c2be44a663fa9d0
}
