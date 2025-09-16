<?php

namespace App\Http\Controllers\Api\frontdesk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\estadia\EstadoEstadia;

class EstadoEstadiaController extends Controller
{
    /** GET /frontdesk/estado-estadia */
    public function index()
    {
        return EstadoEstadia::orderBy('id_estado_estadia')->get();
    }

    /** POST /frontdesk/estado-estadia */
    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo' => 'required|string|max:50|unique:estado_estadia,codigo',
            'nombre' => 'required|string|max:100',
        ]);

        $estado = EstadoEstadia::create($data);

        return response()->json($estado, 201);
    }
}
