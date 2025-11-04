<?php

namespace App\Http\Controllers\Api\frontdesk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\estadia\Estadia;
use Illuminate\Support\Facades\DB;

class EstadiaController extends Controller
{
    public function show(int $id)
    {
        // Buscar la estadía junto con sus relaciones
        $estadia = Estadia::with([
            'estado',
            'reserva:id_reserva,codigo_reserva,id_cliente,id_fuente,id_estado_res',
            'clientes:id_cliente,nombre,apellido1,apellido2,email',
        ])->find($id);

        if (!$estadia) {
            return response()->json(['message' => 'Estadía no encontrada'], 404);
        }

        // Buscar folio asociado
        $folio = DB::table('folio')
            ->select('id_folio', 'id_estado_folio', 'total')
            ->where('id_estadia', $estadia->id_estadia)
            ->first();

        if ($folio) {
            $folio->estado_folio = DB::table('estado_folio')
                ->where('id_estado_folio', $folio->id_estado_folio)
                ->value('nombre');
        }

        // Acompañantes (excluye al titular)
        $acompanantes = DB::table('estadia_clientes as ec')
            ->join('clientes as c', 'c.id_cliente', '=', 'ec.id_cliente')
            ->where('ec.id_estadia', $estadia->id_estadia)
            ->where('ec.rol', '!=', 'TITULAR')
            ->select('c.id_cliente', 'c.nombre', 'c.apellido1', 'c.email', 'ec.rol')
            ->get();

        // Titular
        $titular = DB::table('clientes')
            ->where('id_cliente', $estadia->id_cliente_titular)
            ->select('id_cliente', 'nombre', 'apellido1', 'email')
            ->first();

        return response()->json([
            'estadia' => $estadia,
            'titular' => $titular,
            'acompanantes' => $acompanantes,
            'folio' => $folio,
        ]);
    }
}
