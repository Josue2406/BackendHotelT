<?php

namespace App\Services\house_keeping;

use App\Models\house_keeping\Limpieza;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RegistroAutomaticoDeLimpiezaService
{
    public function crearDesdeNuevaHabitacion(int $idHabitacion): Limpieza
    {
        // Verifica si ya existe una limpieza para esta habitación
        $yaExiste = Limpieza::where('id_habitacion', $idHabitacion)->exists();

        if ($yaExiste) {
            throw new ModelNotFoundException("Ya existe una limpieza asociada a la habitación {$idHabitacion}");
        }

        return Limpieza::create([
            'id_habitacion' => $idHabitacion,
            // los demás campos quedarán en null por defecto
        ]);
    }
}
