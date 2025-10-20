# 📋 Catálogos y Seeders del Sistema Hotelero

Este documento explica todos los catálogos del sistema, cómo llenarlos y cómo usar los seeders.

---

## 🎯 RESUMEN DE CATÁLOGOS

El sistema tiene **75+ registros de catálogo** distribuidos en:

| Categoría | Tablas | Registros | Seeder |
|-----------|--------|-----------|--------|
| **Generales** | 6 | 40 | `CatalogosGeneralesSeeder` |
| **Pagos** | 4 | 35 | `CatalogosPagoSeeder` |
| **Políticas** | 1 | 4 | `PoliticaCancelacionSeeder` |
| **TOTAL** | **11** | **~79** | - |

---

## 📦 1. CATÁLOGOS GENERALES

### 1.1 Tipos de Documento (tabla: `tipo_doc`)

**Propósito:** Tipos de identificación para clientes

| ID | Nombre |
|----|--------|
| 1 | Cédula Nacional |
| 2 | Pasaporte |
| 3 | DIMEX (Extranjero) |
| 4 | Cédula Jurídica |
| 5 | Licencia de Conducir |

**Uso en el sistema:**
```json
{
  "id_tipo_doc": 1,
  "num_doc": "1-2345-6789"
}
```

---

### 1.2 Fuentes de Reserva (tabla: `fuentes`)

**Propósito:** Canal u origen de la reserva

| ID | Nombre | Código |
|----|--------|--------|
| 1 | Sitio Web Oficial | WEB |
| 2 | Recepción (Walk-in) | WALKIN |
| 3 | Booking.com | BOOKING |
| 4 | Airbnb | AIRBNB |
| 5 | Expedia | EXPEDIA |
| 6 | Agencia de Viajes | AGENCY |
| 7 | Teléfono | PHONE |
| 8 | Email | EMAIL |
| 9 | Redes Sociales | SOCIAL |
| 10 | Referido | REFERRAL |

**Uso en el sistema:**
```json
{
  "id_fuente": 1,
  "origen": "Reserva desde sitio web oficial"
}
```

---

### 1.3 Tipos de Habitación (tabla: `tipos_habitacion`)

**Propósito:** Categorías de habitaciones del hotel

| ID | Nombre | Descripción |
|----|--------|-------------|
| 1 | Individual | Habitación para una persona con cama individual |
| 2 | Doble | Habitación para dos personas con cama matrimonial o dos camas individuales |
| 3 | Triple | Habitación para tres personas |
| 4 | Suite | Habitación amplia con sala de estar separada |
| 5 | Suite Presidencial | Suite de lujo con múltiples habitaciones y servicios premium |
| 6 | Familiar | Habitación grande para familias con capacidad para 4-6 personas |
| 7 | Ejecutiva | Habitación con espacio de trabajo y servicios de negocios |
| 8 | Deluxe | Habitación de categoría superior con amenidades mejoradas |

**Uso en el sistema:**
```json
{
  "tipo_habitacion_id": 4,
  "nombre": "Suite 301",
  "capacidad": 4
}
```

---

### 1.4 Estados de Habitación (tabla: `estado_habitacions`)

**Propósito:** Estado actual de una habitación

| ID | Nombre | Descripción |
|----|--------|-------------|
| 1 | Disponible | Habitación lista para ser ocupada |
| 2 | Ocupada | Habitación actualmente en uso por un huésped |
| 3 | Sucia | Habitación que requiere limpieza |
| 4 | Limpia | Habitación limpia pero aún no disponible |
| 5 | Mantenimiento | Habitación fuera de servicio por mantenimiento |

**Transiciones:**
```
Disponible → Ocupada (reserva confirmada/check-in)
Ocupada → Sucia (check-out)
Sucia → Limpia (housekeeping limpia)
Limpia → Disponible (inspección aprobada)
Cualquiera → Mantenimiento (requiere reparación)
```

---

### 1.5 Estados de Reserva (tabla: `estado_reserva`)

