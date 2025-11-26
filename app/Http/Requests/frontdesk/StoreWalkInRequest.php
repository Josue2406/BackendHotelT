<?php
namespace App\Http\Requests\frontdesk;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreWalkInRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $data = $this->all();
        

        // Compatibilidad con front antiguo
        if (!isset($data['id_cliente']) && isset($data['id_cliente_titular'])) {
            $data['id_cliente'] = $data['id_cliente_titular'];
        }
        if (!isset($data['id_hab']) && isset($data['id_habitacion'])) {
            $data['id_hab'] = $data['id_habitacion'];
        }
        if (!isset($data['id_tipo_hab']) && isset($data['id_tipos_hab'])) {
            $data['id_tipo_hab'] = $data['id_tipos_hab'];
        }

        if (isset($data['cedula']) && is_string($data['cedula'])) {
            $data['cedula'] = trim($data['cedula']);
        }

        $this->replace($data);
    }

    public function rules(): array
    {
        return [
            // Huésped: al menos uno entre id_cliente o cedula
            'id_cliente'         => ['nullable','integer','exists:clientes,id_cliente'],
            'cedula'             => ['nullable','string','max:50'],

            // Habitación y tipo
            'id_tipos_hab'       => ['nullable','integer','exists:tipo_habitacion,id_tipos_hab'],
            'id_tipo_hab'        => ['nullable','integer','exists:tipo_habitacion,id_tipos_hab'],
            'id_habitacion'      => ['required','integer','exists:habitaciones,id_habitacion'],
            'id_hab'             => ['nullable','integer','exists:habitaciones,id_habitacion'],

            // Fechas
            'fecha_llegada'      => ['required','date','before_or_equal:fecha_salida'],
            'fecha_salida'       => ['required','date','after_or_equal:fecha_llegada'],

            // Composición
            'adultos'            => ['required','integer','min:1'],
            'ninos'              => ['nullable','integer','min:0'],
            'bebes'              => ['nullable','integer','min:0'],

            // Opcionales
            'id_fuente'          => ['nullable','integer','exists:fuentes,id_fuente'],
            'id_estado_estadia'  => ['nullable','integer','exists:estado_estadia,id_estado_estadia'],
            'nombre_asignacion'  => ['nullable','string','max:255'],
            'observacion_checkin'=> ['nullable','string','max:1000'],
        ];

        
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (empty($this->input('id_cliente')) && empty($this->input('cedula'))) {
                $v->errors()->add('id_cliente', 'Debes indicar el cliente por ID o por cédula.');
                $v->errors()->add('cedula', 'Debes indicar el cliente por ID o por cédula.');
            }
        });
    }

    protected function passedValidation(): void
    {
        $this->merge([
            'id_cliente'  => $this->filled('id_cliente') ? (int) $this->input('id_cliente') : null,
            'id_tipo_hab' => $this->filled('id_tipo_hab') ? (int) $this->input('id_tipo_hab') : null,
            'id_hab'      => (int) $this->input('id_hab'),
            'adultos'     => (int) $this->input('adultos'),
            'ninos'       => $this->filled('ninos') ? (int) $this->input('ninos') : 0,
            'bebes'       => $this->filled('bebes') ? (int) $this->input('bebes') : 0,
        ]);
    }
}




//
/*
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
            //'nombre_asignacion'  => 'nullable|string|max:30',
            'observacion_checkin'=> 'nullable|string|max:300',
        ];
    }
}
*/