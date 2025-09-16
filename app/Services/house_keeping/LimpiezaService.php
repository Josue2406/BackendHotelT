<?php
namespace App\Services\house_keeping;
use App\Models\house_keeping\HistorialLimpieza;
use App\Models\house_keeping\Limpieza;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LimpiezaService
{
    public function crearLimpieza(array $data)
    {
        $limpieza = Limpieza::create($data);

        // Registrar historial
        $this->registrarHistorial(
            $limpieza->id_limpieza,
            'creación',
            null,
            json_encode($limpieza->toArray())
        );

        return $limpieza;
    }

    public function actualizarLimpieza(Limpieza $limpieza, array $data)
    {
        $valoresAnteriores = $limpieza->only(array_keys($data));
        $limpieza->update($data);

        // Registrar historial
        $this->registrarHistorial(
            $limpieza->id_limpieza,
            'actualización',
            json_encode($valoresAnteriores),
            json_encode($data)
        );

        return $limpieza;
    }

    private function registrarHistorial(int $idLimpieza, string $evento, ?string $valorAnterior, ?string $valorNuevo)
    {
        HistorialLimpieza::create([
            'id_limpieza'    => $idLimpieza,
            'actor_id'       => Auth::check() ? Auth::id() : null,
            'evento'         => $evento,
            'fecha'          => Carbon::now(),
            'valor_anterior' => $valorAnterior,
            'valor_nuevo'    => $valorNuevo,
        ]);
    }
}