# Módulo Housekeeping (Limpieza & Mantenimiento)

> **Resumen corto:** En producción el flujo real usa **PATCH** (update). Los métodos **POST** y **DELETE** existen **solo para pruebas** (QA/Dev). Cada actualización dispara un **evento en tiempo real** usando **Laravel Reverb** (WebSockets). La autenticación se maneja con **Laravel Sanctum** y todas las rutas de escritura están protegidas para registrar **responsable** (`id_usuario_reporta`) y **fecha** (`fecha_reporte`).  
> **Campos y reglas**: consultar los **FormRequests** y **Resources** en `app/Http/Requests/house_keeping/*` y `app/Http/Resources/house_keeping/*` para el contrato exacto.

---

## 1) Propósito

Gestionar las operaciones de **Limpieza** y **Mantenimiento** de habitaciones: creación inicial (vía servicio de Habitación), **actualización** por PATCH, finalización y auditoría mediante historiales independientes.

- Existen **servicios para crear nuevas lineas** que crea automáticamente la línea relacionada en **Limpieza** y **Mantenimiento**.
- Por lo anterior, en uso real **no** se llama a `POST /limpiezas` o `POST /mantenimientos` desde la UI; se **actualiza** el registro existente con `PATCH`.

---

## 2) Estructura del proyecto (housekeeping)

Ubicación de componentes del módulo:

```
app/
  Http/
    Controllers/
      Api/
        house_keeping/
          LimpiezaController.php
          MantenimientoController.php
          HistorialLimpiezaController.php   # solo GET (index/show/porLimpieza)
          HistorialMantenimientoController.php  # solo GET (index/show/porMantenimiento)
    Requests/
      house_keeping/
        StoreLimpiezaRequest.php
        UpdateLimpiezaRequest.php
        StoreMantenimientoRequest.php
        UpdateMantenimientoRequest.php
  Http/Resources/
    house_keeping/
      LimpiezaResource.php
      MantenimientoResource.php
  Models/
    house_keeping/
      Limpieza.php
      Mantenimiento.php
  Services/
    house_keeping/
      LimpiezaService.php    --para el registro de historial
      MantenimientoService.php   --para el registro de historial
      RegistroAutomaticoDeLimpiezaService.php  --para el registro limpieza desde la creacion de una habitacion
      RegistroAutomaticoDeMantenimientoService.php  --para el registro limpieza desde la creacion de una habitacion
# Habitaciones y Estado están fuera del módulo housekeeping
app/Models/habitacion/Habitacione.php
app/Models/habitacion/EstadoHabitacion.php
```

> **Nota**: Los **Requests** definen las **reglas de validación** y los **Resources** definen el **shape** de la respuesta JSON. Revísalos para conocer exactamente los nombres de campos, enums, relaciones cargadas y formatos de fecha.

---

## 3) Reglas clave de negocio

- **Único método de uso real:** `PATCH /limpiezas/{id}` y `PATCH /mantenimientos/{id}`.
- En **cada actualización** (incluye `finalizar`):
  - Se registra `id_usuario_reporta` con el usuario autenticado y `fecha_reporte` con `now()`.
  - Se ejecuta el **servicio de historial** correspondiente (Limpieza/Mantenimiento).
  - Se emite un **evento** a través de **Laravel Reverb** para actualizar al frontend en tiempo real.
- **POST** y **DELETE** en Limpieza/Mantenimiento están habilitados **solo para casos de prueba** (QA/Dev).

---

## 4) Autenticación y protección

- Todas las rutas operativas están protegidas con **Sanctum** (`auth:sanctum`).  
- En clientes (Postman/Frontend), envía el token:
  ```http
  Authorization: Bearer <TOKEN_SANCTUM>
  ```

### Instalación rápida de Sanctum (si corresponde)
```bash
composer require laravel/sanctum
```

---

## 5) WebSockets en tiempo real con Laravel Reverb

Cada cambio relevante dispara un **evento** (por ejemplo, `NuevaLimpiezaAsignada`) que viaja por **Reverb**.

### Instalación
```bash
composer require laravel/reverb
```

Variables típicas en `.env` (ya configuradas en el proyecto):
```
REVERB_APP_ID=...
REVERB_APP_KEY=...
REVERB_APP_SECRET=...
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
BROADCAST_CONNECTION=reverb
```

### Ejecución en local
```bash
php artisan reverb:start
php artisan queue:work   # si los eventos van en cola
```

### Consumo en Frontend
- Usar **Laravel Echo** o pusher-js compatible para suscribirse a los canales y escuchar el evento.
- El **payload** incluye: `id`, `habitacion`, `asignado_a`, `estado`, `fecha`, `prioridad`, etc. (ver evento en `events/`).

---

## 6) Rutas de API (muy importante)

```php
// Rutas protegidas: registran historial, responsable y usuario reporta
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('limpiezas', LimpiezaController::class);
    Route::apiResource('mantenimientos', MantenimientoController::class);
});

// Historial de Limpiezas (solo lectura)
Route::apiResource('historial-limpiezas', HistorialLimpiezaController::class)
    ->only(['index', 'show']);
Route::get('limpiezas/{id}/historial', [HistorialLimpiezaController::class, 'porLimpieza']);

// Historial de Mantenimientos (solo lectura)
Route::apiResource('historial-mantenimientos', HistorialMantenimientoController::class)
    ->only(['index', 'show']);
Route::get('mantenimientos/{id}/historial', [HistorialMantenimientoController::class, 'porMantenimiento']);
```

