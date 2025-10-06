<?php
namespace App\Http\Controllers\Api\Clientes;

use App\Http\Controllers\Controller;
use App\Models\cliente\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class HuespedController extends Controller
{

    // app/Http/Controllers/Api/Huespedes/HuespedController.php

public function store(Request $r)
{
    // El front pega aquí lo del draft (o lo que el usuario edite)
    $data = $r->validate([
        'email'        => ['nullable','email','max:150'], // se coloca si vino en el login
        'nombre'       => ['required','string','max:100'],
        'apellido1'    => ['required','string','max:100'],
        'apellido2'    => ['nullable','string','max:100'],
        'telefono'     => ['nullable','string','max:30'],
        'nacionalidad' => ['nullable','string','max:3'],
        'direccion'    => ['nullable','string','max:255'],
        'numero_doc'   => ['nullable','string','max:50'],
        // agrega campos que tu tabla huespedes realmente tenga
    ]);

    // NO tocar clientes. Solo crear huésped.
    $huesped = \App\Models\huesped\Huesped::create($data);

    return response()->json([
        'message' => 'Huésped creado',
        'huesped' => $huesped,
    ], \Symfony\Component\HttpFoundation\Response::HTTP_CREATED);
}

}