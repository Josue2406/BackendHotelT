<?php

namespace App\Http\Requests\frontdesk;

use Illuminate\Foundation\Http\FormRequest;

class WalkinStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'cliente' => 'sometimes|array',
            'cliente.nombre' => 'required_with:cliente|string|max:255',
            'cliente.apellido1' => 'required_with:cliente|string|max:255',
            'cliente.email' => 'required_with:cliente|email|max:255',
            'cliente.telefono' => 'required_with:cliente|string|max:20',
            'cliente.id_tipo_doc' => 'required_with:cliente|integer',
            'cliente.numero_doc' => 'required_with:cliente|string|max:50',
            'cliente.nacionalidad' => 'required_with:cliente|string|max:100',
            
            'habitacion' => 'required|array',
            'habitacion.id_habitacion' => 'required|integer|exists:habitaciones,id_habitacion',
            
            'estadia' => 'required|array',
            'estadia.fecha_checkin' => 'required|date',
            'estadia.fecha_checkout' => 'required|date|after:estadia.fecha_checkin',
            'estadia.notas' => 'nullable|string',
            
            'huespedes' => 'required|array|min:1',
            'huespedes.*.id_cliente' => 'nullable|integer|exists:clientes,id_cliente',
            'huespedes.*.es_titular' => 'required|boolean',
        ];
    }

    protected function prepareForValidation()
    {
        $original = $this->all();
        
        if (isset($original['cliente']) || isset($original['habitacion']) || isset($original['estadia'])) {
            $transformed = [];
            
            if (isset($original['habitacion']['id_habitacion'])) {
                $transformed['id_hab'] = $original['habitacion']['id_habitacion'];
            }
            
            if (isset($original['estadia'])) {
                $transformed['fecha_llegada'] = $original['estadia']['fecha_checkin'] ?? null;
                $transformed['fecha_salida'] = $original['estadia']['fecha_checkout'] ?? null;
                $transformed['observacion_checkin'] = $original['estadia']['notas'] ?? null;
            }
            
            if (isset($original['huespedes']) && is_array($original['huespedes'])) {
                foreach ($original['huespedes'] as $huesped) {
                    if (!empty($huesped['id_cliente'])) {
                        $transformed['id_cliente'] = $huesped['id_cliente'];
                        break;
                    }
                }
            }
            
            if (isset($original['cliente'])) {
                $transformed['cedula'] = $original['cliente']['numero_doc'] ?? null;
                $transformed['cliente'] = $original['cliente'];
            }
            
            $this->merge($transformed);
        }
    }
}
