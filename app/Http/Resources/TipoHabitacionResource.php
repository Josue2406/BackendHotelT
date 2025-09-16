<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TipoHabitacionResource extends JsonResource
{
    public function toArray($request): array {
        return [
            'id'         => $this->id,
            'nombre'     => $this->nombre,
            'codigo'     => $this->codigo,
            'capacidad'  => $this->capacidad,
            'tarifa_base'=> (float) $this->tarifa_base,
            'amenidades' => $this->amenidades ?? [],
            'descripcion'=> $this->descripcion,
            'created_at' => $this->created_at,
        ];
    }
}
