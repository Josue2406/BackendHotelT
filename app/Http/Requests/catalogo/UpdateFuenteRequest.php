<?php // app/Http/Requests/UpdateFuenteRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFuenteRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        $id = $this->route('fuente')->id_fuente ?? null;
        return [
            'nombre' => ['sometimes','string','max:100', Rule::unique('fuentes','nombre')->ignore($id,'id_fuente')],
            'codigo' => ['sometimes','string','max:5',   Rule::unique('fuentes','codigo')->ignore($id,'id_fuente')],
        ];
    }
}
