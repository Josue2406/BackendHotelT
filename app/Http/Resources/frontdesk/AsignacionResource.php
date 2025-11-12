<?php

namespace App\Http\Resources\frontdesk;

use Illuminate\Http\Resources\Json\JsonResource;

class AsignacionResource extends JsonResource
{
    public function toArray($request): array
    {
        $habitacion = $this->getRelationValue('id_hab');
        $checkins   = $this->getRelationValue('check_ins_where_id_asignacion');
        $checkouts  = $this->getRelationValue('check_outs_where_id_asignacion');

        return [
            'id'               => $this->id_asignacion,
            'origen'           => $this->origen,
            'nombre'           => $this->nombre,
            'fecha_asignacion' => optional($this->fecha_asignacion)->toDateTimeString(),
            'adultos'          => (int) $this->adultos,
            'ninos'            => (int) ($this->ninos ?? 0),
            'bebes'            => (int) ($this->bebes ?? 0),

            'habitacion' => [
                'id'     => $habitacion->id_habitacion ?? null,
                'numero' => $habitacion->numero ?? null,
                'piso'   => $habitacion->piso   ?? null,
                'tipo'   => optional($habitacion->tipo)->nombre   ?? null,
                'estado' => optional($habitacion->estado)->nombre ?? null,
            ],

            'checkins'  => $checkins ? $checkins->map(fn($c) => [
                'id'         => $c->id_checkin,
                'fecha_hora' => optional($c->fecha_hora)->toDateTimeString(),
                'observacion'=> $c->obervacion,
            ]) : [],

            'checkouts' => $checkouts ? $checkouts->map(fn($c) => [
                'id'         => $c->id_checkout,
                'fecha_hora' => optional($c->fecha_hora)->toDateTimeString(),
                'resultado'  => $c->resultado,
            ]) : [],
        ];
    }
}
