# Sistema de Reservas - Separación de Lógica Administrativa y Web

## 📋 Resumen de Cambios

Se ha implementado una separación completa entre las reservas administrativas (personal del hotel) y las reservas web (clientes), proporcionando endpoints específicos para cada tipo de usuario con sus respectivas validaciones y lógicas de negocio.

---

## 🏢 Reservas Administrativas (`/api/reservas`)

### Autenticación
- **Requiere**: Token de **Usuario** administrativo (personal del hotel, recepción, staff)
- **Middleware**: `auth:sanctum`
- **Guard**: Valida que el usuario sea del modelo `User`/`Usuario`

### Características
- Los usuarios administrativos pueden crear reservas para **cualquier cliente**
- Requiere especificar el `id_cliente` en el request
- Acceso completo a todas las operaciones de reserva
- Incluye operaciones avanzadas: check-in, check-out, modificaciones, extensiones, reportes

---

## 📍 Endpoints Administrativos

### 1. Crear Reserva Administrativa
```http
POST /api/reservas
Authorization: Bearer {token_usuario_administrativo}
Content-Type: application/json
```

**Body:**
```json
{
  "id_cliente": 28,
  "id_tipo_res": 1,
  "id_estado_res": 1,
  "id_fuente": 1,
  "notas": "Reserva realizada por recepción",
  "habitaciones": [
    {
      "id_habitacion": 1,
      "fecha_llegada": "2026-08-20",
      "fecha_salida": "2026-08-26",
      "adultos": 2,
      "ninos": 0,
      "bebes": 0
    }
  ]
}
```

**Campos Requeridos:**
- `id_cliente` ✅ **OBLIGATORIO** - ID del cliente para quien se crea la reserva
- `id_estado_res` ✅ Estado inicial de la reserva
- `id_fuente` ✅ Fuente de la reserva (1=Recepción, 2=Web, etc.)
- `habitaciones` ✅ Array con al menos una habitación

**Respuesta Exitosa (201):**
```json
{
  "id_reserva": 123,
  "codigo_reserva": "UPUNG4HK",
  "id_cliente": 28,
  "id_estado_res": 1,
  "id_fuente": 1,
  "fecha_creacion": "2025-11-03 13:38:39",
  "total_monto_reserva": 750.00,
  "notas": "Reserva realizada por recepción",
  "cliente": {
    "id_cliente": 28,
    "nombre": "Juan",
    "apellido1": "Pérez"
  },
  "estado": {
    "id_estado_res": 1,
    "nombre": "Pendiente"
  },
  "habitaciones": [...]
}
```

---

### 2. Listar Reservas (Admin)
```http
GET /api/reservas
Authorization: Bearer {token_usuario_administrativo}
```

**Query Parameters (opcionales):**
- `search` - Búsqueda general (ID, nombre cliente, email, notas)
- `estado` - Filtrar por nombre de estado
- `desde` - Fecha desde (fecha creación o llegada)
- `hasta` - Fecha hasta
- `fuente` - Filtrar por nombre de fuente

**Ejemplo:**
```http
GET /api/reservas?estado=Confirmada&desde=2025-11-01
```

---

### 3. Ver Detalle de Reserva (Admin)
```http
GET /api/reservas/{id_reserva}
Authorization: Bearer {token_usuario_administrativo}
```

---

### 4. Actualizar Reserva (Admin)
```http
PUT /api/reservas/{id_reserva}
Authorization: Bearer {token_usuario_administrativo}
Content-Type: application/json
```

**Body:**
```json
{
  "id_estado_res": 2,
  "notas": "Cliente confirmó por teléfono"
}
```

---

### 5. Eliminar Reserva (Admin)
```http
DELETE /api/reservas/{id_reserva}
Authorization: Bearer {token_usuario_administrativo}
```

---

### 6. Confirmar Reserva
```http
POST /api/reservas/{id_reserva}/confirmar
Authorization: Bearer {token_usuario_administrativo}
```

