<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Restablecer contraseña</title>
</head>
<body style="font-family: Arial, sans-serif;">
    <h2>Hola {{ $user->name }},</h2>
    <p>Recibimos una solicitud para restablecer tu contraseña.</p>
    <p>Tu código de verificación es:</p>
    <h1 style="color: #2d89ef;">{{ $code }}</h1>
    <p>Este código expirará en 10 minutos.</p>
    <p>Si no solicitaste este cambio, puedes ignorar este correo.</p>
    <br>
    <p>Atentamente,<br>El equipo de soporte</p>
</body>
</html>
