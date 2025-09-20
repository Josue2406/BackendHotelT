<?php
namespace App\Http\Requests\clientes\wizard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePreferenciasRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        // Soporta payload anidado: roomPreferences.{bedType,floor,view,smokingAllowed}
        $rp = (array) $this->input('roomPreferences', []);

        $this->merge([
            'bed_type'        => $this->input('bed_type', $rp['bedType'] ?? null),
            'floor'           => $this->input('floor', $rp['floor'] ?? null),
            'view'            => $this->input('view', $rp['view'] ?? null),
            'smoking_allowed' => $this->has('smoking_allowed')
                ? $this->input('smoking_allowed')
                : ($rp['smokingAllowed'] ?? null),
        ]);

        // Normaliza boolean
        if ($this->has('smoking_allowed')) {
            $this->merge([
                'smoking_allowed' => filter_var(
                    $this->input('smoking_allowed'),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                )
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'bed_type'        => ['sometimes','nullable', Rule::in(['single','double','queen','king','twin'])],
            'floor'           => ['sometimes','nullable', Rule::in(['low','middle','high'])],
            'view'            => ['sometimes','nullable', Rule::in(['ocean','mountain','city','garden'])],
            'smoking_allowed' => ['sometimes','nullable','boolean'],
        ];
    }
}
