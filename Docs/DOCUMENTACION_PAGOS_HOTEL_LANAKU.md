# 📘 Documentación de Sistema de Pagos - Hotel Lanaku

## 🎯 Descripción General

Sistema de procesamiento de pagos con soporte para múltiples divisas (USD, CRC, EUR) con conversión automática según tipo de cambio del día. Implementa los requisitos específicos del Hotel Lanaku para pagos flexibles y transparentes.

---

## 💰 Divisas y Conversiones

### Divisas Soportadas (Principales)
- **USD** - Dólar Estadounidense (Moneda base del sistema)
- **CRC** - Colón Costarricense
- **EUR** - Euro

### Características
- ✅ Conversión automática según tipo de cambio del día
- ✅ Cache de tipos de cambio (12 horas)
- ✅ Fallback a valores aproximados si la API externa falla
- ✅ Transparencia total: muestra monto en divisa elegida Y equivalente en USD
- ✅ Recalculo automático del tipo de cambio en cada pago

---

## 💳 Métodos de Pago

| Código | Nombre | Descripción | Autorización |
|--------|--------|-------------|--------------|
| **CA** | Efectivo | Pago en efectivo en cualquier divisa | No |
| **VI** | Visa/Mastercard | Tarjetas de crédito/débito | No |
| **AX** | American Express | Tarjetas American Express | No |
| **TB** | Transferencia Bancaria | Transferencias nacionales/internacionales | No |
| **CR** | Crédito | Pago diferido (solo VIP/Agencias) | **Sí** |

---

## 🔄 Flujos de Pago

### 1. Pago Inicial (30%)

**Endpoint:** `POST /api/pagos/inicial`

Cobra automáticamente el 30% del total de la reserva para confirmarla.

**Request Body:**
```json
{
  "id_reserva": 123,
  "codigo_moneda": "CRC",
  "codigo_metodo_pago": "VI",
  "referencia": "VISA-4532",
  "notas": "Pago inicial por cliente"
}
```

**Response Success (201):**
```json
{
  "success": true,
  "message": "Pago inicial procesado exitosamente.",
  "data": {
    "pago": {
      "id_reserva_pago": 456,
      "id_reserva": 123,
      "monto": 156000.00,
      "tipo_cambio": 520.00,
      "monto_usd": 300.00,
      "fecha_pago": "2025-10-27 10:30:00",
      "moneda": {
        "codigo": "CRC",
        "nombre": "Colón Costarricense",
        "simbolo": "₡"
      },
      "metodoPago": {
        "codigo": "VI",
        "nombre": "Visa / Mastercard"
      }
    },
    "detalles_conversion": {
      "monto_pagado": 156000.00,
      "moneda_pago": "CRC",
      "simbolo": "₡",
      "tipo_cambio": 520.00,
      "monto_usd": 300.00,
      "equivalente": "₡156,000.00 = $300.00 USD"
    },
    "reserva": {
      "total_reserva": 1000.00,
      "monto_pagado": 300.00,
      "monto_pendiente": 700.00,
      "porcentaje_pagado": 30,
      "pago_completo": false
    }
  }
}
```

---

### 2. Pago Restante

**Endpoint:** `POST /api/pagos/restante`

Procesa el pago del saldo pendiente. **IMPORTANTE:** Recalcula el tipo de cambio al momento del pago.

**Request Body:**
```json
{
  "id_reserva": 123,
  "codigo_moneda": "EUR",
  "codigo_metodo_pago": "AX",
  "referencia": "AMEX-8765",
  "notas": "Pago final"
}
```

**Response Success (201):**
```json
{
  "success": true,
  "message": "Pago del saldo restante procesado exitosamente.",
  "data": {
    "pago": {
      "id_reserva_pago": 457,
      "monto": 644.00,
      "tipo_cambio": 0.92,
      "monto_usd": 700.00,
      "fecha_pago": "2025-10-28 15:45:00"
    },
    "detalles_conversion": {
      "equivalente": "€644.00 = $700.00 USD"
    },
    "reserva": {
      "total_reserva": 1000.00,
      "monto_pagado": 1000.00,
      "monto_pendiente": 0.00,
      "porcentaje_pagado": 100,
      "pago_completo": true
    },
    "tipo_cambio_info": {
      "fecha": "2025-10-28",
      "tasa": 0.92,
      "moneda": "EUR",
      "nota": "Tipo de cambio recalculado al momento del pago"
    }
  }
}
```

---

### 3. Pago Completo (100%)

**Endpoint:** `POST /api/pagos/completo`

Procesa el 100% de la reserva en una sola transacción. Si ya existen pagos previos, automáticamente cobra solo la diferencia.

