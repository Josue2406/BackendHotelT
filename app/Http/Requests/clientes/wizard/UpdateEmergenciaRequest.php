<?php
namespace App\Http\Requests\clientes\wizard;

use Illuminate\Foundation\Http\FormRequest;

// app/Http/Requests/clientes/wizard/UpdateEmergenciaRequest.php
class UpdateEmergenciaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $ec = (array) $this->input('emergencyContact', []);

        $email = $this->input('email', $ec['email'] ?? null);
        $phone = $this->input('phone', $ec['phone'] ?? null);

        if (is_string($email)) $email = strtolower(trim($email));
        if (is_string($phone)) $phone = preg_replace('/[^\d+]/', '', $phone); // deja + y dígitos

        $this->merge([
            'name'        => $this->input('name', $ec['name'] ?? null),
            'relationship'=> $this->input('relationship', $ec['relationship'] ?? null),
            'email'       => $email,
            'phone'       => $phone,
        ]);
    }

    public function rules(): array
    {
        return [
            'name'         => ['sometimes','nullable','string','max:100'],
            'relationship' => ['sometimes','nullable','string','max:60'],
            'phone'        => ['sometimes','nullable','string','max:50'],
            'email'        => ['sometimes','nullable','email','max:150'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'         => 'nombre del contacto',
            'relationship' => 'relación',
            'phone'        => 'teléfono de emergencia',
            'email'        => 'email de emergencia',
        ];
    }
}