Cambia automáticamente el estado a "Confirmada" (ID 2).

---

### 7. Cancelar Reserva
```http
POST /api/reservas/{id_reserva}/cancelar
Authorization: Bearer {token_usuario_administrativo}
```

Cambia el estado a "Cancelada" (ID 3) y libera las habitaciones.

---

### 8. Realizar Check-In
```http
POST /api/reservas/{id_reserva}/realizar-checkin
Authorization: Bearer {token_usuario_administrativo}
Content-Type: application/json
```

**Body (opcional):**
```json
{
  "notas": "Cliente llegó temprano"
}
```

Cambia el estado a "Check-in" (ID 4).

---

### 9. Realizar Check-Out
```http
POST /api/reservas/{id_reserva}/realizar-checkout
Authorization: Bearer {token_usuario_administrativo}
Content-Type: application/json
```

**Body (opcional):**
```json
{
  "notas": "Cliente satisfecho, sin incidencias"
}
```

Cambia el estado a "Check-out" (ID 5).

---

### 10. Cambiar Estado Genérico
```http
PUT /api/reservas/{id_reserva}/estado
Authorization: Bearer {token_usuario_administrativo}
Content-Type: application/json
```

**Body:**
```json
{
  "id_estado_res": 2,
  "notas": "Cambio de estado manual"
}
```

---

### 11. Procesar Pago
```http
POST /api/reservas/{id_reserva}/pagos
Authorization: Bearer {token_usuario_administrativo}
Content-Type: application/json
```

**Body:**
```json
{
  "id_metodo_pago": 1,
  "monto": 225.00,
  "codigo_moneda": "USD",
  "id_estado_pago": 1,
  "referencia": "TRX-12345",
  "notas": "Pago inicial 30%"
}
```

---

### 12. Listar Pagos de Reserva
```http
GET /api/reservas/{id_reserva}/pagos
Authorization: Bearer {token_usuario_administrativo}
```

---

### 13. Extender Estadía
```http
POST /api/reservas/{id_reserva}/extender
Authorization: Bearer {token_usuario_administrativo}
Content-Type: application/json
```

**Body:**
```json
{
  "id_reserva_habitacion": 45,
  "noches_adicionales": 2,
  "nueva_fecha_salida": "2026-08-28"
}
```

---

### 14. Cambiar Habitación
```http
POST /api/reservas/{id_reserva}/modificar/cambiar-habitacion
Authorization: Bearer {token_usuario_administrativo}
Content-Type: application/json
```

**Body:**
```json
{
  "id_reserva_habitacion": 45,
  "id_habitacion_nueva": 10,
  "motivo": "Cliente solicitó habitación con mejor vista"
}
```

---

### 15. Modificar Fechas
```http
POST /api/reservas/{id_reserva}/modificar/cambiar-fechas
Authorization: Bearer {token_usuario_administrativo}
Content-Type: application/json
```

**Body:**
```json
{
  "id_reserva_habitacion": 45,
  "nueva_fecha_llegada": "2026-08-21",
  "nueva_fecha_salida": "2026-08-27",
  "aplicar_politica": true
}
```

---

### 16. Reducir Estadía
```http
POST /api/reservas/{id_reserva}/modificar/reducir-estadia
Authorization: Bearer {token_usuario_administrativo}
Content-Type: application/json
```

**Body:**
```json
{
  "id_reserva_habitacion": 45,
  "nueva_fecha_salida": "2026-08-24",
  "aplicar_politica": true
}
```

---

### 17. Preview de Cancelación
```http
GET /api/reservas/{id_reserva}/cancelacion/preview
Authorization: Bearer {token_usuario_administrativo}
```

Calcula el reembolso según políticas de cancelación sin ejecutar la cancelación.

---