**Request Body:**
```json
{
  "id_reserva": 124,
  "codigo_moneda": "USD",
  "codigo_metodo_pago": "TB",
  "referencia": "TRANSFER-2024-001",
  "notas": "Pago completo por transferencia"
}
```

**Response Success (201):**
```json
{
  "success": true,
  "message": "Pago completo procesado exitosamente.",
  "data": {
    "pago": {
      "id_reserva_pago": 458,
      "monto": 1500.00,
      "tipo_cambio": 1.0,
      "monto_usd": 1500.00
    },
    "reserva": {
      "total_reserva": 1500.00,
      "monto_pagado": 1500.00,
      "monto_pendiente": 0.00,
      "porcentaje_pagado": 100,
      "pago_completo": true
    }
  }
}
```

---

## 🛠️ Endpoints Auxiliares

### Obtener Divisas Principales

**Endpoint:** `GET /api/pagos/divisas-principales`

Retorna las divisas principales del hotel (USD, CRC, EUR) con sus tasas actuales.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "codigo": "USD",
      "nombre": "Dólar Estadounidense",
      "tasa": 1.0,
      "simbolo": "$"
    },
    {
      "codigo": "CRC",
      "nombre": "Colón Costarricense",
      "tasa": 520.00,
      "simbolo": "₡"
    },
    {
      "codigo": "EUR",
      "nombre": "Euro",
      "tasa": 0.92,
      "simbolo": "€"
    }
  ]
}
```

---

### Obtener Métodos de Pago

**Endpoint:** `GET /api/pagos/metodos`

Lista todos los métodos de pago activos.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "codigo": "CA",
      "nombre": "Efectivo",
      "descripcion": "Pago en efectivo (Cash). Puede realizarse en cualquier divisa...",
      "requiere_autorizacion": false
    },
    {
      "id": 2,
      "codigo": "VI",
      "nombre": "Visa / Mastercard",
      "descripcion": "Pago con tarjeta de crédito o débito Visa o Mastercard.",
      "requiere_autorizacion": false
    },
    {
      "id": 5,
      "codigo": "CR",
      "nombre": "Crédito",
      "descripcion": "Pago diferido a una fecha posterior al check-out...",
      "requiere_autorizacion": true
    }
  ]
}
```

---

### Obtener Tipo de Cambio

**Endpoint:** `GET /api/pagos/tipo-cambio/{moneda}`

Obtiene el tipo de cambio actual para una moneda específica.

**Ejemplo:** `GET /api/pagos/tipo-cambio/CRC`

**Response:**
```json
{
  "success": true,
  "data": {
    "moneda": "CRC",
    "tipo_cambio": 520.00,
    "fecha": "2025-10-27",
    "formula": "1 USD = 520.00 CRC"
  }
}
```

---

### Calcular Precio en 3 Divisas

**Endpoint:** `POST /api/pagos/calcular-precio`

Calcula automáticamente un precio en USD, CRC y EUR.

**Request Body:**
```json
{
  "monto_usd": 150.00
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "usd": {
      "monto": 150.00,
      "simbolo": "$",
      "codigo": "USD",
      "nombre": "Dólar Estadounidense",
      "formato": "$150.00"
    },
    "crc": {
      "monto": 78000.00,
      "simbolo": "₡",
      "codigo": "CRC",
      "nombre": "Colón Costarricense",
      "tipo_cambio": 520.00,
      "formato": "₡78,000.00"
    },
    "eur": {
      "monto": 138.00,
      "simbolo": "€",
      "codigo": "EUR",
      "nombre": "Euro",
      "tipo_cambio": 0.92,
      "formato": "€138.00"
    }
  }
}
```

---

## 🔐 Autorización para Crédito

Cuando se usa el método de pago **CR (Crédito)**, es obligatorio proporcionar el campo `autorizado_por`:

```json
{
  "id_reserva": 123,
  "codigo_moneda": "USD",
  "codigo_metodo_pago": "CR",
  "autorizado_por": "Juan Pérez - Gerente",
  "notas": "Agencia de viajes XYZ - Crédito 30 días"
}
```

Si no se proporciona, el sistema retornará un error:

```json
{
  "success": false,
  "message": "El método de pago 'Crédito' requiere autorización. Por favor, proporcione 'autorizado_por'."
}
```

---

## ❌ Manejo de Errores

### Errores Comunes

**1. Reserva ya pagada completamente**
```json
{
  "success": false,
  "message": "La reserva ya está completamente pagada."
}
```

**2. No hay saldo pendiente**
```json
{
  "success": false,
  "message": "La reserva no tiene saldo pendiente."
}
```

**3. Reserva cancelada/finalizada**
```json
{
  "success": false,
  "message": "No se puede procesar pagos para una reserva cancelada o finalizada."
}
```

