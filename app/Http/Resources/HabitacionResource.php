<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HabitacionResource extends JsonResource
{
    public function toArray($request): array {
        return [
            'id'            => $this->id,
            'numero'        => $this->numero,
            'piso'          => $this->piso,
            'estado'        => $this->estado,
            'tarifa_noche'  => $this->tarifa_noche ? (float) $this->tarifa_noche : null,
            'tarifa_efectiva'=> (float) $this->tarifa_efectiva,
            'habilitada'    => (bool) $this->habilitada,
            'tipo' => [
                'id'         => $this->tipo->id ?? null,
                'nombre'     => $this->tipo->nombre ?? null,
                'codigo'     => $this->tipo->codigo ?? null,
                'capacidad'  => $this->tipo->capacidad ?? null,
                'tarifa_base'=> isset($this->tipo) ? (float) $this->tipo->tarifa_base : null,
            ],
            'created_at' => $this->created_at,
        ];
    }
}
