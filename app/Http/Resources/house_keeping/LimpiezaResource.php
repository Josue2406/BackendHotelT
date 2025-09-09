<?php

namespace App\Http\Resources\house_keeping;

use Illuminate\Http\Resources\Json\JsonResource;

class LimpiezaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id_limpieza,
            'nombre'            => $this->nombre,
            'descripcion'       => $this->descripcion,
            'notas'             => $this->notas,
            'prioridad'         => $this->prioridad,

            'fecha_inicio'      => optional($this->fecha_inicio)->toDateTimeString(),
            'fecha_final'       => optional($this->fecha_final)->toDateTimeString(),
            'fecha_reporte'     => optional($this->fecha_reporte)->toDateTimeString(),

            'habitacion_id'     => $this->id_habitacion,
            'usuario_asigna_id' => $this->id_usuario_asigna,
            'usuario_reporta_id'=> $this->id_usuario_reporta,

            // 👉 Solo ID de estado (el frontend decide qué mostrar)
            'estado_id'         => $this->id_estado_hab,

            // Opcional: nombre del estado si se cargó la relación
            //'estado_nombre'     => $this->whenLoaded('estadoHabitacion', fn () => $this->estadoHabitacion->nombre),

            'created_at'        => optional($this->created_at)->toDateTimeString(),
            'updated_at'        => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
