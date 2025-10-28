# Ejemplos de Prueba: Creación de Reservas (Dual Mode)

## Descripción

El endpoint de creación de reservas funciona en **DOS MODOS**:

1. **Modo Web** (con token): Cliente autenticado crea su propia reserva
2. **Modo Recepción** (sin token): Recepcionista crea reserva para un cliente

---

## 1. Modo Web (Cliente Autenticado)

### Endpoint
```
POST /api/reservas
```

### Headers
```
Authorization: Bearer {token_del_cliente}
Content-Type: application/json
```

### Request Body
```json
{
  "id_estado_res": 1,
  "id_fuente": 1,
  "notas": "Reserva desde la web",
  "habitaciones": [
    {
      "id_habitacion": 1,
      "fecha_llegada": "2026-05-01",
      "fecha_salida": "2026-05-05",
      "adultos": 2,
      "ninos": 1,
      "bebes": 0
    }
  ]
}
```

### Notas Importantes
- ✅ **NO** se requiere `id_cliente` en el body (se toma del token automáticamente)
- ✅ Si se envía `id_cliente` en el body, será **ignorado** (por seguridad)
- ✅ El sistema siempre usará el `id_cliente` del token autenticado
- ✅ Se registra en logs como "Reserva creada desde WEB"

### Response Exitoso
```json
{
  "success": true,
  "message": "Reserva creada exitosamente",
  "data": {
    "id_reserva": 50,
    "codigo_reserva": "RES-000050",
    "id_cliente": 28,
    "id_estado_res": 1,
    "estado_nombre": "Pendiente",
    "total_monto_reserva": 500.00,
    "monto_pagado": 0.00,
    "monto_pendiente": 500.00,
    "habitaciones": [
      {
        "id_reserva_habitacion": 75,
        "id_habitacion": 1,
        "numero_habitacion": "101",
        "tipo_habitacion": "Doble",
        "fecha_llegada": "2026-05-01",
        "fecha_salida": "2026-05-05",
        "noches": 4,
        "adultos": 2,
        "ninos": 1,
        "bebes": 0,
        "subtotal": 500.00
      }
    ]
  }
}
```

---

## 2. Modo Recepción (Sin Token)

### Endpoint
```
POST /api/reservas
```

### Headers
```
Content-Type: application/json
```

**NOTA**: NO se envía header `Authorization`

### Request Body
```json
{
  "id_cliente": 28,
  "id_estado_res": 1,
  "id_fuente": 2,
  "notas": "Reserva telefónica registrada por recepción",
  "habitaciones": [
    {
      "id_habitacion": 2,
      "fecha_llegada": "2026-06-15",
      "fecha_salida": "2026-06-20",
      "adultos": 2,
      "ninos": 0,
      "bebes": 0
    }
  ]
}
```

### Notas Importantes
- ✅ **REQUIERE** `id_cliente` en el body (obligatorio)
- ✅ El recepcionista puede crear reservas para cualquier cliente existente
- ✅ El `id_cliente` debe existir en la tabla `clientes`
- ✅ Se registra en logs como "Reserva creada desde RECEPCIÓN"

### Response Exitoso
```json
{
  "success": true,
  "message": "Reserva creada exitosamente",
  "data": {
    "id_reserva": 51,
    "codigo_reserva": "RES-000051",
    "id_cliente": 28,
    "id_estado_res": 1,
    "estado_nombre": "Pendiente",
    "total_monto_reserva": 750.00,
    "monto_pagado": 0.00,
    "monto_pendiente": 750.00,
    "habitaciones": [
      {
        "id_reserva_habitacion": 76,
        "id_habitacion": 2,
        "numero_habitacion": "102",
        "tipo_habitacion": "Suite",
        "fecha_llegada": "2026-06-15",
        "fecha_salida": "2026-06-20",
        "noches": 5,
        "adultos": 2,
        "ninos": 0,
        "bebes": 0,
        "subtotal": 750.00
      }
    ]
  }
}
```

### Error: Cliente No Existe
```json
{
  "message": "The selected id cliente is invalid.",
  "errors": {
    "id_cliente": [
      "El cliente especificado no existe en el sistema."
    ]
  }
}
```

### Error: Falta id_cliente
```json
{
  "message": "The id cliente field is required.",
  "errors": {
    "id_cliente": [
      "El ID del cliente es obligatorio cuando no hay autenticación (reservas desde recepción)."
    ]
  }
}
```

