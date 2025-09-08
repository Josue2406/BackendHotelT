<?php

namespace App\Http\Resources\house_keeping;

use Illuminate\Http\Resources\Json\JsonResource;

class LimpiezaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id_limpieza,
            'nombre'         => $this->nombre,
            'descripcion'    => $this->descripcion,
            'notas'          => $this->notas,
            'prioridad'      => $this->prioridad,
            'estado'         => $this->fecha_final ? 'finalizada' : 'pendiente',

            'fecha_inicio'   => optional($this->fecha_inicio)->toDateTimeString(),
            'fecha_final'    => optional($this->fecha_final)->toDateTimeString(),
            'fecha_reporte'  => optional($this->fecha_reporte)->toDateTimeString(),

            // IDs simples (evitamos confusión con relación que se llama igual que la columna)
            'habitacion_id'        => $this->id_habitacion,
            'usuario_asigna_id'    => $this->id_usuario_asigna,
            'usuario_reporta_id'   => $this->id_usuario_reporta,

            'created_at'     => optional($this->created_at)->toDateTimeString(),
            'updated_at'     => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
