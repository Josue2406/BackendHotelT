<?php
namespace App\Http\Requests\reserva;

use Illuminate\Foundation\Http\FormRequest;

class AddReservaServicioRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'id_servicio'    => 'required|integer|exists:servicio,id_servicio',
            'cantidad'       => 'required|integer|min:1',
            'precio_unitario'=> 'nullable|numeric|min:0', // Opcional, se toma del servicio si no se envía
            'fecha_servicio' => 'nullable|date',
            'descripcion'    => 'nullable|string|max:200',
        ];
    }
}
