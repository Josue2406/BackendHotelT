<?php
namespace App\Http\Requests\frontdesk;

use Illuminate\Foundation\Http\FormRequest;

class StoreWalkInRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'id_cliente_titular' => 'required|integer|exists:clientes,id_cliente',
            'id_fuente'          => 'nullable|integer|exists:fuentes,id_fuente',
            'id_hab'             => 'required|integer|exists:habitaciones,id_habitacion',
            'fecha_llegada'      => 'required|date',
            'fecha_salida'       => 'required|date|after:fecha_llegada',
            'adultos'            => 'required|integer|min:1',
            'ninos'              => 'nullable|integer|min:0',
            'bebes'              => 'nullable|integer|min:0',
            'id_estado_estadia'  => 'nullable|integer|exists:estado_estadia,id_estado_estadia',
            'nombre_asignacion'  => 'nullable|string|max:30',
            'observacion_checkin'=> 'nullable|string|max:300',
        ];
    }
}
