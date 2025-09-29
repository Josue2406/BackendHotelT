<?php
namespace App\Http\Requests\clientes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class StoreClienteFullRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        // ---- Normalización base ----
        $normStr = function($v){ return is_string($v) ? trim($v) : $v; };
        $lower   = function($v){ return $v !== null ? strtolower(trim($v)) : $v; };

        $email = $lower($this->input('email'));
        $telefono = preg_replace('/[^\d+]/','', (string)$this->input('telefono',''));

        // género (Masculino/Femenino/Otro) -> M/F/Otro
        $genMap = ['masculino'=>'M','femenino'=>'F','otro'=>'Otro'];
        $genero = $this->input('genero');
        $genero = $genero ? ($genMap[mb_strtolower($genero)] ?? $genero) : null;

        // fecha_nacimiento dd/mm/yyyy -> Y-m-d (si viene así)
        $fn = $this->input('fecha_nacimiento');
        if (is_string($fn) && preg_match('#^\d{2}/\d{2}/\d{4}$#', $fn)) {
            try { $fn = Carbon::createFromFormat('d/m/Y', $fn)->format('Y-m-d'); } catch(\Throwable $e){}
        }

        // VIP: si no viene, default false (checkbox visible)
        $esVip = $this->has('es_vip') || $this->has('esVip')
            ? filter_var($this->input('es_vip', $this->input('esVip')), FILTER_VALIDATE_BOOLEAN)
            : false;

        // ---- Room Preferences (anidado) ----
        $rp = (array)$this->input('roomPreferences', []);
        $smoking = array_key_exists('smokingAllowed',$rp)
            ? filter_var($rp['smokingAllowed'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;

        // ---- Travel Profile (companions anidado) ----
        $cmp = (array)$this->input('companions', []);
        $hasChildren = array_key_exists('hasChildren',$cmp)
            ? filter_var($cmp['hasChildren'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;
        $children = $cmp['childrenAgeRanges'] ?? null;
        if (is_string($children)) {
            $children = array_values(array_filter(array_map('trim', explode(',',$children))));
        }
        if (is_array($children)) $children = array_values(array_unique($children));
        $needsConn = array_key_exists('needsConnectedRooms', $cmp)
            ? filter_var($cmp['needsConnectedRooms'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;

        // ---- Medical (anidado) ----
        $allergies  = $this->input('allergies'); // array<string> o string "a,b"
        if (is_string($allergies)) $allergies = array_values(array_filter(array_map('trim', explode(',', $allergies))));
        $diet  = $this->input('dietaryRestrictions');
        if (is_string($diet)) $diet = array_values(array_filter(array_map('trim', explode(',', $diet))));
        if (is_array($allergies)) $allergies = array_values(array_unique($allergies));
        if (is_array($diet)) $diet = array_values(array_unique($diet));

        // ---- Emergency (anidado) ----
        $ec = (array)$this->input('emergencyContact', []);
        $ecEmail = $lower($ec['email'] ?? null);
        $ecPhone = isset($ec['phone']) ? preg_replace('/[^\d+]/','', (string)$ec['phone']) : null;

        $this->merge([
            // cliente
            'email'            => $email,
            'telefono'         => $telefono ?: null,
            'genero'           => $genero,
            'fecha_nacimiento' => $fn ?: null,
            'es_vip'           => (bool)$esVip,

            // preferencias (hasOne)
            'bed_type'         => $rp['bedType']   ?? null,
            'floor'            => $rp['floor']     ?? null,
            'view'             => $rp['view']      ?? null,
            'smoking_allowed'  => $smoking,

            // perfil viaje (hasOne)
            'typical_travel_group' => $cmp['typicalTravelGroup'] ?? null,
            'has_children'         => $hasChildren,
            'children_age_ranges'  => $children,
            'preferred_occupancy'  => $cmp['preferredOccupancy'] ?? null,
            'needs_connected_rooms'=> $needsConn,

            // salud (hasOne)
            'medical_notes'        => $this->input('medicalNotes'),
            'allergies'            => $allergies,
            'dietary_restrictions' => $diet,

            // emergencia (hasOne)
            'ec_name'         => $normStr($ec['name'] ?? null),
            'ec_relationship' => $normStr($ec['relationship'] ?? null),
            'ec_phone'        => $ecPhone,
            'ec_email'        => $ecEmail,
        ]);
    }

    public function rules(): array
    {
        $ALLERGIES = ['Nueces','Mariscos','Lácteos','Gluten','Huevos','Soja','Pescado','Abejas'];
        $DIET = ['Vegetariano','Vegano','Sin Gluten','Kosher','Halal','Sin Azúcar'];

        return [
            // Cliente (requeridos del paso 1)
            'nombre'       => 'required|string|max:60',
            'apellido1'    => 'required|string|max:60',
            'apellido2'    => 'nullable|string|max:60',
            'email'        => ['required','email:rfc','max:50', Rule::unique('clientes','email')],
            'telefono'     => ['required','string','max:50', Rule::unique('clientes','telefono')],
            'nacionalidad' => 'required|string|max:60',

            // Documento (ajusta a required si tu negocio lo exige)
            'id_tipo_doc'  => 'nullable|integer|exists:tipo_doc,id_tipo_doc',
            'numero_doc'   => [
                'nullable','string','max:40',
                Rule::unique('clientes','numero_doc')->where(fn($q)=>$q->where('id_tipo_doc', $this->input('id_tipo_doc')))
            ],

            // Adicionales
            'direccion'        => 'nullable|string|max:200',
            'fecha_nacimiento' => 'nullable|date',
            'genero'           => ['nullable', Rule::in(['M','F','Otro'])],
            'es_vip'           => 'boolean',
            'notas_personal'   => 'nullable|string',

            // Preferencias (enum según FE)
            'bed_type'        => ['sometimes','nullable', Rule::in(['single','double','queen','king','twin'])],
            'floor'           => ['sometimes','nullable', Rule::in(['low','middle','high'])],
            'view'            => ['sometimes','nullable', Rule::in(['ocean','mountain','city','garden'])],
            'smoking_allowed' => ['sometimes','nullable','boolean'],

            // Perfil viaje
            'typical_travel_group' => ['sometimes','nullable', Rule::in(['solo','couple','family','business_group','friends'])],
            'has_children'         => ['sometimes','nullable','boolean'],
            'children_age_ranges'  => ['sometimes','nullable','array'],
            'children_age_ranges.*'=> ['in:0-2,3-7,8-12,13-17'],
            'preferred_occupancy'  => ['sometimes','nullable','integer','min:1','max:10'],
            'needs_connected_rooms'=> ['sometimes','nullable','boolean'],

            // Salud
            'allergies'              => ['sometimes','nullable','array'],
            'allergies.*'            => ['in:'.implode(',', $ALLERGIES)],
            'dietary_restrictions'   => ['sometimes','nullable','array'],
            'dietary_restrictions.*' => ['in:'.implode(',', $DIET)],
            'medical_notes'          => ['sometimes','nullable','string'],

            // Emergencia
            'ec_name'         => ['sometimes','nullable','string','max:100'],
            'ec_relationship' => ['sometimes','nullable','string','max:60'],
            'ec_phone'        => ['sometimes','nullable','string','max:50'],
            'ec_email'        => ['sometimes','nullable','email','max:150'],
        ];
    }
}
