# 🏨 FLUJO COMPLETO DEL SISTEMA DE RESERVAS

Este documento explica el ciclo de vida completo de una reserva desde su creación hasta su finalización, incluyendo todos los caminos posibles (pagos, cancelaciones, modificaciones, extensiones).

---

## 📊 ESTADOS DE RESERVA

El sistema maneja **8 estados** diferentes para una reserva:

| ID | Estado | Descripción | ¿Terminal? |
|----|--------|-------------|-----------|
| 1 | **Pendiente** | Reserva creada sin pago mínimo | No |
| 2 | **Cancelada** | Reserva cancelada por el cliente o el hotel | Sí |
| 3 | **Confirmada** | Reserva confirmada (pagó al menos 30%) | No |
| 4 | **Check-in** | Cliente ya hizo check-in (está en el hotel) | No |
| 5 | **Check-out** | Cliente ya hizo check-out (dejó el hotel) | No |
| 6 | **No Show** | Cliente no se presentó | Sí |
| 7 | **En Espera** | Reserva en lista de espera | No |
| 8 | **Finalizada** | Proceso completo (después del checkout) | Sí |

### Estados Terminales
Los estados **Cancelada**, **No Show** y **Finalizada** son terminales. No se puede cambiar desde estos estados.

### Transiciones Permitidas

```
PENDIENTE → CONFIRMADA, CANCELADA, EN ESPERA
EN ESPERA → CONFIRMADA, CANCELADA
CONFIRMADA → CANCELADA, CHECK-IN, NO SHOW
CHECK-IN → CHECK-OUT, CANCELADA
CHECK-OUT → FINALIZADA
CANCELADA → [ninguno]
NO SHOW → [ninguno]
FINALIZADA → [ninguno]
```

---

## 🎯 FLUJO PRINCIPAL: RESERVA EXITOSA

### **PASO 1: Crear Reserva**

**Endpoint:** `POST /api/reservas`

```json
{
  // "id_cliente": 1,
  "id_estado_res": 1,
  "id_tipo_res": 1,
  "id_fuente": 1,
  "notas": "Reserva para luna de miel",
  "habitaciones": [
    {
      "id_habitacion": 101,
      "fecha_llegada": "2025-11-15",
      "fecha_salida": "2025-11-18",
      "adultos": 2,
      "ninos": 0,
      "bebes": 0,
      "tarifa_noche": 150.00
    }
  ]
}
```

**¿Qué sucede internamente?**

1. ✅ **Validaciones automáticas:**
   - `fecha_salida` > `fecha_llegada` (constraint DB)
   - Capacidad de habitación (adultos + niños + bebés ≤ capacidad)
   - Habitación disponible en esas fechas

2. ✅ **ReservaObserver::creating()** se ejecuta:
   - Genera código único (ej: "TCA4ZJJY")
   - Formato legible: "TCA4-ZJJY"

3. ✅ **Se crea la reserva:**
   - `id_estado_res` = 1 (Pendiente)
   - `total_monto_reserva` = 450.00 USD (3 noches × $150)
   - `monto_pagado` = 0.00
   - `monto_pendiente` = 450.00
   - `pago_completo` = false
   - `porcentaje_minimo_pago` = 30% (135.00 USD)

