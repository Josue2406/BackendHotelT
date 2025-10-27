# 🔄 Sistema de Modificación de Reservas - Hotel Lanaku

## 📋 Resumen

He implementado un sistema completo para permitir **modificaciones de reservas** con tres tipos de cambios:

1. **Cambio de Habitación** (sin extensión de fechas)
2. **Modificación de Fechas** (check-in y/o check-out)
3. **Reducción de Estadía** (checkout anticipado)

---

## 🗂️ Archivos Creados

### 1. Servicio Principal
✅ **`ModificacionReservaService.php`**
- Ubicación: `app/Services/reserva/ModificacionReservaService.php`
- **3 métodos principales:**
  - `cambiarHabitacion()` - Cambio sin extensión
  - `modificarFechas()` - Cambio de check-in/checkout
  - `reducirEstadia()` - Checkout anticipado

### 2. Requests de Validación
✅ **`CambiarHabitacionRequest.php`**
✅ **`ModificarFechasRequest.php`**
✅ **`ReducirEstadiaRequest.php`**

---

## 🔧 Funcionalidades Implementadas

### 1️⃣ CAMBIO DE HABITACIÓN
**Endpoint:** `POST /api/reservas/{id}/modificar/cambiar-habitacion`

**Casos de uso:**
- Cliente quiere upgrade a habitación superior
- Cliente quiere downgrade por precio
- Cambio por preferencias (vista, piso, etc.)

**Request:**
```json
{
  "id_reserva_habitacion": 123,
  "id_habitacion_nueva": 45,
  "motivo": "Cliente solicita habitación con vista al mar"
}
```

**Response:**
```json
{
  "success": true,
  "habitacion_antigua": {
    "id": 12,
    "nombre": "Habitación Estándar 101",
    "precio": 500.00
  },
  "habitacion_nueva": {
    "id": 45,
    "nombre": "Suite Premium 205",
    "precio": 750.00
  },
  "diferencia_precio": 250.00,
  "tipo_ajuste": "cargo_adicional",
  "monto_ajuste": 250.00,
  "reserva": {
    "total_nuevo": 1250.00,
    "monto_pagado": 500.00,
    "monto_pendiente": 750.00
  }
}
```

**Características:**
- ✅ Verifica disponibilidad de la nueva habitación
- ✅ Calcula diferencia de precio automáticamente
- ✅ Actualiza total de la reserva
- ✅ Recalcula montos pagados/pendientes
- ✅ Registra el motivo del cambio

---

### 2️⃣ MODIFICACIÓN DE FECHAS
**Endpoint:** `POST /api/reservas/{id}/modificar/cambiar-fechas`

**Casos de uso:**
- Cliente adelanta check-in
- Cliente atrasa check-out
- Cliente cambia ambas fechas

**Request:**
```json
{
  "id_reserva_habitacion": 123,
  "nueva_fecha_llegada": "2025-11-01",
  "nueva_fecha_salida": "2025-11-10",
  "aplicar_politica": true
}
```

**Response:**
```json
{
  "success": true,
  "fechas_originales": {
    "llegada": "2025-11-05",
    "salida": "2025-11-08",
    "noches": 3
  },
  "fechas_nuevas": {
    "llegada": "2025-11-01",
    "salida": "2025-11-10",
    "noches": 9
  },
  "precios": {
    "precio_anterior": 450.00,
    "precio_nuevo": 1350.00,
    "diferencia": 900.00,
    "penalidad": 0.00,
    "ajuste_total": 900.00
  },
  "politica": "Sin penalidad por extensión",
  "reserva": {
    "total_nuevo": 1350.00,
    "monto_pagado": 135.00,
    "monto_pendiente": 1215.00
  }
}
```

**Características:**
- ✅ Permite modificar solo check-in, solo check-out, o ambos
- ✅ Verifica disponibilidad en nuevas fechas
- ✅ Recalcula precios con tarifas actuales
- ✅ Aplica políticas de cancelación si hay reducción
- ✅ Calcula penalidades según días de anticipación

---

### 3️⃣ REDUCCIÓN DE ESTADÍA
**Endpoint:** `POST /api/reservas/{id}/modificar/reducir-estadia`

**Casos de uso:**
- Checkout anticipado
- Cancelación parcial de noches

**Request:**
```json
{
  "id_reserva_habitacion": 123,
  "nueva_fecha_salida": "2025-11-06",
  "aplicar_politica": true
}
```

**Response:**
```json
{
  "success": true,
  "reduccion": {
    "noches_canceladas": 2,
    "noches_originales": 5,
    "noches_nuevas": 3,
    "fecha_salida_original": "2025-11-08",
    "fecha_salida_nueva": "2025-11-06"
  },
  "montos": {
    "precio_original": 750.00,
    "precio_nuevo": 450.00,
    "monto_noches_canceladas": 300.00,
    "reembolso": 210.00,
    "penalidad": 90.00
  },
  "politica": "Se cobra la primera noche con impuestos (cancelación con menos de 72 horas)",
  "reserva": {
    "total_nuevo": 540.00,
    "monto_pagado": 225.00,
    "monto_pendiente": 315.00
  }
}
```

