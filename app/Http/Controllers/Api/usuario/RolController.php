<?php

namespace App\Http\Controllers\Api\usuario;

use App\Http\Controllers\Controller;
use App\Http\Requests\usuario\StoreRolRequest;
use App\Http\Requests\usuario\UpdateRolRequest;
use App\Models\usuario\Rol;
use Illuminate\Database\QueryException;

class RolController extends Controller
{
    public function index()
    {
        return Rol::orderBy('id_rol','desc')->paginate(20);
    }

    public function store(StoreRolRequest $request)
    {
        $rol = Rol::create($request->validated());
        return response()->json($rol, 201);
    }

    public function show(Rol $role) // si tu binding es Rol $rol, usa $rol
    {
        return $role;
    }

    public function update(UpdateRolRequest $request, Rol $role)
    {
        $role->update($request->validated());
        return $role->fresh();
    }

    public function destroy(Rol $role)
    {
        try {
            $role->delete(); // FK RESTRICT si hay usuarios con ese rol
            return response()->noContent();
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'No se puede eliminar: existen usuarios con este rol.'
            ], 409);
        }
    }
}
