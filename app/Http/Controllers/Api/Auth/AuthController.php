<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\UserResource;
use App\Models\usuario\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Http\Requests\Auth\RegisterRequest;
class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $user = User::where('email', strtolower($data['email']))->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => new UserResource($user),
        ]);
    }

    public function me(Request $request)
    {
        return new UserResource($request->user()->load('rol'));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['message' => 'Logout ok']);
    }
    public function register(RegisterRequest $request)
{
    $data = $request->validated();

    // Asegúrate de hashear la contraseña
    $data['password'] = bcrypt($data['password']);

    $user = User::create($data);

    $token = $user->createToken('api')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user'  => new UserResource($user->load('rol')),
    ]);
}
}
