# 🌐 Sistema de Reservas: Web vs Recepción

Este documento explica cómo funciona el sistema de creación de reservas en dos modalidades diferentes: **reservas web (autenticadas)** y **reservas desde recepción (sin autenticación)**.

---

## ⚙️ CONFIGURACIÓN DE RUTAS

### Estado Actual (Implementado)

```php
// routes/api.php

// POST (store) sin autenticación obligatoria para permitir reservas desde recepción
Route::post('reservas', [ReservaController::class, 'store']); // Web (con token) o Recepción (sin token)

// El resto de operaciones CRUD requieren autenticación
Route::middleware('auth:sanctum')->group(function () {
    Route::get('reservas', [ReservaController::class, 'index']);
    Route::get('reservas/{reserva}', [ReservaController::class, 'show']);
    Route::put('reservas/{reserva}', [ReservaController::class, 'update']);
    Route::patch('reservas/{reserva}', [ReservaController::class, 'update']);
    Route::delete('reservas/{reserva}', [ReservaController::class, 'destroy']);
});
```

### Características
- ✅ **POST /api/reservas** NO requiere autenticación obligatoria
- ✅ Detecta automáticamente si viene con token (web) o sin token (recepción)
- ✅ GET, PUT, PATCH, DELETE SÍ requieren autenticación
- ✅ La lógica del controlador maneja ambos casos de forma segura

---

## 📊 COMPARACIÓN DE MODALIDADES

| Característica | Web (Cliente Autenticado) | Recepción (Staff) |
|---------------|--------------------------|-------------------|
| **Autenticación** | ✅ Requiere token Sanctum | ❌ No requiere autenticación |
| **Origen** | Cliente desde app/web | Personal del hotel |
| **id_cliente** | Se toma del token automáticamente | Se debe enviar en el request |
| **Seguridad** | Cliente solo puede crear sus propias reservas | Staff puede crear para cualquier cliente |
| **Header** | `Authorization: Bearer {token}` | Sin header de autorización |
| **Validación** | id_cliente opcional (se ignora si se envía) | id_cliente **obligatorio** |

---

## 🌐 MODALIDAD 1: RESERVA WEB (Cliente Autenticado)

### Caso de Uso
Cliente registrado que crea su propia reserva desde la aplicación web o móvil.

### Autenticación
```http
POST /api/reservas
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Content-Type: application/json
```

### Request Body

```json
{
  "id_estado_res": 1,
  "id_fuente": 1,
  "notas": "Reserva para aniversario",
  "habitaciones": [
    {
      "id_habitacion": 101,
      "fecha_llegada": "2025-11-20",
      "fecha_salida": "2025-11-23",
      "adultos": 2,
      "ninos": 0,
      "bebes": 0
    }
  ]
}
```

**NOTA:**
- ✅ **NO se requiere** enviar `id_cliente` (se ignora si se envía)
- ✅ El sistema toma el `id_cliente` del token automáticamente
- ✅ Seguridad: El cliente solo puede crear reservas para sí mismo

### Flujo Interno

```
1. Request llega con token Bearer
   ↓
2. Laravel Sanctum valida el token
   ↓
3. $request->user() devuelve el cliente autenticado
   ↓
4. Sistema extrae id_cliente del token
   ↓
5. Se crea la reserva para ese cliente
   ↓
6. Log: "Reserva creada desde WEB (cliente autenticado)"
```

### Código Relevante

**StoreReservaRequest.php:**
```php
'id_cliente' => $this->user() ? 'nullable|integer|exists:clientes,id_cliente' : 'required|integer|exists:clientes,id_cliente'
```
- Si hay token (`$this->user()` existe) → `id_cliente` es **opcional**
- El valor enviado se ignora por seguridad

**ReservaController.php:**
```php
$clienteAutenticado = $r->user();

if ($clienteAutenticado) {
    // CASO WEB: Hay token autenticado
    // Siempre usar el cliente del token (seguridad)
    $data['id_cliente'] = $clienteAutenticado->id_cliente;

    Log::info('Reserva creada desde WEB (cliente autenticado)', [
        'id_cliente' => $data['id_cliente'],
        'email' => $clienteAutenticado->email ?? null
    ]);
}
```

### Respuesta Exitosa

