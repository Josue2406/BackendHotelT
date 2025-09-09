<?php

namespace App\Http\Controllers\Api\usuario;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUsuarioRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;

class UsuarioController extends Controller
{
    public function index()
    {
        return User::with('rol')->orderBy('id_usuario','desc')->paginate(20);
    }

    public function store(StoreUsuarioRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        return response()->json($user->load('rol'), 201);
    }

    public function show(User $usuario)
    {
        return $usuario->load('rol');
    }

    public function update(UpdateUsuarioRequest $request, User $usuario)
    {
        $data = $request->validated();
        if (array_key_exists('password', $data)) {
            $data['password'] = $data['password'] ? Hash::make($data['password']) : $usuario->password;
        }
        $usuario->update($data);
        return $usuario->load('rol')->fresh();
    }

    public function destroy(User $usuario)
    {
        try {
            $usuario->delete(); // puede fallar si hay FKs RESTRICT (p.ej. pagos creados)
            return response()->noContent();
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'No se puede eliminar: el usuario tiene uso histórico (pagos, historial, etc.).'
            ], 409);
        }
    }
}
