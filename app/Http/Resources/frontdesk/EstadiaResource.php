<?php

namespace App\Http\Resources\frontdesk;

use Illuminate\Http\Resources\Json\JsonResource;

class EstadiaResource extends JsonResource
{
    public function toArray($request): array
    {
        // Relaciones (si fueron cargadas con ->with([...]))
        $cliente      = $this->getRelationValue('id_cliente_titular');
        $fuente       = $this->getRelationValue('id_fuente');
        $reserva      = $this->getRelationValue('id_reserva');
        $estadoCat    = $this->getRelationValue('id_estado_estadia');
        $asignaciones = $this->getRelationValue('asignacion_habitacions_where_id_estadium');
        $folios       = $this->getRelationValue('folios_where_id_estadium');

        // Estado lógico
        $estado = $estadoCat->nombre
            ?? (now()->toDateString() < ($this->fecha_llegada?->toDateString() ?? '') ? 'programada'
               : (now()->toDateString() > ($this->fecha_salida?->toDateString() ?? '') ? 'finalizada' : 'en_curso'));

        return [
            'id'             => $this->id_estadia,
            'estado'         => $estado,
            'fecha_llegada'  => optional($this->fecha_llegada)->toDateTimeString(),
            'fecha_salida'   => optional($this->fecha_salida)->toDateString(),
            'adultos'        => (int) $this->adultos,
            'ninos'          => (int) ($this->ninos ?? 0),
            'bebes'          => (int) ($this->bebes ?? 0),

            // Cliente titular
            'cliente' => [
                'id'           => $cliente->id_cliente ?? null,
                'nombre'       => $cliente ? trim($cliente->nombre.' '.$cliente->apellido1.' '.($cliente->apellido2 ?? '')) : null,
                'email'        => $cliente->email ?? null,
                'telefono'     => $cliente->telefono ?? null,
                'nacionalidad' => $cliente->nacionalidad ?? null,
            ],

            // Fuente
            'fuente' => [
                'id'     => $fuente->id_fuente ?? null,
                'nombre' => $fuente->nombre   ?? null,
                'codigo' => $fuente->codigo   ?? null,
            ],

            // Reserva (si está cargada)
            'reserva' => $reserva ? [
                'id'     => $reserva->id_reserva ?? null,
                'estado' => optional($reserva->estado)->nombre ?? null,
                'fuente' => optional($reserva->fuente)->nombre ?? null,
            ] : null,

            // Asignaciones (usando un resource alineado a Reliese; ver abajo)
            'asignaciones' => $asignaciones
                ? AsignacionRelieseResource::collection($asignaciones)
                : [],

            // Folio (si lo cargas; tu modelo tiene hasMany)
            'folios' => $folios ? $folios->map(function ($f) {
                return [
                    'id'     => $f->id_folio ?? null,
                    'estado' => optional($f->estado)->nombre ?? null,
                    'total'  => isset($f->total) ? (float) $f->total : null,
                ];
            }) : [],

            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
