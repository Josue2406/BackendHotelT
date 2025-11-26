<?php

namespace App\Http\Requests\reserva;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServicioRequest extends FormRequest
{
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
        $servicioId = $this->route('servicio')->id_servicio ?? null;

        return [
            'nombre'      => 'sometimes|required|string|max:100|unique:servicio,nombre,' . $servicioId . ',id_servicio',
            'precio'      => 'sometimes|required|numeric|min:0',
            'descripcion' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'   => 'El nombre del servicio es obligatorio.',
            'nombre.unique'     => 'Ya existe un servicio con ese nombre.',
            'precio.required'   => 'El precio es obligatorio.',
            'precio.min'        => 'El precio debe ser mayor o igual a 0.',
        ];
    }
}