---

## Comparación de Ambos Modos

| Característica | Modo Web | Modo Recepción |
|---|---|---|
| **Autenticación** | Requiere token Sanctum | Sin autenticación |
| **Header Authorization** | ✅ Sí | ❌ No |
| **id_cliente en body** | ❌ No requerido (ignorado) | ✅ Requerido |
| **Validación id_cliente** | Se toma del token | Debe existir en DB |
| **Seguridad** | Cliente solo crea sus reservas | Recepcionista puede crear para cualquiera |
| **Log** | "Reserva creada desde WEB" | "Reserva creada desde RECEPCIÓN" |
| **Fuente típica** | id_fuente = 1 (Web) | id_fuente = 2 (Recepción) |

---

## Pruebas en Postman

### Test 1: Reserva Web con Token
```
POST {{baseUrl}}/api/reservas
Headers:
  Authorization: Bearer {{client_token}}
  Content-Type: application/json

Body:
{
  "id_estado_res": 1,
  "id_fuente": 1,
  "habitaciones": [
    {
      "id_habitacion": 1,
      "fecha_llegada": "2026-05-01",
      "fecha_salida": "2026-05-05",
      "adultos": 2,
      "ninos": 0,
      "bebes": 0
    }
  ]
}
```

**Resultado esperado**: ✅ 201 Created (usa id_cliente del token)

---

### Test 2: Reserva Web intentando especificar otro cliente (debe ignorarse)
```
POST {{baseUrl}}/api/reservas
Headers:
  Authorization: Bearer {{client_token}}
  Content-Type: application/json

Body:
{
  "id_cliente": 99,  // ← Este valor será IGNORADO por seguridad
  "id_estado_res": 1,
  "id_fuente": 1,
  "habitaciones": [
    {
      "id_habitacion": 1,
      "fecha_llegada": "2026-05-01",
      "fecha_salida": "2026-05-05",
      "adultos": 2,
      "ninos": 0,
      "bebes": 0
    }
  ]
}
```

**Resultado esperado**: ✅ 201 Created (usa id_cliente del token, NO el 99)

---

### Test 3: Reserva Recepción sin token
```
POST {{baseUrl}}/api/reservas
Headers:
  Content-Type: application/json

Body:
{
  "id_cliente": 28,  // ← REQUERIDO
  "id_estado_res": 1,
  "id_fuente": 2,
  "notas": "Reserva telefónica",
  "habitaciones": [
    {
      "id_habitacion": 2,
      "fecha_llegada": "2026-06-15",
      "fecha_salida": "2026-06-20",
      "adultos": 2,
      "ninos": 0,
      "bebes": 0
    }
  ]
}
```

**Resultado esperado**: ✅ 201 Created (usa id_cliente=28)

---

### Test 4: Reserva Recepción sin id_cliente (debe fallar)
```
POST {{baseUrl}}/api/reservas
Headers:
  Content-Type: application/json

Body:
{
  "id_estado_res": 1,
  "id_fuente": 2,
  "habitaciones": [
    {
      "id_habitacion": 2,
      "fecha_llegada": "2026-06-15",
      "fecha_salida": "2026-06-20",
      "adultos": 2,
      "ninos": 0,
      "bebes": 0
    }
  ]
}
```

**Resultado esperado**: ❌ 422 Unprocessable Entity
```json
{
  "message": "The id cliente field is required.",
  "errors": {
    "id_cliente": [
      "El ID del cliente es obligatorio cuando no hay autenticación (reservas desde recepción)."
    ]
  }
}
```

---

## Flujo de Validación Interna

### 1. Request llega al endpoint
```php
POST /api/reservas → ReservaController::store(StoreReservaRequest $r)
```

### 2. Validación condicional en StoreReservaRequest
```php
'id_cliente' => $this->user()
    ? 'nullable|integer|exists:clientes,id_cliente'  // Con token: opcional
    : 'required|integer|exists:clientes,id_cliente', // Sin token: requerido
```

