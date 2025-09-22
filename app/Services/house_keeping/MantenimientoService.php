<?php

namespace App\Services\house_keeping;

use App\Models\house_keeping\Mantenimiento;
use App\Models\house_keeping\HistorialMantenimiento;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MantenimientoService
{
    public function registrarCreacion(Mantenimiento $mantenimiento): void
    {
        $this->registrarHistorial(
            $mantenimiento->id_mantenimiento,
            'creación',
            null,
            json_encode($mantenimiento->toArray())
        );
    }

    public function registrarActualizacion(Mantenimiento $mantenimiento, array $nuevosDatos): void
    {
        $valoresAnteriores = $mantenimiento->only(array_keys($nuevosDatos));

        $this->registrarHistorial(
            $mantenimiento->id_mantenimiento,
            'actualización',
            json_encode($valoresAnteriores),
            json_encode($nuevosDatos)
        );
    }

    private function registrarHistorial(
        int $idMantenimiento,
        string $evento,
        ?string $valorAnterior,
        ?string $valorNuevo
    ): void {
        HistorialMantenimiento::create([
            'id_mantenimiento' => $idMantenimiento,
            'actor_id'         => Auth::id(),
            'evento'           => $evento,
            'fecha'            => Carbon::now(),
            'valor_anterior'   => $valorAnterior,
            'valor_nuevo'      => $valorNuevo,
        ]);
    }
}
