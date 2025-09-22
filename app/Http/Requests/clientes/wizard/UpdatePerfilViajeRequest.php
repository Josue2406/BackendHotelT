<?php
namespace App\Http\Requests\clientes\wizard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePerfilViajeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $cmp = (array) $this->input('companions', []);

        $this->merge([
            'typical_travel_group' => $this->input('typical_travel_group', $cmp['typicalTravelGroup'] ?? null),
            'has_children'         => $this->has('has_children') ? $this->input('has_children') : ($cmp['hasChildren'] ?? null),
            'children_age_ranges'  => $this->input('children_age_ranges', $cmp['childrenAgeRanges'] ?? null),
            'preferred_occupancy'  => $this->input('preferred_occupancy', $cmp['preferredOccupancy'] ?? null),
            'needs_connected_rooms'=> $this->has('needs_connected_rooms') ? $this->input('needs_connected_rooms') : ($cmp['needsConnectedRooms'] ?? null),
        ]);

        // booleans
        foreach (['has_children','needs_connected_rooms'] as $b) {
            if ($this->has($b)) {
                $this->merge([$b => filter_var($this->input($b), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)]);
            }
        }

        // normaliza children_age_ranges: array o null
        $ages = $this->input('children_age_ranges');
        if (is_string($ages)) {
            // por si viniera como "0-2,3-7"
            $ages = array_values(array_filter(array_map('trim', explode(',', $ages))));
        }
        if (is_array($ages)) {
            $ages = array_values(array_unique($ages));
        }
        $this->merge(['children_age_ranges' => $ages]);
    }

    public function rules(): array
    {
        return [
            'typical_travel_group' => ['sometimes','nullable', Rule::in(['solo','couple','family','business_group','friends'])],
            'has_children'         => ['sometimes','nullable','boolean'],
            'children_age_ranges'  => ['sometimes','nullable','array'],
            'children_age_ranges.*'=> ['in:0-2,3-7,8-12,13-17'],
            'preferred_occupancy'  => ['sometimes','nullable','integer','min:1','max:10'],
            'needs_connected_rooms'=> ['sometimes','nullable','boolean'],
        ];
    }
}
