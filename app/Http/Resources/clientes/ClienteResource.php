<?php
namespace App\Http\Resources\clientes;

use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResource extends JsonResource
{
    // Si quieres quitar el wrapper "data", descomenta:
    public static $wrap = null;

    public function toArray($request)
    {
        return [
            'id_cliente'       => $this->id_cliente,
            'nombre_completo'  => $this->nombre_completo ?? trim(implode(' ', array_filter([
                                        $this->nombre, $this->apellido1, $this->apellido2
                                    ]))),
            'nombre'           => $this->nombre,
            'apellido1'        => $this->apellido1,
            'apellido2'        => $this->apellido2,
            'email'            => $this->email,
            'telefono'         => $this->telefono,
            'id_tipo_doc'      => $this->id_tipo_doc,
            'tipo_documento'   => optional($this->whenLoaded('tipoDocumento'))->descripcion,
            'numero_doc'       => $this->numero_doc,
            'nacionalidad'     => $this->nacionalidad,
            'direccion'        => $this->direccion,
            'fecha_nacimiento' => $this->fecha_nacimiento?->format('Y-m-d'),
            //'fecha_nacimiento' => $this->fecha_nacimiento, // ya casteado a Y-m-d en el modelo
            'genero'           => $this->genero,
            'es_vip'         => $this->es_vip,
  'notas_personal' => $this->notas_personal,
            'preferencias'       => $this->whenLoaded('preferencias'),
  'perfil_viaje'       => $this->whenLoaded('perfilViaje'),
  'salud'              => $this->whenLoaded('salud'),
  'contacto_emergencia'=> $this->whenLoaded('contactoEmergencia'),
            'created_at'       => optional($this->created_at)->toDateTimeString(),
            'updated_at'       => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}


/* si quiero remover el wrapper data globalmente, en AppServiceProvider@boot()
use Illuminate\Http\Resources\Json\JsonResource;
JsonResource::withoutWrapping();
*/ 