<?php
namespace App\Http\Requests\clientes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class UpdateClienteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        // Normaliza: trim; email a minúsculas; '' => null solo en campos realmente opcionales
        $toTrim = ['nombre','apellido1','apellido2','email','telefono','numero_doc','nacionalidad','direccion','genero','fecha_nacimiento','notas_personal'];
        $data = $this->only($toTrim);

        foreach ($toTrim as $k) {
            if (array_key_exists($k, $data)) {
                $v = is_string($data[$k]) ? trim($data[$k]) : $data[$k];
                if ($k === 'email' && $v !== null) $v = strtolower($v);

                // convertir vacío a null SOLO en opcionales reales
                if (in_array($k, ['apellido2','email','telefono','numero_doc','direccion','genero','notas_personal'], true) && $v === '') {
                    $v = null;
                }
                $data[$k] = $v;
            }
        }

        // Teléfono: deja solo + y dígitos (si viene)
        if (array_key_exists('telefono', $data) && is_string($data['telefono']) && $data['telefono'] !== null) {
            $data['telefono'] = preg_replace('/[^\d+]/', '', $data['telefono']);
        }

        // Mapea género UI -> BD (Masculino/Femenino/Otro -> M/F/Otro)
        if (array_key_exists('genero', $data) && $data['genero']) {
            $map = ['masculino'=>'M','femenino'=>'F','otro'=>'Otro'];
            $lower = mb_strtolower($data['genero']);
            $data['genero'] = $map[$lower] ?? $data['genero'];
        }

        // Fecha dd/mm/yyyy -> Y-m-d (si aplica)
        if (!empty($data['fecha_nacimiento']) && is_string($data['fecha_nacimiento'])) {
            if (preg_match('#^\d{2}/\d{2}/\d{4}$#', $data['fecha_nacimiento'])) {
                try {
                    $data['fecha_nacimiento'] = Carbon::createFromFormat('d/m/Y', $data['fecha_nacimiento'])->format('Y-m-d');
                } catch (\Throwable $e) { /* dejar que la validación falle si no es válida */ }
            }
        }

        /* VIP: acepta true/false/1/0/on/off
        if ($this->has('es_vip')) {
            $vip = filter_var($this->input('es_vip'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $data['es_vip'] = $vip;
        } */
         // Solo si viene es_vip/esVip, lo convertimos; si no viene, no lo tocamos
    if ($this->has('es_vip') || $this->has('esVip')) {
        $raw = $this->has('es_vip') ? $this->input('es_vip') : $this->input('esVip');
        $vip = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $this->merge(['es_vip' => (bool) $vip]);
    }

        $this->merge($data);
    }

    public function rules(): array
    {
        // Id para ignorar unicidad
        $routeCliente = $this->route('cliente');
        $id = is_object($routeCliente) ? ($routeCliente->id_cliente ?? null) : ($routeCliente ?? $this->route('id'));

        // Fallback para la regla compuesta si no envían id_tipo_doc en el update
        $currentTipoDoc = is_object($routeCliente) ? ($routeCliente->id_tipo_doc ?? null) : null;

        return [
            // Si vienen, no vacíos:
            'nombre'           => 'sometimes|string|filled|max:60',
            'apellido1'        => 'sometimes|string|filled|max:60',
            'apellido2'        => 'nullable|string|max:60',

            // En tu BD NO son nullable, así que no permitas nullable aquí
            'email'            => [
                'sometimes','email:rfc','max:50',
                Rule::unique('clientes','email')->ignore($id,'id_cliente'),
            ],
            'telefono'         => [
                'sometimes','string','filled','max:50',
                Rule::unique('clientes','telefono')->ignore($id,'id_cliente'),
            ],

            // Catálogo (nullable en BD)
            'id_tipo_doc'      => 'sometimes|nullable|integer|exists:tipo_doc,id_tipo_doc',

            // Unicidad compuesta: usa el id_tipo_doc enviado o el actual del modelo
            'numero_doc'       => [
                'sometimes','nullable','string','max:40',
                Rule::unique('clientes','numero_doc')
                    ->ignore($id,'id_cliente')
                    ->where(fn($q) => $q->where(
                        'id_tipo_doc',
                        $this->input('id_tipo_doc', $currentTipoDoc)
                    )),
            ],

            // En tu BD nacionalidad NO es nullable
            'nacionalidad'     => 'sometimes|string|filled|max:60',

            // Sí es nullable
            'direccion'        => 'sometimes|nullable|string|max:200',
            'fecha_nacimiento' => 'sometimes|nullable|date',
            'genero'           => ['sometimes','nullable', Rule::in(['M','F','Otro'])],

            // NUEVOS
            'es_vip'           => 'sometimes|boolean',
            'notas_personal'   => 'sometimes|nullable|string',
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
