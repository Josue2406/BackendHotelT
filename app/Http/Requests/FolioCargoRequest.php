<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para agregar cargos a un folio
 * 
 * Este FormRequest centraliza las reglas de validación para el endpoint
 * POST /folios/{id}/cargos, permitiendo reutilización y mejores mensajes de error.
 * 
 * @package App\Http\Requests
 */
class FolioCargoRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para hacer esta solicitud.
     *
     * @return bool
     */
    public function authorize()
    {
        // Si tienes lógica de autorización específica, impleméntala aquí
        // Por ahora permitimos todas las solicitudes autenticadas
        return true;
    }

    /**
     * Reglas de validación para el cargo
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'monto' => [
                'required',
                'numeric',
                'gt:0',
                'regex:/^\d+(\.\d{1,2})?$/', // Máximo 2 decimales
            ],
            'descripcion' => [
                'required',
                'string',
                'max:255',
                'min:3',
            ],
            'cliente_id' => [
                'nullable',
                'integer',
                'exists:clientes,id_cliente',
            ],
        ];
    }

    /**
     * Mensajes de error personalizados
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            // Mensajes para 'monto'
            'monto.required' => 'El monto del cargo es obligatorio',
            'monto.numeric' => 'El monto debe ser un valor numérico',
            'monto.gt' => 'El monto debe ser mayor a 0',
            'monto.regex' => 'El monto no puede tener más de 2 decimales',

            // Mensajes para 'descripcion'
            'descripcion.required' => 'La descripción del cargo es obligatoria',
            'descripcion.string' => 'La descripción debe ser texto',
            'descripcion.max' => 'La descripción no puede exceder 255 caracteres',
            'descripcion.min' => 'La descripción debe tener al menos 3 caracteres',

            // Mensajes para 'cliente_id'
            'cliente_id.integer' => 'El ID del cliente debe ser un número entero',
            'cliente_id.exists' => 'El cliente especificado no existe en el sistema',
        ];
    }

    /**
     * Nombres personalizados para los atributos (para mensajes de error)
     *
     * @return array<string, string>
     */
    public function attributes()
    {
        return [
            'monto' => 'monto del cargo',
            'descripcion' => 'descripción',
            'cliente_id' => 'ID del cliente',
        ];
    }
}
