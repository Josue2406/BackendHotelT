<?php

namespace App\Services\house_keeping;

use App\Models\house_keeping\Mantenimiento;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RegistroAutomaticoDeMantenimientoService
{
    /**
     * Crea un mantenimiento en blanco para una nueva habitación.
     *
     * @param int $idHabitacion
     * @return Mantenimiento
     *
     * @throws ModelNotFoundException si ya existe un mantenimiento para esa habitación
     */
    public function crearDesdeNuevaHabitacion(int $idHabitacion): Mantenimiento
    {
        // Verifica si ya existe uno
        $yaExiste = Mantenimiento::where('id_habitacion', $idHabitacion)->exists();

        if ($yaExiste) {
            throw new ModelNotFoundException("Ya existe un mantenimiento para la habitación {$idHabitacion}");
        }

        return Mantenimiento::create([
            'id_habitacion' => $idHabitacion,
            // todos los demás campos nulos por defecto
        ]);
    }
}