4. ✅ **Estado de habitación:**
   - La habitación permanece **Disponible** hasta que se confirme la reserva

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "id_reserva": 123,
    "codigo_reserva": "TCA4ZJJY",
    "codigo_formateado": "TCA4-ZJJY",
    "id_estado_res": 1,
    "estado": "Pendiente",
    "total_monto_reserva": 450.00,
    "monto_pagado": 0.00,
    "monto_pendiente": 450.00,
    "pago_completo": false,
    "porcentaje_minimo_pago": 30.00
  }
}
```

---

### **PASO 2: Procesar Pago Inicial (Mínimo 30%)**

**Endpoint:** `POST /api/reservas/123/pagos`

```json
{
  "codigo_moneda": "USD",
  "id_metodo_pago": 1,
  "monto": 135.00,
  "id_estado_pago": 2,
  "referencia": "VISA-4532",
  "notas": "Pago inicial 30%"
}
```

**¿Qué sucede internamente?**

1. ✅ **Validaciones:**
   - Monto mínimo: 0.01
   - Código de moneda válido (USD, CRC, EUR, etc.)
   - Estado de pago válido (Completado, Parcial, Pendiente)

2. ✅ **Conversión de moneda (si aplica):**
   - Si pago en USD: `tipo_cambio = 1.0`, `monto_usd = 135.00`
   - Si pago en CRC: Consulta API, convierte a USD, guarda ambos montos

3. ✅ **Se registra el pago:**
   - Se crea registro en `reserva_pago`
   - Campos: `monto`, `id_moneda`, `tipo_cambio`, `monto_usd`

4. ✅ **ReservaPagoObserver::created()** se ejecuta:
   - Llama a `Reserva::actualizarMontosPago()`
   - Suma todos los pagos completados/parciales usando `monto_usd`
   - Actualiza `monto_pagado` = 135.00
   - Actualiza `monto_pendiente` = 315.00
   - Calcula `porcentaje_pagado` = 30%

5. ✅ **Cambio automático de estado:**
   - Como alcanzó el 30%, **cambia de Pendiente (1) → Confirmada (3)**
   - Observer ejecuta `ReservaObserver::updated()`

6. ✅ **ReservaObserver::updated()** detecta cambio a Confirmada:
   - Llama a `marcarHabitacionesConfirmadas()`
   - Si `fecha_llegada` ≤ hoy: Habitación → **Ocupada**
   - Si `fecha_llegada` > hoy: Habitación sigue **Disponible** (reserva futura)

**Respuesta:**
```json
{
  "success": true,
  "message": "Pago procesado exitosamente",
  "data": {
    "id_reserva_pago": 456,
    "monto": 135.00,
    "moneda": { "codigo": "USD", "nombre": "Dólar Estadounidense" },
    "tipo_cambio": 1.0,
    "monto_usd": 135.00
  },
  "reserva": {
    "id_reserva": 123,
    "id_estado_res": 3,
    "estado": "Confirmada",
    "monto_pagado": 135.00,
    "monto_pendiente": 315.00,
    "pago_completo": false,
    "porcentaje_pagado": 30.0
  }
}
```

---

### **PASO 3: Pago Completo (Opcional)**

**Endpoint:** `POST /api/reservas/123/pagos`

```json
{
  "codigo_moneda": "CRC",
  "id_metodo_pago": 1,
  "monto": 163957.50,
  "id_estado_pago": 2,
  "referencia": "TRANSFERENCIA-001"
}
```

**¿Qué sucede?**

1. ✅ Sistema consulta tipo de cambio: 1 USD = 520.50 CRC
2. ✅ Convierte: 163,957.50 ÷ 520.50 = 315.00 USD
3. ✅ Registra pago: `monto = 163957.50`, `tipo_cambio = 520.50`, `monto_usd = 315.00`
4. ✅ Observer actualiza totales:
   - `monto_pagado` = 135 + 315 = 450.00 USD
   - `monto_pendiente` = 0.00
   - `pago_completo` = **true**

**Estado:** Sigue en **Confirmada** (no cambia hasta check-in)

---

### **PASO 4: Check-In (Día de llegada)**

**Endpoint:** `POST /api/reservas/123/checkin`

```json
{
  "fecha_entrada": "2025-11-15 14:30:00",
  "notas": "Cliente llegó temprano"
}
```

**¿Qué sucede internamente?**

1. ✅ **Validaciones:**
   - Reserva debe estar en estado **Confirmada** (3)
   - Fecha de check-in no puede ser anterior a `fecha_llegada`
   - Pago mínimo del 30% debe estar cubierto

2. ✅ **Se cambia el estado:**
   - `id_estado_res` = 3 (Confirmada) → 4 (Check-in)

3. ✅ **ReservaObserver::updated()** detecta cambio a Check-in:
   - Llama a `marcarHabitacionesOcupadas()`
   - **Todas las habitaciones** de la reserva → Estado **Ocupada** (2)

4. ✅ **Se crea registro de Estadía:**
   - Tabla: `estadia`
   - Campos: `id_reserva`, `fecha_entrada`, `fecha_salida_prevista`

**Respuesta:**
```json
{
  "success": true,
  "message": "Check-in realizado exitosamente",
  "data": {
    "id_reserva": 123,
    "id_estado_res": 4,
    "estado": "Check-in",
    "estadia": {
      "id_estadia": 789,
      "fecha_entrada": "2025-11-15 14:30:00",
      "fecha_salida_prevista": "2025-11-18 12:00:00"
    }
  }
}
```

**Estado de habitación:** **Ocupada** (2)

---

### **PASO 5: Check-Out (Día de salida)**

**Endpoint:** `POST /api/reservas/123/checkout`

```json
{
  "fecha_salida": "2025-11-18 11:00:00",
  "monto_adicional": 0.00,
  "notas": "Todo en orden"
}
```

**¿Qué sucede internamente?**

1. ✅ **Validaciones:**
   - Reserva debe estar en estado **Check-in** (4)
   - Pago debe estar completo (`pago_completo = true`)
   - Si hay cargos adicionales, deben estar pagados

2. ✅ **Se cambia el estado:**
   - `id_estado_res` = 4 (Check-in) → 5 (Check-out)

3. ✅ **ReservaObserver::updated()** detecta cambio a Check-out:
   - Llama a `marcarHabitacionesSucias()`
   - **Todas las habitaciones** → Estado **Sucia** (3)

4. ✅ **Se actualiza la estadía:**
   - `fecha_salida_real` = "2025-11-18 11:00:00"
   - `estado` = "Finalizada"

**Respuesta:**
```json
{
  "success": true,
  "message": "Check-out realizado exitosamente",
  "data": {
    "id_reserva": 123,
    "id_estado_res": 5,
    "estado": "Check-out"
  }
}
```

**Estado de habitación:** **Sucia** (3)

---

### **PASO 6: Finalizar Reserva**

**Endpoint:** `PATCH /api/reservas/123`

```json
{
  "id_estado_res": 8
}
```

**¿Qué sucede?**

1. ✅ **Se cambia el estado:**
   - `id_estado_res` = 5 (Check-out) → 8 (Finalizada)

2. ✅ **Estado final alcanzado:**
   - La reserva ya no puede cambiar de estado
   - Las habitaciones siguen en estado **Sucia** hasta que housekeeping las limpie

**Estado de habitación:** **Sucia** (3) - Hasta que limpieza la marque como **Limpia** (4) o **Disponible** (1)

---

## 🔄 FLUJOS ALTERNATIVOS

### **A. CANCELAR RESERVA**

#### A.1 Preview de Cancelación

**Endpoint:** `GET /api/reservas/123/cancelacion/preview`

```json
{
  "success": true,
  "data": {
    "id_reserva": 123,
    "total_pagado": 450.00,
    "politica_aplicable": {
      "id_politica": 2,
      "nombre": "Cancelación con 7-15 días de anticipación",
      "porcentaje_penalizacion": 25,
      "dias_minimos_anticipacion": 7,
      "dias_maximos_anticipacion": 15
    },
    "dias_anticipacion": 10,
    "penalizacion": 112.50,
    "monto_reembolso": 337.50,
    "puede_cancelar": true
  }
}
```

**Cálculo de reembolso:**
```
Total pagado: $450.00
Penalización (25%): $112.50
Reembolso: $337.50
```

#### A.2 Confirmar Cancelación

**Endpoint:** `POST /api/reservas/123/cancelar-con-politica`

```json
{
  "motivo": "Cambio de planes",
  "confirmar": true
}
```

**¿Qué sucede internamente?**

1. ✅ **Se calcula reembolso** según política aplicable
2. ✅ **Se cambia el estado:**
   - `id_estado_res` → 2 (Cancelada)

3. ✅ **ReservaObserver::updated()** detecta cambio a Cancelada:
   - Llama a `liberarHabitaciones()`
   - Si habitación NO está en **Mantenimiento** (5):
     - Habitación → Estado **Disponible** (1)
   - Si habitación SÍ está en **Mantenimiento**:
     - Se mantiene en **Mantenimiento** (5)

4. ✅ **Se registra reembolso:**
   - Tabla: `reserva_pago`
   - `id_estado_pago` = 4 (Reembolsado)
   - `monto` = monto del reembolso

**Respuesta:**
```json
{
  "success": true,
  "message": "Reserva cancelada exitosamente",
  "data": {
    "id_reserva": 123,
    "id_estado_res": 2,
    "estado": "Cancelada",
    "reembolso": {
      "monto_original": 450.00,
      "penalizacion": 112.50,
      "monto_reembolso": 337.50
    }
  }
}
```

**Estado de habitación:** **Disponible** (1) - Excepto si está en mantenimiento

---

### **B. MODIFICAR RESERVA**

#### B.1 Cambiar Fechas

**Endpoint:** `PATCH /api/reservas/123`

```json
{
  "habitaciones": [
    {
      "id_reserva_habitacion": 456,
      "fecha_llegada": "2025-11-20",
      "fecha_salida": "2025-11-23"
    }
  ]
}
```

**Validaciones:**
- Habitación disponible en nuevas fechas
- No puede modificar si estado es Check-in o posterior
- Nueva fecha_salida > nueva fecha_llegada

#### B.2 Cambiar Habitación

**Endpoint:** `PATCH /api/reservas/123`

```json
{
  "habitaciones": [
    {
      "id_reserva_habitacion": 456,
      "id_habitacion": 105
    }
  ]
}
```

**Validaciones:**
- Nueva habitación disponible
- Capacidad suficiente
- Puede haber diferencia de precio

---

### **C. EXTENDER ESTADÍA**

**Endpoint:** `POST /api/reservas/123/extender`

```json
{
  "noches_adicionales": 2,
  "nueva_fecha_salida": "2025-11-20"
}
```

**¿Qué sucede internamente?**

1. ✅ **Validaciones:**
   - Reserva debe estar en **Check-in** (4)
   - `nueva_fecha_salida` > `fecha_salida` actual
   - Habitación disponible en fechas adicionales

2. ✅ **ExtensionEstadiaService verifica disponibilidad:**
   - Si habitación actual está disponible: OK
   - Si habitación NO disponible: Ofrece alternativas

**Caso 1: Habitación disponible**

```json
{
  "success": true,
  "disponible": true,
  "misma_habitacion": true,
  "costo_adicional": 300.00,
  "nueva_fecha_salida": "2025-11-20",
  "noches_adicionales": 2
}
```

**Caso 2: Habitación NO disponible - Ofrece alternativas**

```json
{
  "success": true,
  "disponible": false,
  "misma_habitacion": false,
  "mensaje": "La habitación actual no está disponible. Se encontraron alternativas.",
  "habitaciones_alternativas": [
    {
      "id_habitacion": 106,
      "nombre": "Suite Deluxe",
      "tarifa_noche": 180.00,
      "costo_adicional": 360.00
    }
  ]
}
```

#### C.2 Confirmar Extensión con Cambio de Habitación

**Endpoint:** `POST /api/reservas/123/extender/confirmar`

```json
{
  "id_habitacion_nueva": 106,
  "noches_adicionales": 2,
  "nueva_fecha_salida": "2025-11-20"
}
```

**¿Qué sucede?**

1. ✅ Se actualiza la reserva con nueva fecha de salida
2. ✅ Se crea nueva `reserva_habitacion` para las noches adicionales
3. ✅ Se calcula costo adicional
4. ✅ Se actualiza `total_monto_reserva`
5. ✅ Se actualiza `monto_pendiente`

---

### **D. NO SHOW (Cliente no se presenta)**

**Endpoint:** `PATCH /api/reservas/123`

```json
{
  "id_estado_res": 6,
  "motivo": "Cliente no llegó y no avisó"
}
```

**¿Qué sucede?**

1. ✅ **Se cambia el estado:**
   - `id_estado_res` = 3 (Confirmada) → 6 (No Show)

2. ✅ **ReservaObserver NO libera habitaciones automáticamente**
   - El hotel debe decidir manualmente

3. ✅ **Política de No Show:**
   - Generalmente NO hay reembolso
   - El hotel retiene el pago completo o parcial según política

**Estado final:** No Show (estado terminal)

---

## 🏢 ESTADOS DE HABITACIÓN Y SU RELACIÓN CON RESERVAS

### Ciclo de vida de una habitación en una reserva:

```
1. DISPONIBLE (1)
   ↓
   [Reserva creada - Estado: Pendiente]
   ↓
   Habitación sigue DISPONIBLE
   ↓
   [Pago del 30% - Estado cambia a: Confirmada]
   ↓