**Propósito:** Estado del ciclo de vida de una reserva

| ID | Nombre | Terminal | Descripción |
|----|--------|----------|-------------|
| 1 | Pendiente | No | Reserva creada sin pago mínimo |
| 2 | Cancelada | Sí | Reserva cancelada |
| 3 | Confirmada | No | Reserva confirmada (pagó ≥30%) |
| 4 | Check-in | No | Cliente hizo check-in |
| 5 | Check-out | No | Cliente hizo check-out |
| 6 | No Show | Sí | Cliente no se presentó |
| 7 | En Espera | No | Reserva en lista de espera |
| 8 | Finalizada | Sí | Proceso completo |

**Transiciones permitidas:**
```
Pendiente → Confirmada, Cancelada, En Espera
En Espera → Confirmada, Cancelada
Confirmada → Cancelada, Check-in, No Show
Check-in → Check-out, Cancelada
Check-out → Finalizada
```

---

### 1.6 Estados de Estadía (tabla: `estado_estadia`)

**Propósito:** Estado de la estadía del huésped

| ID | Nombre |
|----|--------|
| 1 | Activa |
| 2 | Finalizada |
| 3 | Cancelada |
| 4 | En Proceso de Check-out |

---

## 💳 2. CATÁLOGOS DE PAGOS

### 2.1 Estados de Pago (tabla: `estado_pago`)

**Propósito:** Estado de un pago de reserva

| ID | Nombre | Uso |
|----|--------|-----|
| 1 | Pendiente | Pago iniciado pero no completado |
| 2 | Completado | Pago procesado exitosamente |
| 3 | Fallido | Pago rechazado o con error |
| 4 | Reembolsado | Dinero devuelto al cliente |
| 5 | Parcial | Pago parcial procesado |

**Ejemplo de uso:**
```json
{
  "id_estado_pago": 2,
  "monto": 135.00,
  "fecha_pago": "2025-10-15"
}
```

---

### 2.2 Tipos de Transacción (tabla: `tipo_transaccion`)

**Propósito:** Tipo de movimiento financiero

| ID | Nombre | Descripción |
|----|--------|-------------|
| 1 | Pago | Pago de reserva o servicios |
| 2 | Reembolso | Devolución de dinero al cliente |
| 3 | Cancelación | Cancelación de pago |
| 4 | Ajuste | Ajuste manual de pago |

---

### 2.3 Monedas (tabla: `moneda`)

**Propósito:** Monedas soportadas para pagos (sistema multi-moneda)

| ID | Código | Nombre |
|----|--------|--------|
| 1 | USD | Dólar Estadounidense |
| 2 | CRC | Colón Costarricense |
| 3 | EUR | Euro |
| 4 | GBP | Libra Esterlina |
| 5 | CAD | Dólar Canadiense |
| 6 | MXN | Peso Mexicano |
| 7 | JPY | Yen Japonés |
| 8 | CNY | Yuan Chino |
| 9 | BRL | Real Brasileño |
| 10 | ARS | Peso Argentino |
| 11 | COP | Peso Colombiano |
| 12 | CLP | Peso Chileno |
| 13 | PEN | Sol Peruano |
| 14 | CHF | Franco Suizo |
| 15 | AUD | Dólar Australiano |
| 16 | NZD | Dólar Neozelandés |

**Ejemplo de uso:**
```json
{
  "codigo_moneda": "CRC",
  "monto": 52050.00
}
```

**Sistema automáticamente:**
- Consulta tipo de cambio actual
- Convierte a USD (moneda base)
- Almacena: monto original, tipo_cambio, monto_usd

---

### 2.4 Métodos de Pago (tabla: `metodo_pago`)

**Propósito:** Formas de pago aceptadas

| ID | Nombre |
|----|--------|
| 1 | Tarjeta de Crédito |
| 2 | Tarjeta de Débito |
| 3 | Efectivo |
| 4 | Transferencia Bancaria |
| 5 | PayPal |
| 6 | SINPE Móvil |
| 7 | Depósito Bancario |
| 8 | Stripe |
| 9 | Mercado Pago |
| 10 | Cheque |

