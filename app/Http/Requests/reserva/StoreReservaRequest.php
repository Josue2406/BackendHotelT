<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservaRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'id_cliente'   => 'required|integer|exists:clientes,id_cliente',
            'id_estado_res'=> 'required|integer|exists:estado_reserva,id_estado_res',
            'id_fuente'    => 'nullable|integer|exists:fuentes,id_fuente',
            'adultos'      => 'required|integer|min:1',
            'ninos'        => 'required|integer|min:0',
            'bebes'        => 'required|integer|min:0',
            'total_monto_reserva' => 'required|numeric|min:0',
            'notas'        => 'nullable|string|max:300',
        ];
    }
}
