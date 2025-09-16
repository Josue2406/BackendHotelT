<?php
namespace App\Http\Requests\reserva;

use Illuminate\Foundation\Http\FormRequest;

class CotizarReservaRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            // opcionalmente recibir noches/fechas y tipo hab para cotizar:
            'habitaciones' => 'required|array|min:1',
            'habitaciones.*.id_habitacion' => 'required|integer|exists:habitaciones,id_habitacion',
            'habitaciones.*.fecha_llegada' => 'required|date',
            'habitaciones.*.fecha_salida'  => 'required|date|after:habitaciones.*.fecha_llegada',
            'servicios' => 'sometimes|array',
            'servicios.*.id_servicio'     => 'required|integer|exists:servicio,id_servicio',
            'servicios.*.cantidad'        => 'required|integer|min:1',
            'servicios.*.precio_unitario' => 'required|numeric|min:0',
        ];
    }
}