```json
{
  "id_reserva": 125,
  "codigo_reserva": "ABC8DEFG",
  "codigo_formateado": "ABC8-DEFG",
  "id_cliente": 45,
  "cliente": {
    "id_cliente": 45,
    "nombre": "Juan",
    "apellido1": "Pérez",
    "email": "juan.perez@email.com"
  },
  "id_estado_res": 1,
  "estado": {
    "id_estado_res": 1,
    "nombre": "Pendiente"
  },
  "total_monto_reserva": 450.00,
  "monto_pagado": 0.00,
  "monto_pendiente": 450.00,
  "pago_completo": false,
  "fecha_creacion": "2025-10-15T14:30:00.000000Z",
  "habitaciones": [
    {
      "id_reserva_hab": 178,
      "id_habitacion": 101,
      "fecha_llegada": "2025-11-20",
      "fecha_salida": "2025-11-23",
      "adultos": 2,
      "ninos": 0,
      "bebes": 0,
      "subtotal": 450.00,
      "habitacion": {
        "id_habitacion": 101,
        "nombre": "Suite Deluxe",
        "numero": "101",
        "capacidad": 4
      }
    }
  ]
}
```

---

## 🏢 MODALIDAD 2: RESERVA DESDE RECEPCIÓN (Sin Autenticación)

### Caso de Uso
Personal del hotel (recepcionista) crea una reserva para un cliente existente.

### Autenticación
```http
POST /api/reservas
Content-Type: application/json
```

**NOTA:** Sin header `Authorization` (no hay token)

### Request Body

```json
{
  "id_cliente": 45,
  "id_estado_res": 1,
  "id_fuente": 2,
  "notas": "Reserva creada en recepción - Cliente walk-in",
  "habitaciones": [
    {
      "id_habitacion": 102,
      "fecha_llegada": "2025-11-25",
      "fecha_salida": "2025-11-28",
      "adultos": 2,
      "ninos": 1,
      "bebes": 0
    }
  ]
}
```

**NOTA:**
- ✅ **id_cliente ES OBLIGATORIO**
- ✅ Debe ser un cliente existente en la base de datos
- ✅ El staff puede crear reservas para cualquier cliente

### Flujo Interno

```
1. Request llega SIN token Bearer
   ↓
2. $request->user() devuelve null
   ↓
3. Sistema verifica que id_cliente esté presente en el body
   ↓
4. Valida que id_cliente exista en tabla clientes
   ↓
5. Se crea la reserva para ese cliente
   ↓
6. Log: "Reserva creada desde RECEPCIÓN (sin autenticación)"
```

### Código Relevante

**StoreReservaRequest.php:**
```php
'id_cliente' => $this->user() ? 'nullable|integer|exists:clientes,id_cliente' : 'required|integer|exists:clientes,id_cliente'
```
- Si NO hay token (`$this->user()` es null) → `id_cliente` es **obligatorio**
- Se valida que exista en la tabla `clientes`

**ReservaController.php:**
```php
} else {
    // CASO RECEPCIÓN: No hay token
    // Usar id_cliente del request (ya validado que existe)
    if (!isset($data['id_cliente'])) {
        return response()->json([
            'success' => false,
            'message' => 'Se requiere id_cliente cuando no hay autenticación'
        ], 400);
    }

    Log::info('Reserva creada desde RECEPCIÓN (sin autenticación)', [
        'id_cliente' => $data['id_cliente']
    ]);
}
```

### Respuesta Exitosa

```json
{
  "id_reserva": 126,
  "codigo_reserva": "XYZ9KLMN",
  "codigo_formateado": "XYZ9-KLMN",
  "id_cliente": 45,
  "cliente": {
    "id_cliente": 45,
    "nombre": "María",
    "apellido1": "González",
    "email": "maria.gonzalez@email.com"
  },
  "id_estado_res": 1,
  "estado": {
    "id_estado_res": 1,
    "nombre": "Pendiente"
  },
  "total_monto_reserva": 600.00,
  "monto_pagado": 0.00,
  "monto_pendiente": 600.00,
  "pago_completo": false,
  "fecha_creacion": "2025-10-15T15:00:00.000000Z",
  "habitaciones": [...]
}
```

---

## 🔐 SEGURIDAD

### Protección en Modo Web

**Problema:** ¿Qué pasa si un cliente malicioso envía un `id_cliente` diferente al suyo en el token?