**Ejemplo de uso:**
```json
{
  "id_metodo_pago": 1,
  "referencia": "VISA-4532"
}
```

**NOTA:** La moneda ya NO va en método de pago, ahora va directamente en el pago.

---

## 🚫 3. POLÍTICAS DE CANCELACIÓN

**Propósito:** Políticas automáticas según días de anticipación

| ID | Nombre | Días Min | Días Max | Penalización |
|----|--------|----------|----------|--------------|
| 1 | Cancelación flexible (30+ días) | 30 | 9999 | 0% |
| 2 | Cancelación moderada (15-30 días) | 15 | 29 | 10% |
| 3 | Cancelación estricta (7-15 días) | 7 | 14 | 25% |
| 4 | Cancelación muy estricta (< 7 días) | 0 | 6 | 50% |

**Cálculo automático:**
```
Días de anticipación = fecha_llegada - fecha_cancelacion

Si días ≥ 30 → 0% penalización (100% reembolso)
Si días 15-29 → 10% penalización (90% reembolso)
Si días 7-14 → 25% penalización (75% reembolso)
Si días < 7 → 50% penalización (50% reembolso)
```

---

## ⚙️ CÓMO USAR LOS SEEDERS

### Paso 1: Ejecutar Todos los Seeders

```bash
php artisan db:seed
```

**Salida esperada:**
```
🌱 Iniciando seeders del sistema hotelero...
================================================
✅ Tipos de Documento insertados (5)
✅ Fuentes de Reserva insertadas (10)
✅ Tipos de Habitación insertados (8)
✅ Estados de Habitación insertados (5)
✅ Estados de Reserva insertados (8)
✅ Estados de Estadía insertados (4)
================================================
✅ TODOS LOS CATÁLOGOS GENERALES INSERTADOS
================================================
✅ Estados de pago insertados correctamente
✅ Tipos de transacción insertados correctamente
✅ Monedas insertadas correctamente (16 monedas)
✅ Métodos de pago insertados correctamente (10 métodos)
================================================
✅ SEEDERS COMPLETADOS EXITOSAMENTE
================================================
  Total de catálogos: ~60 registros
================================================
```

### Paso 2: Ejecutar Seeders Individuales

```bash
# Solo catálogos generales
php artisan db:seed --class=CatalogosGeneralesSeeder

# Solo catálogos de pagos
php artisan db:seed --class=CatalogosPagoSeeder

# Solo políticas de cancelación
php artisan db:seed --class=PoliticaCancelacionSeeder
```

### Paso 3: Refrescar y Repoblar (⚠️ BORRA TODO)

```bash
php artisan migrate:fresh --seed
```

**ADVERTENCIA:** Esto eliminará TODA la data y creará las tablas de nuevo.

---

## 📝 EJEMPLOS DE DATOS COMPLETOS

### Ejemplo 1: Crear Reserva Completa

