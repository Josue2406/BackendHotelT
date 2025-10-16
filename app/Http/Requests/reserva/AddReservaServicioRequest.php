<?php
namespace App\Http\Requests\reserva;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\reserva\Servicio;

class AddReservaServicioRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array {
        return [
            'id_servicio'    => 'required|integer|exists:servicio,id_servicio',
            'cantidad'       => 'required|integer|min:1',
            'precio_unitario'=> 'nullable|numeric|min:0', // Opcional, se toma del servicio si no se envía
            'descuento'      => 'nullable|numeric|min:0|max:100', // Descuento en porcentaje
            'fecha_servicio' => 'nullable|date',
            'descripcion'    => 'nullable|string|max:200',
        ];
    }

    /**
     * Configurar validador para validaciones personalizadas
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Validar que el precio unitario no exceda excesivamente el precio base
            if ($this->has('precio_unitario') && $this->has('id_servicio')) {
                $servicio = Servicio::find($this->input('id_servicio'));
                $precioUnitario = $this->input('precio_unitario');

                if ($servicio && $precioUnitario !== null) {
                    $precioBase = $servicio->precio;

                    // Validar que el precio con descuento no sea negativo
                    if ($precioUnitario < 0) {
                        $validator->errors()->add(
                            'precio_unitario',
                            'El precio unitario no puede ser negativo.'
                        );
                    }

                    // Advertir si el precio es mucho menor al precio base (posible error)
                    if ($precioUnitario < ($precioBase * 0.1) && $precioUnitario > 0) {
                        $validator->errors()->add(
                            'precio_unitario',
                            "El precio unitario ({$precioUnitario}) es muy bajo comparado con el precio base del servicio ({$precioBase}). Verifique si es correcto."
                        );
                    }
                }
            }

            // Validar descuento si se proporciona
            if ($this->has('descuento')) {
                $descuento = $this->input('descuento');

                if ($descuento < 0 || $descuento > 100) {
                    $validator->errors()->add(
                        'descuento',
                        'El descuento debe estar entre 0% y 100%.'
                    );
                }
            }
        });
    }

    /**
     * Mensajes de error personalizados
     */
    public function messages(): array
    {
        return [
            'id_servicio.required' => 'El servicio es obligatorio.',
            'id_servicio.exists' => 'El servicio seleccionado no existe.',
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.min' => 'La cantidad debe ser al menos 1.',
            'precio_unitario.numeric' => 'El precio unitario debe ser un número.',
            'precio_unitario.min' => 'El precio unitario no puede ser negativo.',
            'descuento.numeric' => 'El descuento debe ser un número.',
            'descuento.min' => 'El descuento no puede ser negativo.',
            'descuento.max' => 'El descuento no puede ser mayor al 100%.',
            'fecha_servicio.date' => 'La fecha del servicio debe ser una fecha válida.',
        ];
    }
}