```json
{
  "id_cliente": 999,
  "habitaciones": [...]
}
```

**Solución:** El sistema **SIEMPRE ignora** el `id_cliente` del body cuando hay token:

```php
if ($clienteAutenticado) {
    // Siempre usar el cliente del token (seguridad)
    $data['id_cliente'] = $clienteAutenticado->id_cliente; // Se sobrescribe
}
```

✅ **Resultado:** El cliente solo puede crear reservas para sí mismo.

### Protección en Modo Recepción

**Problema:** ¿Cualquiera puede crear reservas sin autenticación?

**Respuesta:** Depende de tu configuración de rutas:

**Opción 1: Ruta pública** (actual)
```php
// routes/api.php
Route::apiResource('reservas', ReservaController::class);
```
❌ Cualquiera puede acceder

**Opción 2: Middleware para staff** (recomendado)
```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('reservas', ReservaController::class);
});
```
✅ Solo usuarios autenticados (clientes o staff)

**Opción 3: Separar rutas**
```php
// Para clientes web
Route::middleware('auth:sanctum')->group(function () {
    Route::post('reservas', [ReservaController::class, 'store']);
});

// Para staff de recepción (requiere rol admin)
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('admin/reservas', [ReservaController::class, 'storeFromReception']);
});
```
✅ Control granular por rol

---

## 🎯 EJEMPLOS COMPLETOS

### Ejemplo 1: Cliente Web Crea Reserva

**Contexto:** Cliente "Juan Pérez" (id: 45) está autenticado

**Request:**
```bash
curl -X POST "http://localhost:8000/api/reservas" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -H "Content-Type: application/json" \
  -d '{
    "id_estado_res": 1,
    "habitaciones": [
      {
        "id_habitacion": 101,
        "fecha_llegada": "2025-11-20",
        "fecha_salida": "2025-11-23",
        "adultos": 2,
        "ninos": 0,
        "bebes": 0
      }
    ]
  }'
```

**Resultado:**
- ✅ Reserva creada para cliente id: 45
- ✅ Código generado: "ABC8-DEFG"
- ✅ Log: "Reserva creada desde WEB (cliente autenticado)"

### Ejemplo 2: Cliente Web Intenta Crear para Otro

**Contexto:** Cliente "Juan Pérez" (id: 45) está autenticado, pero intenta crear reserva para id: 999

**Request:**
```bash
curl -X POST "http://localhost:8000/api/reservas" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -H "Content-Type: application/json" \
  -d '{
    "id_cliente": 999,
    "id_estado_res": 1,
    "habitaciones": [...]
  }'
```

**Resultado:**
- ✅ Sistema **ignora** id_cliente: 999
- ✅ Reserva creada para cliente id: 45 (del token)
- ✅ Seguridad mantenida

### Ejemplo 3: Recepción Crea Reserva

**Contexto:** Recepcionista crea reserva para cliente existente (id: 78)

**Request:**
```bash
curl -X POST "http://localhost:8000/api/reservas" \
  -H "Content-Type: application/json" \
  -d '{
    "id_cliente": 78,
    "id_estado_res": 1,
    "id_fuente": 2,
    "notas": "Cliente walk-in",
    "habitaciones": [
      {
        "id_habitacion": 102,
        "fecha_llegada": "2025-11-25",
        "fecha_salida": "2025-11-28",
        "adultos": 2,
        "ninos": 1,
        "bebes": 0
      }
    ]
  }'
```

**Resultado:**
- ✅ Reserva creada para cliente id: 78
- ✅ Código generado: "XYZ9-KLMN"
- ✅ Log: "Reserva creada desde RECEPCIÓN (sin autenticación)"

### Ejemplo 4: Recepción Olvida id_cliente

**Request:**
```bash
curl -X POST "http://localhost:8000/api/reservas" \
  -H "Content-Type: application/json" \
  -d '{
    "id_estado_res": 1,
    "habitaciones": [...]
  }'
```

