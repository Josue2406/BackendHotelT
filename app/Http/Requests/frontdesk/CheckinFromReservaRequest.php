<?php
namespace App\Http\Requests\frontdesk;

use Illuminate\Foundation\Http\FormRequest;

class CheckinFromReservaRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
        'id_cliente_titular' => 'required|integer|exists:clientes,id_cliente',
        'fecha_llegada' => 'required|date',
        'fecha_salida' => 'required|date|after_or_equal:fecha_llegada',
        'adultos' => 'required|integer|min:1',
        'ninos' => 'nullable|integer|min:0',
        'bebes' => 'nullable|integer|min:0',
        //'id_hab' => 'required|integer|exists:habitaciones,id_hab',
        'id_hab' => 'required|integer|exists:habitaciones,id_habitacion',
        'nombre_asignacion' => 'nullable|string|max:150',
        'observacion_checkin' => 'nullable|string|max:255',
        'id_fuente' => 'nullable|integer',

        // 🔹 NUEVO: aceptar arreglo de acompañantes
        'acompanantes' => 'nullable|array',
        'acompanantes.*.nombre' => 'required|string|max:100',
        'acompanantes.*.documento' => 'nullable|string|max:50',
        'acompanantes.*.email' => 'nullable|email|max:150',
        'acompanantes.*.telefono' => 'nullable|string|max:50',

        'pago_modo' => 'nullable|string|in:general,por_persona',

    ];
    }
}