### 3. Lógica en el controlador
```php
$clienteAutenticado = $r->user();

if ($clienteAutenticado) {
    // CASO WEB: usar cliente del token
    $data['id_cliente'] = $clienteAutenticado->id_cliente;
    Log::info('Reserva creada desde WEB');
} else {
    // CASO RECEPCIÓN: validar id_cliente presente
    if (!isset($data['id_cliente'])) {
        return response()->json(['error' => 'Se requiere id_cliente'], 400);
    }
    Log::info('Reserva creada desde RECEPCIÓN');
}
```

### 4. Creación de la reserva
```php
$reserva = Reserva::create($data); // Con el id_cliente correcto
```

---

## Archivos Involucrados

1. **routes/api.php** (línea ~94)
   - Ruta POST sin middleware auth:sanctum

2. **app/Http/Requests/reserva/StoreReservaRequest.php** (línea 17)
   - Validación condicional de id_cliente

3. **app/Http/Controllers/Api/reserva/ReservaController.php** (línea 154-183)
   - Lógica de dual mode en método store()

---

## FAQ

### ¿Por qué no usar middleware `auth:sanctum` opcional?
Laravel no tiene un middleware "opcional" nativo. La solución es no aplicar el middleware en la ruta y manejarlo en el controlador con `$request->user()`.

### ¿Cómo distingo en logs qué modo se usó?
Los logs incluyen:
- "Reserva creada desde WEB (cliente autenticado)"
- "Reserva creada desde RECEPCIÓN (sin autenticación)"

### ¿Puede un cliente con token crear reservas para otros clientes?
❌ NO. Por seguridad, si hay token, **siempre** se usa el id_cliente del token, ignorando cualquier valor en el body.

### ¿Puede recepción crear reservas sin especificar id_cliente?
❌ NO. Cuando no hay token, `id_cliente` es **obligatorio** y debe ser un cliente existente.

### ¿Qué pasa si envío token inválido o expirado?
El middleware Sanctum rechazará la petición con 401 Unauthorized **antes** de llegar al controlador.

### ¿Las otras operaciones (GET, PUT, DELETE) requieren auth?
✅ SÍ. Solo el POST (crear reserva) permite modo sin autenticación. El resto requiere token.

---

## Estados de Reserva Típicos

| ID | Nombre | Uso |
|---|---|---|
| 1 | Pendiente | Reserva recién creada, sin pago |
| 2 | Confirmada | Pago mínimo 30% recibido |
| 3 | Modificada | Reserva fue modificada |
| 4 | Cancelada | Reserva cancelada |
| 5 | Check-in | Cliente ha hecho check-in |
| 6 | Check-out | Cliente ha hecho check-out |
| 7 | Finalizada | Reserva completada y cerrada |
| 8 | No-show | Cliente no se presentó |

---

## Fuentes de Reserva Típicas

| ID | Nombre | Uso |
|---|---|---|
| 1 | Web | Reservas desde el sitio web |
| 2 | Recepción | Reservas registradas por staff |
| 3 | Teléfono | Reservas por llamada |
| 4 | Booking.com | Reservas de Booking |
| 5 | Expedia | Reservas de Expedia |
| 6 | Airbnb | Reservas de Airbnb |
| 7 | Agencia de viajes | Reservas por agencia |

---

## Checklist de Pruebas

### Modo Web
- [ ] Crear reserva con token válido
- [ ] Verificar que id_cliente del token se use correctamente
- [ ] Intentar enviar id_cliente diferente (debe ignorarse)
- [ ] Verificar log "Reserva creada desde WEB"
- [ ] Verificar que token inválido rechace con 401

### Modo Recepción
- [ ] Crear reserva sin token con id_cliente válido
- [ ] Verificar error si no se envía id_cliente
- [ ] Verificar error si id_cliente no existe
- [ ] Verificar log "Reserva creada desde RECEPCIÓN"
- [ ] Crear reservas para diferentes clientes

### Validaciones Generales
- [ ] Habitación debe existir
- [ ] Fecha llegada >= hoy
- [ ] Fecha salida > fecha llegada
- [ ] Al menos 1 adulto
- [ ] Capacidad máxima de habitación
- [ ] Habitación disponible en rango de fechas

---

## Soporte

Para más información sobre el flujo completo de reservas, consultar:
- [FLUJO_COMPLETO_RESERVAS.md](FLUJO_COMPLETO_RESERVAS.md)
- [RESERVAS_WEB_VS_RECEPCION.md](RESERVAS_WEB_VS_RECEPCION.md)