<?php
namespace App\Http\Requests\clientes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class StoreClienteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $toTrim = [
            'nombre','apellido1','apellido2','email','telefono','numero_doc',
            'nacionalidad','direccion','genero','fecha_nacimiento','notas_personal' // ← notas
        ];
        $data = $this->only($toTrim);

        foreach ($toTrim as $k) {
            if (array_key_exists($k, $data)) {
                $v = is_string($data[$k]) ? trim($data[$k]) : $data[$k];
                if ($k === 'email' && $v !== null) $v = strtolower($v);
                if (in_array($k, ['apellido2','email','telefono','numero_doc','nacionalidad','direccion','genero','notas_personal'], true) && $v === '') {
                    $v = null;
                }
                $data[$k] = $v;
            }
        }

        // Normaliza teléfono: deja sólo + y dígitos
        if (!empty($data['telefono']) && is_string($data['telefono'])) {
            $data['telefono'] = preg_replace('/[^\d+]/', '', $data['telefono']);
        }

        // Mapea género UI → BD
        if (!empty($data['genero'])) {
            $map = ['masculino'=>'M','femenino'=>'F','otro'=>'Otro'];
            $lower = mb_strtolower($data['genero']);
            $data['genero'] = $map[$lower] ?? $data['genero'];
        }

        // Fecha dd/mm/yyyy → Y-m-d (si aplica)
        if (!empty($data['fecha_nacimiento']) && is_string($data['fecha_nacimiento'])) {
            if (preg_match('#^\d{2}/\d{2}/\d{4}$#', $data['fecha_nacimiento'])) {
                try {
                    $data['fecha_nacimiento'] = Carbon::createFromFormat('d/m/Y', $data['fecha_nacimiento'])->format('Y-m-d');
                } catch (\Throwable $e) { /* validación lo capturará si no es válida */ }
            }
        }

        /* VIP (acepta true/false/1/0/on/off)
        $esVip = $this->input('es_vip', null);
        if ($esVip !== null) {
            $esVip = filter_var($esVip, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $this->merge([
            ...$data,
            'es_vip' => $esVip, // puede quedar null si no lo envían; validación lo maneja
        ]); 
    }*/

        
        // VIP: si viene, parsea a bool; si NO viene, fuerza false (porque el checkbox siempre existe)
    if ($this->has('es_vip') || $this->has('esVip')) {
        $raw = $this->has('es_vip') ? $this->input('es_vip') : $this->input('esVip');
        $vip = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        // Si llega "", null u "off", trátalo como false
        $this->merge(['es_vip' => (bool) $vip]);
    } else {
        $this->merge(['es_vip' => false]);
    }
    }

    public function rules(): array
    {
        return [
            'nombre'           => 'required|string|max:60',
            'apellido1'        => 'required|string|max:60',
            'apellido2'        => 'nullable|string|max:60',

            'email'            => ['required','email:rfc','max:50', Rule::unique('clientes','email')],
            'telefono'         => ['required','string','max:50', Rule::unique('clientes','telefono')],

            // Si el UI los quiere obligatorios, cambia nullable→required
            'id_tipo_doc'      => 'nullable|integer|exists:tipo_doc,id_tipo_doc',
            'numero_doc'       => [
                'nullable','string','max:40',
                Rule::unique('clientes','numero_doc')
                    ->where(fn($q) => $q->where('id_tipo_doc', $this->input('id_tipo_doc'))),
            ],

            'nacionalidad'     => 'required|string|max:60',
            'direccion'        => 'nullable|string|max:200',
            'fecha_nacimiento' => 'nullable|date',
            'genero'           => ['nullable', Rule::in(['M','F','Otro'])],

            // ← NUEVO
            'es_vip'           => 'sometimes|boolean',
            'notas_personal'   => 'nullable|string', // agrega max si quieres, p.ej. max:2000
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'      => 'Ya existe un cliente con ese correo.',
            'telefono.unique'   => 'Ya existe un cliente con ese teléfono.',
            'numero_doc.unique' => 'Ya existe un cliente con ese tipo y número de documento.',
            'id_tipo_doc.exists'=> 'El tipo de documento seleccionado no existe.',
            'genero.in'         => 'Género debe ser M, F u Otro.',
        ];
    }

    public function attributes(): array
    {
        return [
            'apellido1'        => 'primer apellido',
            'apellido2'        => 'segundo apellido',
            'id_tipo_doc'      => 'tipo de documento',
            'numero_doc'       => 'número de documento',
            'fecha_nacimiento' => 'fecha de nacimiento',
            'notas_personal'   => 'notas',
        ];
    }
}