### 18. Cancelar con Política
```http
POST /api/reservas/{id_reserva}/cancelar-con-politica
Authorization: Bearer {token_usuario_administrativo}
Content-Type: application/json
```

**Body:**
```json
{
  "motivo": "Cliente canceló por motivos personales",
  "solicitar_reembolso": true
}
```

---

### 19. Buscar por Código
```http
GET /api/reservas/buscar?codigo=UPUN-G4HK
Authorization: Bearer {token_usuario_administrativo}
```

---

### 20. Reportes y Estadísticas
```http
GET /api/reservas/reportes/kpis
GET /api/reservas/reportes/series-temporales
GET /api/reservas/reportes/distribuciones
GET /api/reservas/reportes/export/pdf
Authorization: Bearer {token_usuario_administrativo}
```

---

## 🌐 Reservas Web (`/api/reservas-web`)

### Autenticación
- **Requiere**: Token de **Cliente** (usuario final que reserva)
- **Middleware**: `auth:sanctum`
- **Guard**: Valida que el usuario sea del modelo `Cliente`

### Características
- Los clientes **SOLO** pueden gestionar **sus propias reservas**
- El `id_cliente` se toma **automáticamente del token** (seguridad)
- Las reservas se crean en estado **"Confirmada" (ID 3)** automáticamente
- La fuente se establece automáticamente como **"Web" (ID 2)**
- Operaciones limitadas: crear, ver, modificar y cancelar

---

## 📍 Endpoints Web (Clientes)

### 1. Crear Reserva Web
```http
POST /api/reservas-web
Authorization: Bearer {token_cliente}
Content-Type: application/json
```

**Body:**
```json
{
  "notas": "Reserva para aniversario",
  "habitaciones": [
    {
      "id_habitacion": 1,
      "fecha_llegada": "2026-08-20",
      "fecha_salida": "2026-08-26",
      "adultos": 2,
      "ninos": 0,
      "bebes": 0
    }
  ]
}
```

**Campos Requeridos:**
- `habitaciones` ✅ Array con al menos una habitación

**Campos Automáticos (NO incluir en request):**
- `id_cliente` - Se toma del token automáticamente
- `id_estado_res` - Se establece en 3 (Confirmada) automáticamente
- `id_fuente` - Se establece en 2 (Web) automáticamente

**Respuesta Exitosa (201):**
```json
{
  "id_reserva": 124,
  "codigo_reserva": "XYZABC12",
  "id_cliente": 45,
  "id_estado_res": 3,
  "id_fuente": 2,
  "fecha_creacion": "2025-11-03 14:30:00",
  "total_monto_reserva": 750.00,
  "cliente": {
    "id_cliente": 45,
    "nombre": "María",
    "apellido1": "González",
    "email": "maria@example.com"
  },
  "estado": {
    "id_estado_res": 3,
    "nombre": "Confirmada"
  },
  "fuente": {
    "id_fuente": 2,
    "nombre": "Web"
  },
  "habitaciones": [...]
}
```

---

### 2. Listar Mis Reservas
```http
GET /api/reservas-web
Authorization: Bearer {token_cliente}
```

**Query Parameters (opcionales):**
- `estado` - Filtrar por nombre de estado
- `desde` - Fecha desde
- `hasta` - Fecha hasta

**Ejemplo:**
```http
GET /api/reservas-web?estado=Confirmada
```

Solo retorna las reservas del cliente autenticado.

---

### 3. Ver Detalle de Mi Reserva
```http
GET /api/reservas-web/{id_reserva}
Authorization: Bearer {token_cliente}
```

Valida que la reserva pertenezca al cliente autenticado. Si intenta ver una reserva de otro cliente, retorna error 403.

---

### 4. Modificar Mi Reserva
```http
PUT /api/reservas-web/{id_reserva}
Authorization: Bearer {token_cliente}
Content-Type: application/json
```

