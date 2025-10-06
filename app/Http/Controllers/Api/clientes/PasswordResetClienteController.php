<?php

// app/Http/Controllers/Api/Clientes/PasswordResetClienteController.php
namespace App\Http\Controllers\Api\Clientes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class PasswordResetClienteController extends Controller
{
    // 1) Enviar link de reset al email
    public function sendResetLink(Request $r)
    {
        $r->validate(['email' => 'required|email']);

        $status = Password::broker('clientes')->sendResetLink(
            $r->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => __($status)])
            : response()->json(['message' => __($status)], 422);
    }

    // 2) Resetear password usando token (desde el link del correo)
    public function resetPassword(Request $r)
    {
        $r->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::broker('clientes')->reset(
            $r->only('email','password','password_confirmation','token'),
            function ($cliente, $password) {
                $cliente->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                // Opcional: invalidar todos los tokens previos
                $cliente->tokens()->delete();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => __($status)], Response::HTTP_OK)
            : response()->json(['message' => __($status)], 422);
    }
}