**4. Validación de datos**
```json
{
  "success": false,
  "errors": {
    "codigo_moneda": ["El campo codigo_moneda debe tener 3 caracteres."],
    "codigo_metodo_pago": ["El código de método de pago seleccionado no existe."]
  }
}
```

---

## 📊 Flujo Completo de Ejemplo

### Escenario: Reserva de $1,000 USD

#### 1. **Cliente hace reserva**
- Total: $1,000 USD
- Monto pagado: $0
- Monto pendiente: $1,000

#### 2. **Pago inicial 30% en CRC**
```bash
POST /api/pagos/inicial
{
  "id_reserva": 123,
  "codigo_moneda": "CRC",
  "codigo_metodo_pago": "VI"
}
```

**Resultado:**
- Cobra: ₡156,000 CRC (= $300 USD al TC 520)
- Monto pagado: $300 USD
- Monto pendiente: $700 USD

#### 3. **Cliente paga saldo restante en EUR (días después)**
```bash
POST /api/pagos/restante
{
  "id_reserva": 123,
  "codigo_moneda": "EUR",
  "codigo_metodo_pago": "AX"
}
```

**Resultado:**
- Sistema recalcula TC actual: 1 USD = 0.92 EUR
- Cobra: €644 EUR (= $700 USD al nuevo TC)
- Monto pagado: $1,000 USD
- Monto pendiente: $0 USD
- **Reserva completamente pagada** ✅

---

## 🧪 Testing con Postman/Insomnia

### Headers requeridos:
```
Content-Type: application/json
Accept: application/json
```

### Ejemplo de secuencia de prueba:

1. **Obtener métodos de pago disponibles**
   ```
   GET /api/pagos/metodos
   ```

2. **Ver tipo de cambio actual para CRC**
   ```
   GET /api/pagos/tipo-cambio/CRC
   ```

3. **Procesar pago inicial**
   ```
   POST /api/pagos/inicial
   Body: { "id_reserva": 1, "codigo_moneda": "CRC", "codigo_metodo_pago": "CA" }
   ```

4. **Ver estado de la reserva**
   ```
   GET /api/reservas/1
   ```

5. **Completar pago restante**
   ```
   POST /api/pagos/restante
   Body: { "id_reserva": 1, "codigo_moneda": "USD", "codigo_metodo_pago": "TB" }
   ```

---

## 📝 Notas Importantes

1. **Todos los montos internos se almacenan en USD** para consistencia
2. **El tipo de cambio se recalcula en cada transacción** (no se reutiliza el de pagos anteriores)
3. **Cache de tipos de cambio:** 12 horas para optimizar performance
4. **Fallback:** Si la API externa falla, usa valores aproximados predefinidos
5. **Transacciones atómicas:** Todos los pagos usan transacciones de base de datos
6. **Auditoría completa:** Cada pago registra: monto, moneda, tipo de cambio, fecha, usuario

---

## 🏗️ Arquitectura Técnica

### Modelos
- `MetodoPago` - Métodos de pago con códigos (CA, VI, AX, TB, CR)
- `Moneda` - Catálogo de monedas soportadas
- `ReservaPago` - Registro de cada pago realizado
- `Reserva` - Entidad principal con montos totales

### Servicios
- `ExchangeRateService` - Gestión de tipos de cambio y conversiones

### Controladores
- `PagoController` - Procesamiento de pagos y endpoints auxiliares

### Base de Datos
```sql
metodo_pago:
- id_metodo_pago
- codigo (CA, VI, AX, TB, CR)
- nombre
- descripcion
- activo
- requiere_autorizacion

reserva_pago:
- id_reserva_pago
- id_reserva
- id_metodo_pago
- id_moneda
- monto (en moneda seleccionada)
- tipo_cambio (aplicado)
- monto_usd (convertido)
- referencia
- notas
- fecha_pago
```

---

## ✅ Checklist de Implementación

- [x] ExchangeRateService con soporte USD, CRC, EUR
- [x] Modelo MetodoPago con códigos específicos
- [x] Seeder de métodos de pago (5 métodos)
- [x] Modelo ReservaPago actualizado
- [x] PagoController con 3 flujos principales
- [x] Rutas API configuradas
- [x] Validaciones de datos
- [x] Manejo de errores
- [x] Conversión automática de divisas
- [x] Recalculo de tipo de cambio en cada pago
- [x] Autorización para crédito
- [x] Documentación completa

---

## 🚀 Próximos Pasos

- [ ] Implementar políticas de cancelación del Hotel Lanaku
- [ ] Agregar reportes de pagos por divisa
- [ ] Integración con pasarelas de pago reales
- [ ] Dashboard de tipos de cambio históricos
- [ ] Notificaciones por email de pagos procesados

---

**Desarrollado para Hotel Lanaku** 🏨
**Fecha:** Octubre 2025
**Versión:** 1.0