> **Protección**: Mantener `auth:sanctum` es crítico para poder auditar el **usuario** y garantizar que el historial registre al **responsable** y la **fecha** de cada operación.

---

## 7) Endpoints (contratos básicos)

> **POST** y **DELETE** existen **solo** para pruebas. En operación real, usar **PATCH**.

### Limpieza
- `GET    /api/limpiezas`  
  Filtros: `prioridad`, `pendientes`, `id_habitacion`, `estado_id`, `desde`, `hasta`.
- `POST   /api/limpiezas` *(solo pruebas)*
- `GET    /api/limpiezas/{id}`
- `PATCH  /api/limpiezas/{id}` *(método real)*
- `PATCH  /api/limpiezas/{id}/finalizar`
- `DELETE /api/limpiezas/{id}` *(solo pruebas)*

### Mantenimiento
- `GET    /api/mantenimientos`
- `POST   /api/mantenimientos` *(solo pruebas)*
- `GET    /api/mantenimientos/{id}`
- `PATCH  /api/mantenimientos/{id}` *(método real)*
- `PATCH  /api/mantenimientos/{id}/finalizar`
- `DELETE /api/mantenimientos/{id}` *(solo pruebas)*

### Historial (solo lectura)
- `GET /api/historial-limpiezas`
- `GET /api/historial-limpiezas/{id}`
- `GET /api/limpiezas/{id}/historial`
- `GET /api/historial-mantenimientos`
- `GET /api/historial-mantenimientos/{id}`
- `GET /api/mantenimientos/{id}/historial`

> **Campos y validaciones**: consultar `Store*/Update*Request`.  
> **Estructura de respuesta** (relaciones, formatos): consultar `*Resource`.

---

## 8) Ejemplos de uso (referencia)

### (Real) Actualizar Limpieza
```http
PATCH /api/limpiezas/123
Authorization: Bearer <TOKEN>
Content-Type: application/json

{
  "id_usuario_asigna": 45,
  "prioridad": "alta",
  "notas": "Se reprogramó por ocupación"
}
```
Efectos:
- Actualiza campos permitidos por `UpdateLimpiezaRequest`.
- Registra `id_usuario_reporta` y `fecha_reporte` en backend. #utilizar zona horaria segun corresponga en el .env para cargar tu zona horaria local ejemplo: APP_TIMEZONE=America/Costa_Rica ---configuracion ya establecida
- Registra entrada en **historial de limpiezas**.
- Emite **evento Reverb** hacia el frontend.

### (Pruebas) Crear Limpieza
```http
POST /api/limpiezas
Authorization: Bearer <TOKEN>
Content-Type: application/json

{
  "fecha_inicio": "2025-10-01 10:30:00",
  "prioridad": "media",
  "id_usuario_asigna": 12, #es el usuario al que se le asigna la tarea
  "id_habitacion": 204,
  "id_estado_hab": 3,
  "notas": "Cambio de sábanas"
  #usuario reporta y fecha reporte se manejan por debajo del sistema
}
```

### (Pruebas) Eliminar Limpieza
```http
DELETE /api/limpiezas/123
Authorization: Bearer <TOKEN>
```

*(Análogos para Mantenimiento; verificar `UpdateMantenimientoRequest` y `MantenimientoResource`.)*

---

## 9) Historiales

- **Servicios dedicados**:
  - `LimpiezaService` → tabla `historial_limpiezas`
  - `MantenimientoService` → tabla `historial_mantenimientos`
- **Controladores** exponen solo **GET** (`index`, `show`, y `por<Limpieza|Mantenimiento>`).
- Cada registro de historial incluye **quién** hizo el cambio (usuario autenticado) **evento** **valores anterior y nuevo** y **cuándo**.

---

## 10) Diagrama de flujo

```mermaid
flowchart LR
A[Servicio Habitación] -->|crea auto| B(Limpieza base)
A -->|crea auto| C(Mantenimiento base)

B --> D[PATCH Limpieza]
C --> E[PATCH Mantenimiento]

D -->|actualiza| F[Historial Limpieza]
E -->|actualiza| G[Historial Mantenimiento]

D -->|cambia asignación| H[Evento Reverb]
E -->|cambia asignación| H

D -->|finaliza| I[PATCH /limpiezas/{id}/finalizar]
E -->|finaliza| J[PATCH /mantenimientos/{id}/finalizar]
```

---

## 11) QA y pruebas

- **Unit tests con mocks** (no requieren BD):  
  Validan interacción del controlador con los servicios, emisión de eventos y forma básica del Resource.
- **Smoke tests** manuales en Postman/Insomnia para flujos críticos (con token Sanctum).

> Si en algún momento quieres integración automatizada, preparar una BD de testing o usar MySQL en testing evita limitaciones de SQLite (views, tipos `SET/ENUM`).

---

## 12) Notas finales

- Mantener actualizado este README cuando cambien **Requests/Resources** o las **rutas**.
- Documentar en un ADR cualquier cambio de estrategia (por ejemplo, si en el futuro PATCH deja de nulificar omitidos, etc.).
