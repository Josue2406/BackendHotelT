<?php

namespace App\Http\Resources\house_keeping;

use Illuminate\Http\Resources\Json\JsonResource;

class LimpiezaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id_limpieza,
            'notas'          => $this->notas,
            'prioridad'      => $this->prioridad,

            'fecha_inicio'   => optional($this->fecha_inicio)->toDateTimeString(),
            'fecha_final'    => optional($this->fecha_final)->toDateTimeString(),
            'fecha_reporte'  => optional($this->fecha_reporte)->toDateTimeString(),

            // 🔁 Habitacion (con tipo anidado)
            'habitacion' => $this->whenLoaded('habitacion', function () {
                return [
                    'id'     => $this->habitacion->id_habitacion,
                    'numero' => $this->habitacion->numero,
                    'piso'   => $this->habitacion->piso,
                    'tipo'   => $this->when(
                        $this->habitacion->relationLoaded('tipo') && $this->habitacion->tipo,
                        function () {
                            return [
                                'id_tipo_hab' => $this->habitacion->tipo->id_tipo_hab,
                                'nombre'      => $this->habitacion->tipo->nombre,
                                'descripcion' => $this->habitacion->tipo->descripcion,
                                'created_at'  => optional($this->habitacion->tipo->created_at)?->toDateTimeString(),
                                'updated_at'  => optional($this->habitacion->tipo->updated_at)?->toDateTimeString(),
                            ];
                        }
                    ),
                ];
            }),

            // ✅ Usuario asignado (nombre y teléfono)
            'usuario_asignado' => $this->whenLoaded('asignador', function () {
                return [
                    'id'       => $this->asignador->id_usuario,
                    'nombre'   => $this->asignador->nombre,
                    'telefono' => $this->asignador->telefono,
                ];
            }),
            'usuario_reporta' => $this->whenLoaded('reportante', function () {
                return [
                    'id'       => $this->reportante->id_usuario,
                    'nombre'   => $this->reportante->nombre,
                    'telefono' => $this->reportante->telefono,
                ];
            }),

            // ✅ Usuario que reportó
            //'usuario_reporta_id' => $this->id_usuario_reporta,

            // ✅ Estado de la habitación
            'estado' => $this->whenLoaded('estadoHabitacion', function () {
                return [
                    'id'          => $this->estadoHabitacion->id_estado_hab ?? $this->estadoHabitacion->id,
                    'nombre'      => $this->estadoHabitacion->nombre,
                    'tipo'        => $this->estadoHabitacion->tipo,
                    'descripcion' => $this->estadoHabitacion->descripcion,
                ];
            }),

            // ✅ Timestamps del registro
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
