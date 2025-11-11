<?php
// app/Http/Controllers/Api/Clientes/AuthClienteController.php
namespace App\Http\Controllers\Api\Clientes;

use App\Http\Controllers\Controller;
use App\Models\cliente\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class AuthClienteController extends Controller
{
    public function register(Request $r)
{
    $data = $r->validate([
        'nombre'    => ['required','string','max:100'],
        // acepta cualquiera de los dos campos
        'apellido'  => ['required_without:apellido1','string','max:100'],
        'apellido1' => ['required_without:apellido','string','max:100'],
        'email'     => ['required','email','max:150', Rule::unique('clientes','email')],
        'password'  => ['required','string','min:8','confirmed'],
        'telefono'  => ['nullable','string','max:30'],
    ], [
        'apellido.required_without'  => 'Debe enviar "apellido" o "apellido1".',
        'apellido1.required_without' => 'Debe enviar "apellido" o "apellido1".',
    ]);

    $email      = mb_strtolower(trim($data['email']));
    $apellido1  = $data['apellido1'] ?? $data['apellido'];

    $cliente = Cliente::create([
        'nombre'    => $data['nombre'],
        'apellido1' => $apellido1,                    // ← usar el mapeo
        'email'     => $email,                        // ← usar normalizado
        'password'  => Hash::make($data['password']),
        'telefono'  => $data['telefono'] ?? null,
    ]);

    $token = $cliente->createToken('cliente-token')->plainTextToken;

    return response()->json([
        'message' => 'Registro exitoso',
        'token'   => $token,
        'cliente' => $cliente,
    ], Response::HTTP_CREATED);
}

        /*
        $data = $r->validate([
            'nombre'   => ['required','string','max:100'],
            'apellido1' => ['required','string','max:100'],
            'email'    => ['required','email','max:150', Rule::unique('clientes','email')],
            'password' => ['required','string','min:8','confirmed'], // requiere password_confirmation
            'telefono' => ['nullable','string','max:30'],
        ]);

        $cliente = Cliente::create([
            'nombre'   => $data['nombre'],
            'apellido1' => $data['apellido1'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'telefono' => $data['telefono'] ?? null,
        ]);

        // crea token (puedes nombrar el token por dispositivo)
        $token = $cliente->createToken('cliente-token')->plainTextToken;

        return response()->json([
            'message' => 'Registro exitoso',
            'token'   => $token,
            'cliente' => $cliente,
        ], Response::HTTP_CREATED);
    } */

    /*
    public function login(Request $r)
    {
        $credentials = $r->validate([
            'email'    => ['required','email'],
            'password' => ['required','string'],
        ]);

          // 1) Normaliza el email para evitar problemas de espacios/mayúsculas
    $email = mb_strtolower(trim($data['email']));

        $cliente = Cliente::where('email', $credentials['email'])->first();

        if (!$cliente || !Hash::check($credentials['password'], $cliente->password)) {
            return response()->json(['message' => 'Credenciales inválidas'], 422);
        }

        // Opcional: revocar tokens previos si quieres single-session
        // $cliente->tokens()->delete();

        $token = $cliente->createToken('cliente-token')->plainTextToken;

        return response()->json([
            'message' => 'Login exitoso',
            'token'   => $token,
            'cliente' => $cliente,
        ]);
    } */
/*
public function login(Request $r)
{
    $data = $r->validate([
        'email'    => ['required','email'],
        'password' => ['required','string'],
    ]);

    // 1) Normaliza el email para evitar problemas de espacios/mayúsculas
    $email = mb_strtolower(trim($data['email']));

    // 2) Búsqueda por email (en MySQL suele ser case-insensitive, igual normalizamos)
    $cliente = Cliente::where('email', $email)->first();

    // 3) Mensaje único (no reveles si el email existe)
    if (!$cliente || !Hash::check($data['password'], $cliente->password)) {
        // Opcional: registrar intento fallido/auditar aquí
        return response()->json(['message' => 'Credenciales inválidas'], 422);
    }

    // 4) Single-session opcional: revoca tokens previos
    // $cliente->tokens()->delete();

    // 5) (Opcional) asigna abilities para RBAC futuro
    // $token = $cliente->createToken('cliente-token', ['cliente:basic'])->plainTextToken;
    $token = $cliente->createToken('cliente-token')->plainTextToken;

    return response()->json([
        'message' => 'Login exitoso',
        'token'   => $token,
        'cliente' => $cliente,
    ]);
}
 */
// app/Http/Controllers/Api/Clientes/AuthClienteController.php
public function login(Request $r)
{
    $data = $r->validate([
        'email'    => ['required','email'],
        'password' => ['required','string'],

        // opcionales SOLO para “prefill” del huésped
        'nombre'      => ['nullable','string','max:100'],
        'apellido1'   => ['nullable','string','max:100'],
        'apellido2'   => ['nullable','string','max:100'],
        'telefono'    => ['nullable','string','max:30'],
        'nacionalidad'=> ['nullable','string','max:3'],
        'direccion'   => ['nullable','string','max:255'],
    ]);

    $email = mb_strtolower(trim($data['email']));

    $cliente = \App\Models\cliente\Cliente::where('email', $email)->first();

    if (!$cliente || !\Illuminate\Support\Facades\Hash::check($data['password'], $cliente->password)) {
        return response()->json(['message' => 'Credenciales inválidas'], 422);
    }

    // NO actualizar/crear nada. Login independiente.
    $token = $cliente->createToken('cliente-token')->plainTextToken;

    // “draft_profile” = lo que vino en el login para prellenar el huésped (no persistido)
    $draft = [
        'email'        => $email, // importante para el huésped
        'nombre'       => $data['nombre']      ?? null,
        'apellido1'    => $data['apellido1']   ?? null,
        'apellido2'    => $data['apellido2']   ?? null,
        'telefono'     => $data['telefono']    ?? null,
        'nacionalidad' => $data['nacionalidad']?? null,
        'direccion'    => $data['direccion']   ?? null,
    ];

    return response()->json([
        'message'       => 'Login exitoso',
        'token'         => $token,
        'cliente'       => $cliente,   // por si el front quiere mostrar el perfil real
        'draft_profile' => array_filter($draft, fn($v)=>!is_null($v)), // solo los que vinieron
    ]);
}


    public function me(Request $r)
    {
        return response()->json($r->user()); // auth:sanctum
    }

    public function logout(Request $r)
    {
        $r->user()->currentAccessToken()->delete(); // revoca token actual
        return response()->json(['message' => 'Sesión cerrada']);
    }
}
