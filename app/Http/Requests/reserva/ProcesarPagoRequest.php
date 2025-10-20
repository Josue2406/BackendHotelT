<?php

namespace App\Http\Requests\reserva;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\catalago_pago\EstadoPago;
use App\Services\ExchangeRateService;

class ProcesarPagoRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $exchangeService = app(ExchangeRateService::class);
        $monedasSoportadas = array_keys(ExchangeRateService::MONEDAS_SOPORTADAS);

        return [
            // Monto del pago
            'monto' => 'required|numeric|min:0.01',

            // Moneda del pago (ej: USD, CRC, EUR)
            'codigo_moneda' => [
                'required',
                'string',
                'size:3',
                function ($attribute, $value, $fail) use ($exchangeService) {
                    if (!$exchangeService->estaMonedaSoportada($value)) {
                        $fail('La moneda seleccionada no está soportada.');
                    }
                },
            ],

            // Método de pago (tarjeta, efectivo, transferencia, etc.)
            'id_metodo_pago' => 'required|integer|exists:metodo_pago,id_metodo_pago',

            // Estado del pago
            'id_estado_pago' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $estadosValidos = [
                        EstadoPago::ESTADO_COMPLETADO,
                        EstadoPago::ESTADO_PARCIAL,
                        EstadoPago::ESTADO_PENDIENTE,
                    ];

                    if (!in_array($value, $estadosValidos)) {
                        $fail('El estado de pago debe ser Completado, Parcial o Pendiente.');
                    }
                },
            ],

            // Campos opcionales
            'id_tipo_transaccion' => 'nullable|integer|exists:tipo_transaccion,id_tipo_transaccion',
            'referencia' => 'nullable|string|max:100',
            'notas' => 'nullable|string|max:500',
        ];
    }

    /**
     * Configurar validador para validaciones personalizadas
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Validar que el monto no exceda el pendiente de la reserva
            $reserva = $this->route('reserva');

            if ($reserva) {
                $montoPago = $this->input('monto');
                $montoPendiente = $reserva->monto_pendiente;

                // if ($montoPago > $montoPendiente) {
                //     $validator->errors()->add(
                //         'monto',
                //         "El monto del pago (\${$montoPago}) no puede ser mayor al monto pendiente (\${$montoPendiente})."
                //     );
                // }

                // Advertir si el pago es muy bajo
                $montoMinimo = $reserva->total_monto_reserva * ($reserva->porcentaje_minimo_pago / 100);

                if ($montoPago < $montoMinimo && $reserva->monto_pagado == 0) {
                    $validator->errors()->add(
                        'monto',
                        "El pago inicial debe ser al menos el {$reserva->porcentaje_minimo_pago}% del total (\${$montoMinimo}) para confirmar la reserva."
                    );
                }
            }
        });
    }

    /**
     * Mensajes de error personalizados
     */
    public function messages(): array
    {
        return [
            'monto.required' => 'El monto del pago es obligatorio.',
            'monto.numeric' => 'El monto debe ser un número válido.',
            'monto.min' => 'El monto debe ser mayor a 0.',
            'id_metodo_pago.required' => 'El método de pago es obligatorio.',
            'id_metodo_pago.exists' => 'El método de pago seleccionado no es válido.',
            'id_tipo_transaccion.exists' => 'El tipo de transacción seleccionado no es válido.',
            'referencia_transaccion.max' => 'La referencia de transacción no puede exceder 100 caracteres.',
            'notas.max' => 'Las notas no pueden exceder 300 caracteres.',
        ];
    }
}