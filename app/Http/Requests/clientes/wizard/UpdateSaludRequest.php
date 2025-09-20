<?php
namespace App\Http\Requests\clientes\wizard;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSaludRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        // Viene como: allergies (array<string>), dietaryRestrictions (array<string>), medicalNotes (string)
        $this->merge([
            'allergies'            => $this->input('allergies', null),
            'dietary_restrictions' => $this->input('dietaryRestrictions', null),
            'medical_notes'        => $this->input('medicalNotes', null),
        ]);

        // normaliza arrays si vinieran como string "a,b,c"
        foreach (['allergies','dietary_restrictions'] as $key) {
            $val = $this->input($key);
            if (is_string($val)) {
                $val = array_values(array_filter(array_map('trim', explode(',', $val))));
                $this->merge([$key => $val]);
            }
            if (is_array($val)) {
                $this->merge([$key => array_values(array_unique($val))]);
            }
        }
    }

    public function rules(): array
    {
        // Catálogos exactos del UI:
        $ALLERGIES = ['Nueces','Mariscos','Lácteos','Gluten','Huevos','Soja','Pescado','Abejas'];
        $DIET = ['Vegetariano','Vegano','Sin Gluten','Kosher','Halal','Sin Azúcar'];

        return [
            'allergies'              => ['sometimes','nullable','array'],
            'allergies.*'            => ['in:'.implode(',', $ALLERGIES)],
            'dietary_restrictions'   => ['sometimes','nullable','array'],
            'dietary_restrictions.*' => ['in:'.implode(',', $DIET)],
            'medical_notes'          => ['sometimes','nullable','string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'allergies'            => 'alergias',
            'dietary_restrictions' => 'restricciones dietéticas',
            'medical_notes'        => 'notas médicas',
        ];
    }
}