**Resultado:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "id_cliente": [
      "El ID del cliente es obligatorio cuando no hay autenticación (reservas desde recepción)."
    ]
  }
}
```
❌ Error 422: Validación falla

---

## 📝 RESUMEN DE VALIDACIONES

### StoreReservaRequest

| Campo | Con Token (Web) | Sin Token (Recepción) |
|-------|-----------------|----------------------|
| `id_cliente` | Opcional (se ignora) | **Obligatorio** |
| `id_estado_res` | Obligatorio | Obligatorio |
| `id_fuente` | Opcional | Opcional |
| `notas` | Opcional | Opcional |
| `habitaciones` | Obligatorio (min: 1) | Obligatorio (min: 1) |

### Lógica del Controlador

```php
// Pseudocódigo
if (hay_token) {
    id_cliente = token.cliente.id
    log("Reserva WEB")
} else {
    if (!request.id_cliente) {
        error("Se requiere id_cliente")
    }
    id_cliente = request.id_cliente
    log("Reserva RECEPCIÓN")
}

crear_reserva(id_cliente, datos)
```

---

## 🔄 DIAGRAMA DE FLUJO

```
┌─────────────────────────────────────────────────┐
│         POST /api/reservas                      │
└────────────────┬────────────────────────────────┘
                 │
                 ▼
         ¿Hay header Authorization?
                 │
       ┌─────────┴─────────┐
       │                   │
      SÍ                  NO
       │                   │
       ▼                   ▼
┌──────────────┐    ┌──────────────┐
│  MODO WEB    │    │ MODO RECEP.  │
└──────┬───────┘    └──────┬───────┘
       │                   │
       ▼                   ▼
 Validar token      ¿Tiene id_cliente?
       │                   │
       ▼              ┌────┴────┐
 ¿Token válido?      │         │
       │            SÍ        NO
   ┌───┴───┐         │         │
  SÍ      NO         ▼         ▼
   │       │    Validar    Error 422
   │       │    existe     "id_cliente
   │       │    cliente    requerido"
   │       │        │
   │       ▼        ▼
   │   Error 401  Cliente
   │   "No auth"  encontrado
   │       │        │
   ▼       │        │
Extraer    │        │
id_cliente │        │
del token  │        │
   │       │        │
   └───┬───┴────┬───┘
       │        │
       ▼        ▼
  id_cliente  id_cliente
  = token.id  = request.id
       │        │
       └────┬───┘
            │
            ▼
   Crear reserva
   con id_cliente
            │
            ▼
   Generar código
   único (Observer)
            │
            ▼
   Crear habitaciones
            │
            ▼
   Calcular totales
            │
            ▼
   Enviar correo
   (si tiene email)
            │
            ▼
   Respuesta 201
   con reserva
```

---

## 🛡️ RECOMENDACIONES DE SEGURIDAD

### 1. Proteger Endpoint con Middleware

**Actual (público):**
```php
Route::apiResource('reservas', ReservaController::class);
```

**Recomendado (protegido):**
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('reservas', ReservaController::class);
});
```

### 2. Agregar Control de Roles

```php
// Crear middleware RoleMiddleware
// app/Http/Middleware/RoleMiddleware.php

public function handle($request, Closure $next, $role)
{
    if (!$request->user() || !$request->user()->hasRole($role)) {
        return response()->json(['message' => 'No autorizado'], 403);
    }

    return $next($request);
}
```

**Uso:**
```php
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Solo admins pueden crear reservas para otros
    Route::post('admin/reservas', [ReservaController::class, 'store']);
});

Route::middleware('auth:sanctum')->group(function () {
    // Clientes solo pueden crear sus propias reservas
    Route::post('reservas', [ReservaController::class, 'store']);
});
```

### 3. Rate Limiting

```php
Route::middleware(['throttle:10,1'])->group(function () {
    Route::post('reservas', [ReservaController::class, 'store']);
});
// Máximo 10 reservas por minuto
```

---

## 📊 LOGS Y AUDITORÍA

El sistema registra automáticamente el origen de cada reserva:

**Web:**
```
[2025-10-15 14:30:25] local.INFO: Reserva creada desde WEB (cliente autenticado) {"id_cliente":45,"email":"juan.perez@email.com"}
```

**Recepción:**
```
[2025-10-15 15:00:10] local.INFO: Reserva creada desde RECEPCIÓN (sin autenticación) {"id_cliente":78}
```

Esto permite:
- ✅ Auditar quién creó cada reserva
- ✅ Detectar patrones sospechosos
- ✅ Analizar uso por canal (web vs recepción)

---

**Fecha:** 2025-10-15
**Sistema:** Backend-SistemaHotelero
**Versión:** 1.0