**Características:**
- ✅ Calcula noches canceladas
- ✅ Aplica políticas de cancelación del Hotel Lanaku
- ✅ Calcula reembolso según política aplicable
- ✅ Registra penalidades si corresponde
- ✅ Actualiza total de reserva

---

## 🔐 Integración con Políticas de Cancelación

El sistema integra automáticamente las **Políticas del Hotel Lanaku**:

### Política Estándar (72 horas)
- **72+ horas:** Sin penalidad, reembolso completo
- **Menos de 72 horas:** Se cobra primera noche (30%)

### Temporada Alta (15 días)
- **15+ días:** Sin penalidad
- **Menos de 15 días:** Se cobra 100% primera noche

### Tarifas No Reembolsables
- **Siempre:** Sin reembolso, sin modificaciones

---

## 📊 Ejemplos de Uso

### Ejemplo 1: Upgrade de Habitación
```bash
POST /api/reservas/123/modificar/cambiar-habitacion
{
  "id_reserva_habitacion": 456,
  "id_habitacion_nueva": 789,
  "motivo": "Cliente solicita habitación más grande"
}
```

**Resultado:**
- Cambio de habitación ejecutado
- Cargo adicional de $150 agregado al total
- Monto pendiente actualizado

---

### Ejemplo 2: Extensión de Estadía
```bash
POST /api/reservas/123/modificar/cambiar-fechas
{
  "id_reserva_habitacion": 456,
  "nueva_fecha_salida": "2025-11-12"
  // fecha_llegada queda igual
}
```

**Resultado:**
- 2 noches adicionales agregadas
- Precio calculado con tarifas actuales
- Sin penalidad (es extensión, no cancelación)

---

### Ejemplo 3: Checkout Anticipado
```bash
POST /api/reservas/123/modificar/reducir-estadia
{
  "id_reserva_habitacion": 456,
  "nueva_fecha_salida": "2025-11-06",
  "aplicar_politica": true
}
```

**Resultado:**
- 2 noches canceladas
- Política aplicada: cargo primera noche
- Reembolso parcial calculado

---

## ⚙️ Próximos Pasos para Completar

### Endpoints a agregar en ReservaController:

```php
// En ReservaController.php

public function cambiarHabitacion(CambiarHabitacionRequest $request, Reserva $reserva)
{
    $service = app(ModificacionReservaService::class);

    $resultado = $service->cambiarHabitacion(
        $reserva,
        $request->id_reserva_habitacion,
        $request->id_habitacion_nueva,
        $request->motivo
    );

    return response()->json($resultado);
}

public function modificarFechas(ModificarFechasRequest $request, Reserva $reserva)
{
    $service = app(ModificacionReservaService::class);

    $resultado = $service->modificarFechas(
        $reserva,
        $request->id_reserva_habitacion,
        $request->nueva_fecha_llegada ? Carbon::parse($request->nueva_fecha_llegada) : null,
        $request->nueva_fecha_salida ? Carbon::parse($request->nueva_fecha_salida) : null,
        $request->aplicar_politica ?? true
    );

    return response()->json($resultado);
}

public function reducirEstadia(ReducirEstadiaRequest $request, Reserva $reserva)
{
    $service = app(ModificacionReservaService::class);

    $resultado = $service->reducirEstadia(
        $reserva,
        $request->id_reserva_habitacion,
        Carbon::parse($request->nueva_fecha_salida),
        $request->aplicar_politica ?? true
    );

    return response()->json($resultado);
}
```

### Rutas a agregar en `routes/api.php`:

```php
// Modificaciones de Reserva
Route::post('reservas/{reserva}/modificar/cambiar-habitacion', [ReservaController::class, 'cambiarHabitacion']);
Route::post('reservas/{reserva}/modificar/cambiar-fechas', [ReservaController::class, 'modificarFechas']);
Route::post('reservas/{reserva}/modificar/reducir-estadia', [ReservaController::class, 'reducirEstadia']);
```

---

## ✅ Beneficios del Sistema

1. **Flexibilidad para el Cliente**
   - Puede cambiar habitación por preferencia
   - Puede modificar fechas según necesidad
   - Puede hacer checkout anticipado

2. **Transparencia en Costos**
   - Muestra diferencias de precio claramente
   - Aplica políticas automáticamente
   - Calcula reembolsos precisos

3. **Integración Completa**
   - Se integra con sistema de pagos
   - Usa políticas de cancelación del hotel
   - Actualiza montos automáticamente

4. **Validaciones Robustas**
   - Verifica disponibilidad
   - Valida fechas
   - Previene conflictos

---

**Desarrollado para Hotel Lanaku** 🏨
**Fecha:** Octubre 2025
**Versión:** 1.0