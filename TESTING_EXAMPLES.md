# Ejemplos de Pruebas - Sistema de Gestión Hotelero

Este documento contiene ejemplos de peticiones HTTP para probar todas las funcionalidades implementadas.

## 📋 Tabla de Contenidos
1. [Reservas con Validaciones](#1-reservas-con-validaciones)
2. [Sistema de Pagos](#2-sistema-de-pagos)
3. [Cancelaciones con Políticas](#3-cancelaciones-con-políticas)
4. [Extensión de Estadía](#4-extensión-de-estadía)
5. [Códigos de Reserva](#5-códigos-de-reserva)
6. [Estados de Habitaciones](#6-estados-de-habitaciones)

---

## 1. Reservas con Validaciones

### 1.1 Crear Reserva (debe generar código automáticamente)

```http
POST /api/reservas
Content-Type: application/json

{
  "id_cliente": 1,
  "id_estado_res": 1,
  "id_tipo_res": 1,
  "id_origen_res": 1,
  "noches": 3,
  "comentario": "Reserva de prueba con código autogenerado",
  "habitaciones": [
    {
      "id_habitacion": 1,
      "fecha_llegada": "2025-11-01",
      "fecha_salida": "2025-11-04",
      "adultos": 2,
      "ninos": 1,
      "bebes": 0,
      "tarifa_noche": 100.00
    }
  ]
}
```

**Validaciones que se ejecutan:**
- ✅ `fecha_salida > fecha_llegada` (constraint DB)
- ✅ Capacidad máxima de habitación (adultos + niños + bebés <= capacidad)
- ✅ Código de reserva autogenerado (8 caracteres alfanuméricos)
- ✅ `monto_pagado` y `monto_pendiente` inicializados

**Respuesta esperada:**
```json
{
  "success": true,
  "data": {
    "id_reserva": 123,
    "codigo_reserva": "TCA4ZJJY",
    "codigo_formateado": "TCA4-ZJJY",
    "id_cliente": 1,
    "id_estado_res": 1,
    "total_monto_reserva": 300.00,
    "monto_pagado": 0.00,
    "monto_pendiente": 300.00,
    "pago_completo": false,
    "porcentaje_minimo_pago": 30.00,
    "habitaciones": [...]
  }
}
```

### 1.2 Crear Reserva con Error de Capacidad

```http
POST /api/reservas
Content-Type: application/json

{
  "id_cliente": 1,
  "id_estado_res": 1,
  "id_tipo_res": 1,
  "id_origen_res": 1,
  "noches": 2,
  "habitaciones": [
    {
      "id_habitacion": 1,
      "fecha_llegada": "2025-11-01",
      "fecha_salida": "2025-11-03",
      "adultos": 10,
      "ninos": 5,
      "bebes": 3
    }
  ]
}
```

**Respuesta esperada (error):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "habitaciones.0.capacidad": [
      "La habitación 'Habitación Deluxe' tiene capacidad máxima de 4 personas."
    ]
  }
}
```

### 1.3 Crear Reserva con Fechas Inválidas

```http
POST /api/reservas
Content-Type: application/json

{
  "id_cliente": 1,
  "id_estado_res": 1,
  "id_tipo_res": 1,
  "id_origen_res": 1,
  "noches": 2,
  "habitaciones": [
    {
      "id_habitacion": 1,
      "fecha_llegada": "2025-11-05",
      "fecha_salida": "2025-11-03",
      "adultos": 2,
      "ninos": 0,
      "bebes": 0,
      "tarifa_noche": 100.00
    }
  ]
}
```

**Respuesta esperada (error DB constraint):**
```json
{
  "message": "Error al crear la reserva",
  "error": "SQLSTATE[23000]: Integrity constraint violation: chk_fecha_salida_mayor_llegada"
}
```

---

## 2. Sistema de Pagos

**IMPORTANTE:** El sistema ahora soporta múltiples monedas con conversión automática. Todos los precios de habitaciones están en USD, pero se puede pagar en diferentes monedas.

### 2.1 Consultar Monedas Soportadas

```http
GET /api/monedas/soportadas
```

**Respuesta esperada:**
```json
{
  "success": true,
  "data": [
    {
      "codigo": "USD",
      "nombre": "Dólar Estadounidense",
      "en_base_datos": true,
      "id_moneda": 1
    },
    {
      "codigo": "CRC",
      "nombre": "Colón Costarricense",
      "en_base_datos": true,
      "id_moneda": 2
    },
    {
      "codigo": "EUR",
      "nombre": "Euro",
      "en_base_datos": true,
      "id_moneda": 3
    }
  ]
}
```

### 2.2 Consultar Tipos de Cambio Actuales

```http
GET /api/monedas/tipos-cambio
```

**Respuesta esperada:**
```json
{
  "success": true,
  "data": {
    "moneda_base": "USD",
    "fecha_actualizacion": "2025-10-15 10:30:00",
    "cache_valido_hasta": "2025-10-15 22:30:00",
    "tipos_cambio": {
      "USD": 1.0,
      "CRC": 520.50,
      "EUR": 0.92,
      "GBP": 0.79,
      "CAD": 1.36,
      "MXN": 17.25
    }
  }
}
```

### 2.3 Convertir Entre Monedas

```http
GET /api/monedas/convertir?monto=100&desde=USD&hasta=CRC
```

**Respuesta esperada:**
```json
{
  "success": true,
  "data": {
    "monto_original": 100,
    "moneda_origen": "USD",
    "monto_convertido": 52050.00,
    "moneda_destino": "CRC",
    "tipo_cambio": 520.50,
    "formula": "1 USD = 520.50 CRC"
  }
}
```

### 2.4 Procesar Pago en USD (sin conversión)

```http
POST /api/reservas/123/pagos
Content-Type: application/json

{
  "codigo_moneda": "USD",
  "id_metodo_pago": 1,
  "monto": 90.00,
  "id_estado_pago": 2,
  "referencia": "REF-001",
  "notas": "Pago inicial del 30% en dólares"
}
```

**Proceso automático:**
1. Se registra el pago en USD
2. No se requiere conversión (tipo_cambio = 1.0)
3. monto = 90.00, monto_usd = 90.00
4. Observer actualiza monto_pagado usando monto_usd
5. Como alcanzó el 30%, cambia `id_estado_res` de 1 (Pendiente) a 2 (Confirmada)

**Respuesta esperada:**
```json
{
  "success": true,
  "message": "Pago procesado exitosamente",
  "data": {
    "id_reserva_pago": 456,
    "monto": 90.00,
    "moneda": {
      "codigo": "USD",
      "nombre": "Dólar Estadounidense"
    },
    "tipo_cambio": 1.0,
    "tipo_cambio_formateado": "1 USD = 1.000000 USD",
    "monto_usd": 90.00,
    "metodo_pago": "Tarjeta de Crédito",
    "estado_pago": "Completado",
    "referencia": "REF-001",
    "notas": "Pago inicial del 30% en dólares",
    "fecha_pago": "2025-10-15 10:30:00"
  },
  "reserva": {
    "id_reserva": 123,
    "id_estado_res": 2,
    "estado": "Confirmada",
    "total_monto_reserva": 300.00,
    "monto_pagado": 90.00,
    "monto_pendiente": 210.00,
    "pago_completo": false,
    "porcentaje_pagado": 30.0
  }
}
```

### 2.5 Procesar Pago en Colones Costarricenses (CRC)

```http
POST /api/reservas/123/pagos
Content-Type: application/json

{
  "codigo_moneda": "CRC",
  "id_metodo_pago": 1,
  "monto": 109305.00,
  "id_estado_pago": 2,
  "referencia": "REF-002",
  "notas": "Pago del saldo restante en colones"
}
```

**Proceso automático:**
1. Se recibe pago de 109,305 CRC
2. Sistema obtiene tipo de cambio actual: 1 USD = 520.50 CRC
3. Convierte a USD: 109,305 ÷ 520.50 = 210.00 USD
4. Almacena: monto=109305, tipo_cambio=520.50, monto_usd=210.00
5. Observer actualiza totales usando monto_usd
6. Total pagado: 90 + 210 = 300 USD
7. Marca pago_completo = true

**Respuesta esperada:**
```json
{
  "success": true,
  "message": "Pago procesado exitosamente",
  "data": {
    "id_reserva_pago": 457,
    "monto": 109305.00,
    "moneda": {
      "codigo": "CRC",
      "nombre": "Colón Costarricense"
    },
    "tipo_cambio": 520.50,
    "tipo_cambio_formateado": "1 USD = 520.500000 CRC",
    "monto_usd": 210.00,
    "metodo_pago": "Tarjeta de Crédito",
    "estado_pago": "Completado",
    "referencia": "REF-002",
    "notas": "Pago del saldo restante en colones",
    "fecha_pago": "2025-10-15 11:00:00"
  },
  "reserva": {
    "id_reserva": 123,
    "id_estado_res": 2,
    "estado": "Confirmada",
    "total_monto_reserva": 300.00,
    "monto_pagado": 300.00,
    "monto_pendiente": 0.00,
    "pago_completo": true,
    "porcentaje_pagado": 100.0
  }
}
```

### 2.6 Procesar Pago en Euros (EUR)

```http
POST /api/reservas/124/pagos
Content-Type: application/json

{
  "codigo_moneda": "EUR",
  "id_metodo_pago": 2,
  "monto": 92.00,
  "id_estado_pago": 2,
  "referencia": "EUR-PAYMENT-001",
  "notas": "Pago parcial en euros"
}
```

**Cálculo de conversión:**
- Monto pagado: 92.00 EUR
- Tipo de cambio: 1 USD = 0.92 EUR
- Conversión a USD: 92.00 ÷ 0.92 = 100.00 USD

**Respuesta esperada:**
```json
{
  "success": true,
  "message": "Pago procesado exitosamente",
  "data": {
    "id_reserva_pago": 458,
    "monto": 92.00,
    "moneda": {
      "codigo": "EUR",
      "nombre": "Euro"
    },
    "tipo_cambio": 0.92,
    "tipo_cambio_formateado": "1 USD = 0.920000 EUR",
    "monto_usd": 100.00,
    "metodo_pago": "Transferencia Bancaria",
    "estado_pago": "Completado",
    "referencia": "EUR-PAYMENT-001",
    "fecha_pago": "2025-10-15 11:30:00"
  },
  "reserva": {
    "id_reserva": 124,
    "total_monto_reserva": 450.00,
    "monto_pagado": 100.00,
    "monto_pendiente": 350.00,
    "pago_completo": false,
    "porcentaje_pagado": 22.22
  }
}
```

### 2.7 Consultar Estado de Pagos (Multi-moneda)

```http
GET /api/reservas/123/pagos
```

**Respuesta esperada:**
```json
{
  "success": true,
  "data": {
    "reserva_id": 123,
    "total_reserva": 300.00,
    "monto_pagado": 300.00,
    "monto_pendiente": 0.00,
    "pago_completo": true,
    "porcentaje_pagado": 100,
    "pagos": [
      {
        "id_reserva_pago": 456,
        "monto": 90.00,
        "moneda": {
          "codigo": "USD",
          "nombre": "Dólar Estadounidense"
        },
        "tipo_cambio": 1.0,
        "monto_usd": 90.00,
        "fecha": "2025-10-15 10:30:00",
        "metodo_pago": "Tarjeta de Crédito",
        "estado": "Completado",
        "referencia": "REF-001",
        "notas": "Pago inicial del 30% en dólares"
      },
      {
        "id_reserva_pago": 457,
        "monto": 109305.00,
        "moneda": {
          "codigo": "CRC",
          "nombre": "Colón Costarricense"
        },
        "tipo_cambio": 520.50,
        "monto_usd": 210.00,
        "fecha": "2025-10-15 11:00:00",
        "metodo_pago": "Tarjeta de Crédito",
        "estado": "Completado",
        "referencia": "REF-002",
        "notas": "Pago del saldo restante en colones"
      }
    ]
  }
}
```

**Notas importantes:**
- `total_reserva`, `monto_pagado` y `monto_pendiente` siempre están en USD
- Cada pago muestra el `monto` en la moneda original
- Se guarda el `tipo_cambio` aplicado al momento del pago
- `monto_usd` muestra la conversión a dólares
- Los totales de la reserva suman los `monto_usd` de todos los pagos

---

## 3. Cancelaciones con Políticas

### 3.1 Preview de Cancelación (+30 días - 100% reembolso)

```http
GET /api/reservas/123/cancelacion/preview
```

**Parámetros de prueba:**
- Reserva con `fecha_llegada`: "2025-12-01"
- Fecha actual: "2025-10-14"
- Días de anticipación: 48 días

**Respuesta esperada:**
```json
{
  "success": true,
  "data": {
    "puede_cancelar": true,
    "dias_anticipacion": 48,
    "politica_aplicada": {
      "id_politica": 1,
      "nombre": "Cancelación +30 días",
      "descripcion": "Reembolso del 100% por cancelación con más de 30 días de anticipación"
    },
    "monto_pagado": 300.00,
    "reembolso": 300.00,
    "penalidad": 0.00,
    "porcentaje_reembolso": 100
  }
}
```

### 3.2 Preview de Cancelación (15-30 días - 50% reembolso)

**Parámetros de prueba:**
- Reserva con `fecha_llegada`: "2025-11-01"
- Fecha actual: "2025-10-14"
- Días de anticipación: 18 días

**Respuesta esperada:**
```json
{
  "success": true,
  "data": {
    "puede_cancelar": true,
    "dias_anticipacion": 18,
    "politica_aplicada": {
      "id_politica": 2,
      "nombre": "Cancelación 15-30 días",
      "descripcion": "Reembolso del 50% por cancelación entre 15-30 días de anticipación"
    },
    "monto_pagado": 300.00,
    "reembolso": 150.00,
    "penalidad": 150.00,
    "porcentaje_reembolso": 50
  }
}
```

### 3.3 Preview de Cancelación (7-14 días - 25% reembolso)

**Parámetros de prueba:**
- Días de anticipación: 10 días

**Respuesta esperada:**
```json
{
  "success": true,
  "data": {
    "dias_anticipacion": 10,
    "politica_aplicada": {
      "id_politica": 3,
      "nombre": "Cancelación 7-14 días"
    },
    "monto_pagado": 300.00,
    "reembolso": 75.00,
    "penalidad": 225.00,
    "porcentaje_reembolso": 25
  }
}
```

### 3.4 Preview de Cancelación (<7 días - 0% reembolso)

**Parámetros de prueba:**
- Días de anticipación: 3 días

**Respuesta esperada:**
```json
{
  "success": true,
  "data": {
    "dias_anticipacion": 3,
    "politica_aplicada": {
      "id_politica": 4,
      "nombre": "Cancelación <7 días"
    },
    "monto_pagado": 300.00,
    "reembolso": 0.00,
    "penalidad": 300.00,
    "porcentaje_reembolso": 0
  }
}
```

### 3.5 Confirmar Cancelación

```http
POST /api/reservas/123/cancelar
Content-Type: application/json

{
  "motivo": "Cliente cambió de planes",
  "solicitar_reembolso": true
}
```

**Proceso automático:**
1. Calcula días de anticipación
2. Aplica política de cancelación
3. Calcula reembolso y penalidad
4. Cambia `id_estado_res` a 2 (Cancelada)
5. Libera habitaciones (Observer cambia estado a Disponible)
6. Genera transacción de reembolso si aplica

**Respuesta esperada:**
```json
{
  "success": true,
  "message": "Reserva cancelada exitosamente",
  "data": {
    "id_reserva": 123,
    "estado_anterior": "Confirmada",
    "estado_actual": "Cancelada",
    "fecha_cancelacion": "2025-10-14",
    "dias_anticipacion": 18,
    "politica": "Cancelación 15-30 días",
    "monto_pagado": 300.00,
    "reembolso": 150.00,
    "penalidad": 150.00,
    "habitaciones_liberadas": [1]
  }
}
```

---

## 4. Extensión de Estadía

### 4.1 Extender en la Misma Habitación (disponible)

```http
POST /api/reservas/123/extender
Content-Type: application/json

{
  "id_reserva_habitacion": 789,
  "noches_adicionales": 2,
  "nueva_fecha_salida": "2025-11-06"
}
```

**Validaciones:**
- ✅ Verificar que la habitación esté disponible para las fechas adicionales
- ✅ No hay reservas conflictivas en ese período
- ✅ Calcular costo adicional

**Respuesta esperada (exitosa):**
```json
{
  "success": true,
  "message": "Estadía extendida exitosamente en la misma habitación",
  "data": {
    "tipo_extension": "misma_habitacion",
    "id_reserva_habitacion": 789,
    "habitacion": {
      "id_habitacion": 1,
      "nombre": "Habitación Deluxe",
      "numero": "101"
    },
    "fecha_salida_original": "2025-11-04",
    "nueva_fecha_salida": "2025-11-06",
    "noches_adicionales": 2,
    "tarifa_noche": 100.00,
    "monto_adicional": 200.00,
    "nuevo_total_reserva": 500.00
  }
}
```

### 4.2 Extender con Cambio de Habitación (no disponible)

```http
POST /api/reservas/123/extender
Content-Type: application/json

{
  "id_reserva_habitacion": 789,
  "noches_adicionales": 3,
  "nueva_fecha_salida": "2025-11-07"
}
```

**Escenario:** La habitación actual está reservada después de la fecha de salida.

**Respuesta esperada:**
```json
{
  "success": false,
  "message": "La habitación actual no está disponible. Se encontraron alternativas.",
  "data": {
    "tipo_extension": "requiere_cambio",
    "habitacion_actual": {
      "id_habitacion": 1,
      "nombre": "Habitación Deluxe",
      "disponible": false,
      "fecha_conflicto": "2025-11-05"
    },
    "habitaciones_alternativas": [
      {
        "id_habitacion": 2,
        "nombre": "Habitación Superior",
        "numero": "102",
        "tipo": "Superior",
        "tarifa_noche": 120.00,
        "disponible_desde": "2025-11-04",
        "disponible_hasta": "2025-11-10",
        "monto_adicional": 360.00
      },
      {
        "id_habitacion": 3,
        "nombre": "Habitación Standard",
        "numero": "103",
        "tipo": "Standard",
        "tarifa_noche": 90.00,
        "disponible_desde": "2025-11-04",
        "disponible_hasta": "2025-11-15",
        "monto_adicional": 270.00
      }
    ]
  }
}
```

### 4.3 Confirmar Extensión con Cambio de Habitación

```http
POST /api/reservas/123/extender/confirmar
Content-Type: application/json

{
  "id_reserva_habitacion_original": 789,
  "id_habitacion_nueva": 2,
  "noches_adicionales": 3,
  "nueva_fecha_salida": "2025-11-07",
  "tarifa_noche": 120.00
}
```

**Proceso automático:**
1. Mantiene la reserva original hasta `fecha_salida_original`
2. Crea nueva `ReservaHabitacion` para la habitación alternativa
3. Actualiza el monto total de la reserva
4. Genera notificación de cambio de habitación

**Respuesta esperada:**
```json
{
  "success": true,
  "message": "Extensión confirmada con cambio de habitación",
  "data": {
    "tipo_extension": "cambio_habitacion",
    "reserva_original": {
      "id_reserva_habitacion": 789,
      "id_habitacion": 1,
      "fecha_salida": "2025-11-04"
    },
    "nueva_reserva_habitacion": {
      "id_reserva_habitacion": 790,
      "id_habitacion": 2,
      "nombre": "Habitación Superior",
      "numero": "102",
      "fecha_llegada": "2025-11-04",
      "fecha_salida": "2025-11-07",
      "noches": 3,
      "tarifa_noche": 120.00,
      "subtotal": 360.00
    },
    "nuevo_total_reserva": 660.00,
    "monto_adicional_cobrar": 360.00
  }
}
```

---

## 5. Códigos de Reserva

### 5.1 Buscar Reserva por Código

```http
GET /api/reservas/buscar?codigo=TCA4-ZJJY
```

**O sin guiones:**

```http
GET /api/reservas/buscar?codigo=TCA4ZJJY
```

**Respuesta esperada:**
```json
{
  "success": true,
  "data": {
    "id_reserva": 123,
    "codigo_reserva": "TCA4ZJJY",
    "codigo_formateado": "TCA4-ZJJY",
    "cliente": {
      "id_cliente": 1,
      "nombre": "Juan",
      "apellido": "Pérez",
      "email": "juan@example.com"
    },
    "estado": "Confirmada",
    "fecha_llegada": "2025-11-01",
    "fecha_salida": "2025-11-04",
    "total_monto_reserva": 300.00,
    "monto_pagado": 300.00,
    "pago_completo": true
  }
}
```

### 5.2 Listar Todas las Reservas (incluye códigos)

```http
GET /api/reservas
```

**Respuesta esperada:**
```json
{
  "success": true,
  "data": [
    {
      "id_reserva": 123,
      "codigo_reserva": "TCA4ZJJY",
      "codigo_formateado": "TCA4-ZJJY",
      "cliente": "Juan Pérez",
      "estado": "Confirmada",
      "total_monto_reserva": 300.00
    },
    {
      "id_reserva": 124,
      "codigo_reserva": "9KL3MNCB",
      "codigo_formateado": "9KL3-MNCB",
      "cliente": "María González",
      "estado": "Pendiente",
      "total_monto_reserva": 450.00
    }
  ]
}
```

### 5.3 Obtener Estadísticas del Sistema de Códigos

```http
GET /api/reservas/codigos/estadisticas
```

**Implementación sugerida en el controlador:**
```php
public function estadisticasCodigos()
{
    $service = app(\App\Services\CodigoReservaService::class);
    return response()->json([
        'success' => true,
        'data' => $service->obtenerEstadisticas()
    ]);
}
```

**Respuesta esperada:**
```json
{
  "success": true,
  "data": {
    "longitud_actual": 8,
    "caracteres_disponibles": "23456789ABCDEFGHJKLMNPQRSTUVWXYZ",
    "total_caracteres": 32,
    "max_combinaciones_posibles": "1,099,511,627,776",
    "codigos_generados": 124,
    "porcentaje_uso": "0.00001%",
    "proxima_longitud_en": 10,
    "umbral_cambio": 879609302221,
    "codigos_disponibles": "1,099,511,627,652"
  }
}
```

---

## 6. Estados de Habitaciones

### 6.1 Verificar Liberación Automática al Cancelar

**Paso 1:** Crear una reserva
```http
POST /api/reservas
{
  "id_cliente": 1,
  "habitaciones": [
    {
      "id_habitacion": 5,
      "fecha_llegada": "2025-11-10",
      "fecha_salida": "2025-11-12",
      "adultos": 2,
      "tarifa_noche": 100.00
    }
  ]
}
```

**Paso 2:** Verificar estado de la habitación
```http
GET /api/habitaciones/5
```

**Respuesta:**
```json
{
  "id_habitacion": 5,
  "nombre": "Suite Presidencial",
  "id_estado_hab": 2,
  "estado": "Ocupada"
}
```

**Paso 3:** Cancelar la reserva
```http
POST /api/reservas/125/cancelar
{
  "motivo": "Prueba de liberación automática"
}
```

**Paso 4:** Verificar que la habitación se liberó automáticamente
```http
GET /api/habitaciones/5
```

**Respuesta esperada:**
```json
{
  "id_habitacion": 5,
  "nombre": "Suite Presidencial",
  "id_estado_hab": 1,
  "estado": "Disponible"
}
```

### 6.2 Verificar que Habitaciones en Mantenimiento NO se Liberan

**Paso 1:** Poner habitación en mantenimiento
```http
PUT /api/habitaciones/5
{
  "id_estado_hab": 5
}
```

**Paso 2:** Cancelar reserva con esa habitación
```http
POST /api/reservas/126/cancelar
{
  "motivo": "Prueba con habitación en mantenimiento"
}
```

**Paso 3:** Verificar que la habitación sigue en mantenimiento
```http
GET /api/habitaciones/5
```

**Respuesta esperada:**
```json
{
  "id_habitacion": 5,
  "nombre": "Suite Presidencial",
  "id_estado_hab": 5,
  "estado": "Mantenimiento"
}
```

---

## 🧪 Scripts de Prueba con cURL

### Crear Reserva con Código Autogenerado
```bash
curl -X POST http://localhost:8000/api/reservas \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "id_cliente": 1,
    "id_estado_res": 1,
    "id_tipo_res": 1,
    "id_origen_res": 1,
    "noches": 3,
    "habitaciones": [{
      "id_habitacion": 1,
      "fecha_llegada": "2025-11-01",
      "fecha_salida": "2025-11-04",
      "adultos": 2,
      "ninos": 0,
      "bebes": 0,
      "tarifa_noche": 100.00
    }]
  }'
```

### Procesar Pago Parcial
```bash
curl -X POST http://localhost:8000/api/reservas/123/pagos \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "id_metodo_pago": 1,
    "monto": 90.00,
    "id_estado_pago": 4,
    "referencia": "REF-001"
  }'
```

### Preview de Cancelación
```bash
curl -X GET http://localhost:8000/api/reservas/123/cancelacion/preview \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Buscar por Código
```bash
curl -X GET "http://localhost:8000/api/reservas/buscar?codigo=TCA4-ZJJY" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 📝 Notas Importantes

1. **Códigos de Reserva:**
   - Los códigos se generan automáticamente al crear una reserva
   - Las reservas existentes NO tienen código (valor NULL)
   - Los códigos excluyen caracteres confusos: 0, O, I, 1, l
   - Longitud inicial: 8 caracteres
   - Crece automáticamente cuando se alcanza el 80% de capacidad

2. **Sistema de Pagos Multi-moneda:**
   - **Moneda base:** USD (todos los precios de habitaciones)
   - **Monedas soportadas:** 16 monedas (USD, CRC, EUR, GBP, CAD, MXN, JPY, CNY, BRL, ARS, COP, CLP, PEN, CHF, AUD, NZD)
   - **Conversión automática:** API exchangerate-api.com actualizada diariamente
   - **Cache:** Tipos de cambio se cachean 12 horas
   - **Almacenamiento:** Cada pago guarda `monto` (original), `tipo_cambio`, y `monto_usd` (convertido)
   - **Totales:** Siempre calculados en USD sumando `monto_usd`
   - **Mínimo 30%:** Para confirmar reserva
   - **Estado automático:** Cambia de "Pendiente" a "Confirmada" al alcanzar 30%
   - **Observers:** Actualizan montos en tiempo real

3. **Políticas de Cancelación:**
   - Se calculan automáticamente según días de anticipación
   - 4 políticas predefinidas (seeder ya ejecutado)
   - Las habitaciones se liberan automáticamente (excepto en mantenimiento)
   - Reembolsos calculados sobre monto_pagado en USD

4. **Extensión de Estadía:**
   - Verifica disponibilidad automáticamente
   - Ofrece alternativas si la habitación actual no está disponible
   - Calcula costos adicionales según tarifas actuales

5. **Validaciones de Negocio:**
   - Constraints a nivel de base de datos
   - Validaciones a nivel de Request
   - Observers para lógica automática
   - Validación de monedas soportadas

6. **API de Tipos de Cambio:**
   - **Proveedor:** exchangerate-api.com (gratis)
   - **Endpoint:** https://api.exchangerate-api.com/v4/latest/USD
   - **Fallback:** Tasas predefinidas si falla API
   - **Timeout:** 10 segundos máximo
   - **Cache:** 12 horas (Laravel Cache)

---

## 🔍 Verificar que Todo Funciona

**Checklist de pruebas:**

- [ ] Crear reserva nueva (debe tener código autogenerado)
- [ ] Verificar que `codigo_formateado` tiene guion cada 4 caracteres
- [ ] Intentar crear reserva con capacidad excedida (debe fallar)
- [ ] Intentar crear reserva con fechas inválidas (debe fallar)
- [ ] Procesar pago del 30% (debe cambiar estado a Confirmada)
- [ ] Procesar pago completo (debe marcar pago_completo = true)
- [ ] Preview de cancelación con diferentes anticipaciones
- [ ] Cancelar reserva y verificar liberación de habitaciones
- [ ] Intentar extender estadía en misma habitación
- [ ] Verificar alternativas cuando habitación no disponible
- [ ] Buscar reserva por código (con y sin guiones)
- [ ] Verificar que habitaciones en mantenimiento NO se liberan

---

## 📚 Recursos Adicionales

**Modelos principales:**
- [Reserva.php](app/Models/reserva/Reserva.php)
- [ReservaHabitacion.php](app/Models/reserva/ReservaHabitacion.php)
- [EstadoReserva.php](app/Models/reserva/EstadoReserva.php)
- [PoliticaCancelacion.php](app/Models/reserva/PoliticaCancelacion.php)

**Servicios:**
- [CodigoReservaService.php](app/Services/CodigoReservaService.php)
- [ExtensionEstadiaService.php](app/Services/ExtensionEstadiaService.php)

**Observers:**
- [ReservaObserver.php](app/Observers/ReservaObserver.php)
- [ReservaPagoObserver.php](app/Observers/ReservaPagoObserver.php)

**Requests de Validación:**
- [StoreReservaRequest.php](app/Http/Requests/reserva/StoreReservaRequest.php)
- [ProcesarPagoRequest.php](app/Http/Requests/reserva/ProcesarPagoRequest.php)
- [ExtenderEstadiaRequest.php](app/Http/Requests/reserva/ExtenderEstadiaRequest.php)

---

**Generado:** 2025-10-14
**Sistema:** Backend-SistemaHotelero