**Body:**
```json
{
  "notas": "Actualización de reserva",
  "numero_adultos": 3,
  "numero_ninos": 1,
  "habitaciones": [
    {
      "id_habitacion": 2,
      "fecha_llegada": "2026-08-20",
      "fecha_salida": "2026-08-26",
      "adultos": 3,
      "ninos": 1,
      "bebes": 0
    }
  ]
}
```

**Restricciones:**
- Solo se puede modificar si está en estado "Pendiente" o "Confirmada"
- Solo puede modificar sus propias reservas
- Si modifica habitaciones, se recalcula el total automáticamente

**Campos Opcionales:**
- `notas` - Notas adicionales
- `numero_adultos` - Actualizar número de adultos
- `numero_ninos` - Actualizar número de niños
- `habitaciones` - Si se incluye, reemplaza completamente las habitaciones

---

### 5. Cancelar Mi Reserva
```http
POST /api/reservas-web/{id_reserva}/cancelar
Authorization: Bearer {token_cliente}
Content-Type: application/json
```

**Body (opcional):**
```json
{
  "notas": "Cancelación por cambio de planes"
}
```

**Restricciones:**
- Solo puede cancelar sus propias reservas
- No se puede cancelar una reserva ya cancelada
- Cambia el estado a "Cancelada" (ID 3)

**Respuesta Exitosa (200):**
```json
{
  "success": true,
  "message": "Reserva cancelada exitosamente",
  "data": {
    "id_reserva": 124,
    "estado": {
      "id_estado_res": 3,
      "nombre": "Cancelada"
    },
    ...
  }
}
```

---

## 🔒 Seguridad y Validaciones

### Reservas Administrativas
1. ✅ Requiere token de Usuario administrativo
2. ✅ Debe especificar `id_cliente` en el request
3. ✅ Puede crear reservas para cualquier cliente
4. ✅ Acceso a todas las operaciones avanzadas

### Reservas Web
1. ✅ Requiere token de Cliente
2. ✅ El `id_cliente` se toma del token (no se puede falsificar)
3. ✅ Solo puede ver/modificar/cancelar sus propias reservas
4. ✅ Estado inicial automático: "Confirmada" (ID 3)
5. ✅ Fuente automática: "Web" (ID 2)
6. ✅ Validación de propiedad en todos los endpoints
7. ✅ Validación de estados permitidos para modificar

---

## 📊 Comparativa de Endpoints

| Operación | Admin (`/api/reservas`) | Web (`/api/reservas-web`) |
|-----------|-------------------------|---------------------------|
| **Crear reserva** | ✅ Para cualquier cliente | ✅ Solo para sí mismo |
| **Ver reservas** | ✅ Todas las reservas | ✅ Solo sus reservas |
| **Modificar** | ✅ Cualquier reserva | ✅ Solo sus reservas |
| **Cancelar** | ✅ Cualquier reserva | ✅ Solo sus reservas |
| **Check-in/Check-out** | ✅ Sí | ❌ No |
| **Cambiar estado** | ✅ Sí | ❌ No |
| **Pagos** | ✅ Gestión completa | ❌ No (por ahora) |
| **Extensiones** | ✅ Sí | ❌ No |
| **Modificaciones** | ✅ Habitación, fechas | ✅ Solo básicas |
| **Reportes** | ✅ Sí | ❌ No |

---

## 🔑 IDs de Estados de Reserva

Según el código implementado:

- **ID 1**: Pendiente
- **ID 2**: Confirmada (usado por admin)
- **ID 3**: Confirmada (usado por web) / Cancelada
- **ID 4**: Check-in
- **ID 5**: Check-out
- **ID 6**: No-show

> **Nota**: Verifica los IDs exactos en tu tabla `estado_reserva` de la base de datos.

---

## 🔑 IDs de Fuentes

- **ID 1**: Recepción / Directo
- **ID 2**: Web (usado automáticamente en reservas web)

> **Nota**: Ajusta según tu catálogo de fuentes en la base de datos.

---

