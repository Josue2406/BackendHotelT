<?php

namespace App\Http\Controllers\Api\folio;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\check_out\Folio;

class FolioEstadoController extends Controller
{
    public function show(int $idFolio)
    {
        // 1️⃣ Verificar existencia del folio
        $folio = Folio::with('estadoFolio')->find($idFolio);
        if (!$folio) {
            return response()->json(['message' => 'Folio no encontrado'], 404);
        }

        // 2️⃣ Obtener estado actual
        $estado = $folio->estadoFolio ? [
            'id'          => $folio->estadoFolio->id_estado_folio,
            'nombre'      => $folio->estadoFolio->nombre,
            'descripcion' => $folio->estadoFolio->descripcion ?? $this->descripcionEstado($folio->estadoFolio->nombre),
        ] : [
            'id'          => null,
            'nombre'      => 'DESCONOCIDO',
            'descripcion' => 'El folio no tiene estado asociado.'
        ];

        // 3️⃣ Buscar última operación de cierre (si existe)
        $ultimoCierre = DB::table('folio_operacion')
            ->where('id_folio', $idFolio)
            ->where('tipo', 'cierre')
            ->latest('created_at')
            ->first();

        // 4️⃣ Titular (si fue cerrado y registrado en payload)
        $titular = null;
        if ($ultimoCierre && $ultimoCierre->payload) {
            $payload = json_decode($ultimoCierre->payload, true);
            if (isset($payload['titular'])) {
                $titular = DB::table('clientes')
                    ->select('id_cliente', 'nombre', 'apellido1', 'apellido2')
                    ->where('id_cliente', $payload['titular'])
                    ->first();
            }
        }

        // 5️⃣ Respuesta estructurada
        return response()->json([
            'id_folio'        => $folio->id_folio,
            'estado'          => $estado,
            'cerrado'         => strtoupper($estado['nombre']) === 'CERRADO',
            'fecha_actualizacion' => $folio->updated_at ? $folio->updated_at->format('Y-m-d H:i:s') : null,
            'fecha_cierre'    => $ultimoCierre ? $ultimoCierre->created_at : null,
            'titular'         => $titular,
        ]);
    }

    /**
     * Devuelve una descripción por defecto según el nombre del estado.
     */
    private function descripcionEstado(string $nombre): string
    {
        return match (strtoupper($nombre)) {
            'ABIERTO'  => 'Folio activo con operaciones en curso.',
            'CERRADO'  => 'Folio cerrado. No se permiten más movimientos.',
            'BLOQUEADO' => 'Folio bloqueado temporalmente por revisión.',
            default    => 'Estado no clasificado.',
        };
    }
}