2. OCUPADA (2) - Solo si fecha_llegada ≤ hoy
   ↓
   [Check-in realizado - Estado: Check-in]
   ↓
   OCUPADA (2)
   ↓
   [Check-out realizado - Estado: Check-out]
   ↓
3. SUCIA (3)
   ↓
   [Housekeeping limpia]
   ↓
4. LIMPIA (4) o DISPONIBLE (1)
```

### Estados especiales:

**MANTENIMIENTO (5):**
- Las habitaciones en mantenimiento NO se liberan al cancelar
- NO se pueden reservar
- Permanecen en mantenimiento hasta que el personal lo cambie

---

## 📋 RESUMEN DE OBSERVERS

### **ReservaObserver**

| Evento | Método | Acción |
|--------|--------|--------|
| `creating` | `creating()` | Genera código único de reserva |
| `created` | `created()` | Si es Confirmada, marca habitaciones |
| `updated` | `updated()` | Detecta cambios de estado y ejecuta acciones |
| `deleted` | `deleted()` | Libera habitaciones |

**Acciones por cambio de estado:**

- **→ Cancelada:** Libera habitaciones (a Disponible, excepto Mantenimiento)
- **→ Confirmada:** Marca habitaciones (a Ocupada si fecha_llegada ≤ hoy)
- **→ Check-in:** Marca habitaciones como Ocupadas
- **→ Check-out:** Marca habitaciones como Sucias

### **ReservaPagoObserver**

| Evento | Método | Acción |
|--------|--------|--------|
| `created` | `created()` | Actualiza montos y cambia estado si alcanza 30% |
| `updated` | `updated()` | Recalcula montos si cambió estado o monto |
| `deleted` | `deleted()` | Recalcula montos |

---

## 🔐 VALIDACIONES DE NEGOCIO

### **Al Crear Reserva:**
- ✅ `fecha_salida` > `fecha_llegada` (constraint DB)
- ✅ Capacidad: adultos + niños + bebés ≤ capacidad habitación
- ✅ Habitación disponible en fechas seleccionadas

### **Al Procesar Pago:**
- ✅ Monto mínimo: 0.01
- ✅ Código de moneda soportado
- ✅ Estado de pago válido (Completado, Parcial, Pendiente)

### **Al Hacer Check-in:**
- ✅ Estado debe ser **Confirmada**
- ✅ Pago mínimo 30% cubierto
- ✅ Fecha check-in ≥ fecha_llegada

### **Al Hacer Check-out:**
- ✅ Estado debe ser **Check-in**
- ✅ Pago completo (`pago_completo = true`)
- ✅ Cargos adicionales pagados

### **Al Cancelar:**
- ✅ No puede estar en Check-in o Check-out
- ✅ Políticas de cancelación según días de anticipación

### **Al Extender:**
- ✅ Estado debe ser **Check-in**
- ✅ Nueva fecha > fecha actual
- ✅ Habitación disponible (o alternativas)

---

## 📊 DIAGRAMA DE FLUJO COMPLETO

```
┌─────────────────────────────────────────────────────────────────┐
│                    CREAR RESERVA                                │
│  Estado: PENDIENTE | Habitación: DISPONIBLE                    │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│              PROCESAR PAGO (≥30%)                               │
│  Estado: CONFIRMADA | Habitación: DISPONIBLE u OCUPADA         │
│  (Ocupada solo si fecha_llegada ≤ hoy)                         │
└────────┬────────────────────────────────────────────────────────┘
         │
         │    ┌────────────────────────────┐
         │    │   CANCELAR RESERVA         │
         ├───▶│   Estado: CANCELADA        │
         │    │   Habitación: DISPONIBLE   │
         │    └────────────────────────────┘
         │
         │    ┌────────────────────────────┐
         │    │   MODIFICAR FECHAS         │
         ├───▶│   Estado: CONFIRMADA       │
         │    │   Valida disponibilidad    │
         │    └────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────────┐
│                   CHECK-IN                                      │
│  Estado: CHECK-IN | Habitación: OCUPADA                        │
└────────┬────────────────────────────────────────────────────────┘
         │
         │    ┌────────────────────────────┐
         │    │   EXTENDER ESTADÍA         │
         ├───▶│   Estado: CHECK-IN         │
         │    │   Calcula costo adicional  │
         │    └────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────────┐
│                  CHECK-OUT                                      │
│  Estado: CHECK-OUT | Habitación: SUCIA                         │
└────────┬────────────────────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────────┐
│                 FINALIZADA                                      │
│  Estado: FINALIZADA | Habitación: SUCIA                        │
│  (Housekeeping la limpia → LIMPIA → DISPONIBLE)               │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🎓 EJEMPLOS PRÁCTICOS COMPLETOS

Ver el archivo [`TESTING_EXAMPLES.md`](TESTING_EXAMPLES.md) para ejemplos detallados de HTTP requests para cada operación.

---

**Fecha:** 2025-10-15
**Sistema:** Backend-SistemaHotelero
**Versión:** 1.0