## 📁 Archivos Modificados/Creados

### Archivos Creados
1. ✅ `app/Http/Controllers/Api/reserva/ReservaWebController.php`
2. ✅ `app/Http/Requests/reserva/web/StoreReservaWebRequest.php`
3. ✅ `app/Http/Requests/reserva/web/UpdateReservaWebRequest.php`
4. ✅ `app/Http/Requests/reserva/web/CancelReservaWebRequest.php`
5. ✅ `app/Http/Requests/reserva/CheckInCheckOutRequest.php`

### Archivos Modificados
1. ✅ `app/Http/Controllers/Api/reserva/ReservaController.php` (líneas 160-197)
   - Lógica para diferenciar entre Usuario y Cliente
   - Validación de `id_cliente` según tipo de usuario
2. ✅ `routes/api.php` (líneas 92-169)
   - Rutas administrativas protegidas con `auth:sanctum`
   - Rutas web bajo `/reservas-web` con validación de Cliente

---

## 🧪 Ejemplos de Pruebas

### Prueba 1: Crear Reserva como Admin
```bash
curl -X POST http://localhost:8000/api/reservas \
  -H "Authorization: Bearer {token_admin}" \
  -H "Content-Type: application/json" \
  -d '{
    "id_cliente": 28,
    "id_tipo_res": 1,
    "id_estado_res": 1,
    "id_fuente": 1,
    "habitaciones": [{
      "id_habitacion": 1,
      "fecha_llegada": "2026-08-20",
      "fecha_salida": "2026-08-26",
      "adultos": 2,
      "ninos": 0,
      "bebes": 0
    }]
  }'
```

### Prueba 2: Crear Reserva como Cliente
```bash
curl -X POST http://localhost:8000/api/reservas-web \
  -H "Authorization: Bearer {token_cliente}" \
  -H "Content-Type: application/json" \
  -d '{
    "habitaciones": [{
      "id_habitacion": 1,
      "fecha_llegada": "2026-08-20",
      "fecha_salida": "2026-08-26",
      "adultos": 2,
      "ninos": 0,
      "bebes": 0
    }]
  }'
```

### Prueba 3: Listar Mis Reservas (Cliente)
```bash
curl -X GET http://localhost:8000/api/reservas-web \
  -H "Authorization: Bearer {token_cliente}"
```

### Prueba 4: Cancelar Mi Reserva (Cliente)
```bash
curl -X POST http://localhost:8000/api/reservas-web/124/cancelar \
  -H "Authorization: Bearer {token_cliente}" \
  -H "Content-Type: application/json" \
  -d '{
    "notas": "Cancelación por cambio de planes"
  }'
```

---

## ⚠️ Notas Importantes

1. **Tokens de Autenticación**:
   - Los usuarios administrativos obtienen tokens en `/api/auth/login`
   - Los clientes obtienen tokens en `/api/clientes/auth/login`

2. **Validación de Modelos**:
   - El sistema verifica el tipo de modelo del usuario autenticado
   - Admin: `App\Models\User` o `App\Models\Usuario`
   - Cliente: `App\Models\cliente\Cliente`

3. **Estados Automáticos**:
   - Reservas admin: Usan el estado especificado en el request
   - Reservas web: Siempre inician en "Confirmada" (ID 3)

4. **Fuente Automática**:
   - Reservas web siempre usan fuente "Web" (ID 2)

5. **Correos Electrónicos**:
   - Se envían notificaciones automáticas después del commit de la transacción
   - Notificaciones: ReservaCreada, ReservaActualizada, ReservaCancelada

---

## 📞 Soporte

Para más información sobre la implementación, revisa:
- `app/Http/Controllers/Api/reserva/ReservaController.php`
- `app/Http/Controllers/Api/reserva/ReservaWebController.php`
- `routes/api.php`

---

**Fecha de Implementación**: 03 de Noviembre de 2025
**Versión**: 1.0.0
