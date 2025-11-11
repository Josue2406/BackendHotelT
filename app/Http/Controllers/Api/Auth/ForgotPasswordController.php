<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetCode;
use App\Models\usuario\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    public function sendCode(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Correo no registrado'], 404);
        }

        // Generar código de 6 dígitos
        $code = rand(100000, 999999);

        PasswordResetCode::updateOrCreate(
            ['email' => $request->email],
            ['code' => $code, 'expires_at' => now()->addMinutes(10)]
        );

        // Enviar correo
        Mail::send('mail.forgotPass.reset-code', ['code' => $code, 'user' => $user], function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Código para restablecer tu contraseña');
        });

        return response()->json(['message' => 'Código enviado al correo']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string',
            'password' => 'required|min:6|confirmed'
        ]);

        $reset = PasswordResetCode::where('email', $request->email)
                                  ->where('code', $request->code)
                                  ->first();

        if (!$reset) {
            return response()->json(['message' => 'Código inválido'], 400);
        }

        if ($reset->isExpired()) {
            return response()->json(['message' => 'El código ha expirado'], 400);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $user->update(['password' => Hash::make($request->password)]);

        // Borrar el código usado
        $reset->delete();

        return response()->json(['message' => 'Contraseña restablecida correctamente']);
    }
}
