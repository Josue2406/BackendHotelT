<?php

namespace App\Http\Resources\house_keeping;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HistorialMantenimientoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id_historial_mant,
            'id_mantenimiento' => $this->id_mantenimiento,
            'evento'         => $this->evento,
            'fecha'          => $this->fecha ? $this->fecha->toDateTimeString() : null,
            'valor_anterior' => $this->valor_anterior,
            'valor_nuevo'    => $this->valor_nuevo,

            'actor' => $this->whenLoaded('actor', function () {
                return [
                    'id'       => $this->actor->id_usuario ?? null,
                    'nombre'   => $this->actor->nombre ?? null,
                    'apellido' => trim(($this->actor->apellido1 ?? '') . ' ' . ($this->actor->apellido2 ?? '')) ?: null,
                    'email'    => $this->actor->email ?? null,
                ];
            }),

            // 'mantenimiento' => $this->whenLoaded('mantenimiento', function () {
            //     return [
            //         'id'          => $this->mantenimiento->id_mantenimiento,
            //         'descripcion' => $this->mantenimiento->descripcion ?? null,
            //         'estado'      => $this->mantenimiento->estado ?? null,
            //     ];
            // }),
        ];
    }
}