```json
POST /api/reservas
{
  "id_cliente": 1,
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

**Datos de catálogo usados:**
- `id_estado_res: 1` → Pendiente
- `id_fuente: 1` → Sitio Web Oficial

---

### Ejemplo 2: Procesar Pago en Colones

```json
POST /api/reservas/123/pagos
{
  "codigo_moneda": "CRC",
  "id_metodo_pago": 1,
  "monto": 70267.50,
  "id_estado_pago": 2,
  "referencia": "VISA-4532",
  "notas": "Pago inicial 30%"
}
```

**Datos de catálogo usados:**
- `codigo_moneda: "CRC"` → Colón Costarricense (id: 2)
- `id_metodo_pago: 1` → Tarjeta de Crédito
- `id_estado_pago: 2` → Completado
- `id_tipo_transaccion: 1` → Pago (automático)

**Sistema automáticamente:**
1. Consulta tipo de cambio: 1 USD = 520.50 CRC
2. Convierte: 70,267.50 ÷ 520.50 = 135.00 USD
3. Almacena: monto=70267.50, tipo_cambio=520.50, monto_usd=135.00

---

### Ejemplo 3: Crear Cliente

```json
POST /api/clientes
{
  "nombre": "Juan",
  "apellido1": "Pérez",
  "apellido2": "García",
  "id_tipo_doc": 1,
  "num_doc": "1-2345-6789",
  "email": "juan.perez@email.com",
  "telefono": "8888-8888"
}
```

**Datos de catálogo usados:**
- `id_tipo_doc: 1` → Cédula Nacional

---

### Ejemplo 4: Crear Habitación

```json
POST /api/habitaciones
{
  "nombre": "Suite 301",
  "numero": "301",
  "tipo_habitacion_id": 4,
  "id_estado_hab": 1,
  "capacidad": 4,
  "tarifa_noche": 200.00
}
```

**Datos de catálogo usados:**
- `tipo_habitacion_id: 4` → Suite
- `id_estado_hab: 1` → Disponible

---

## 🔍 VERIFICAR DATOS INSERTADOS

### Consultar todos los estados de pago:

```bash
php artisan tinker
```

```php
DB::table('estado_pago')->get();
// Resultado:
// 1: Pendiente
// 2: Completado
// 3: Fallido
// 4: Reembolsado
// 5: Parcial
```

### Consultar todas las monedas:

```php
DB::table('moneda')->get(['id_moneda', 'codigo', 'nombre']);
```

### Consultar tipos de habitación:

```php
DB::table('tipos_habitacion')->get(['id_tipo_hab', 'nombre']);
```

---

## 🛠️ AGREGAR NUEVOS REGISTROS DE CATÁLOGO

### Opción 1: Crear Seeder Específico

```php
// database/seeders/NuevosCatalogosSeeder.php
public function run(): void
{
    DB::table('fuentes')->insert([
        'nombre' => 'TripAdvisor',
        'codigo' => 'TRIPADVISOR',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}
```

### Opción 2: Agregar Manualmente

```bash
php artisan tinker
```

```php
DB::table('fuentes')->insert([
    'nombre' => 'Google Hotels',
    'codigo' => 'GOOGLE',
    'created_at' => now(),
    'updated_at' => now(),
]);
```

### Opción 3: Por API (si tienes endpoints)

```http
POST /api/admin/fuentes
{
  "nombre": "Trivago",
  "codigo": "TRIVAGO"
}
```

---

## 📊 RESUMEN DE IDs IMPORTANTES

### Estados más usados:

```php
// Estados de Reserva
EstadoReserva::ESTADO_PENDIENTE = 1
EstadoReserva::ESTADO_CONFIRMADA = 3
EstadoReserva::ESTADO_CHECKIN = 4
EstadoReserva::ESTADO_CHECKOUT = 5
EstadoReserva::ESTADO_CANCELADA = 2

// Estados de Pago
EstadoPago::ESTADO_PENDIENTE = 1
EstadoPago::ESTADO_COMPLETADO = 2
EstadoPago::ESTADO_PARCIAL = 5
EstadoPago::ESTADO_REEMBOLSADO = 4

// Estados de Habitación
EstadoHabitacion::ESTADO_DISPONIBLE = 1
EstadoHabitacion::ESTADO_OCUPADA = 2
EstadoHabitacion::ESTADO_SUCIA = 3
EstadoHabitacion::ESTADO_MANTENIMIENTO = 5
```

---

## 📚 ARCHIVOS RELACIONADOS

- [`CatalogosGeneralesSeeder.php`](database/seeders/CatalogosGeneralesSeeder.php)
- [`CatalogosPagoSeeder.php`](database/seeders/CatalogosPagoSeeder.php)
- [`PoliticaCancelacionSeeder.php`](database/seeders/PoliticaCancelacionSeeder.php)
- [`DatabaseSeeder.php`](database/seeders/DatabaseSeeder.php)

---

**Fecha:** 2025-10-15
**Sistema:** Backend-SistemaHotelero
**Versión:** 1.0
