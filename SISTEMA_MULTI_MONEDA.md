# Sistema de Pagos Multi-moneda

## Resumen

El sistema de gestión hotelero ahora soporta pagos en múltiples monedas con conversión automática en tiempo real. Todos los precios base están en USD y se pueden aceptar pagos en 16 monedas diferentes.

## Características Principales

### 1. Monedas Soportadas

El sistema soporta las siguientes 16 monedas:

| Código | Nombre | Región |
|--------|--------|--------|
| USD | Dólar Estadounidense | América |
| CRC | Colón Costarricense | Costa Rica |
| EUR | Euro | Europa |
| GBP | Libra Esterlina | Reino Unido |
| CAD | Dólar Canadiense | Canadá |
| MXN | Peso Mexicano | México |
| JPY | Yen Japonés | Japón |
| CNY | Yuan Chino | China |
| BRL | Real Brasileño | Brasil |
| ARS | Peso Argentino | Argentina |
| COP | Peso Colombiano | Colombia |
| CLP | Peso Chileno | Chile |
| PEN | Sol Peruano | Perú |
| CHF | Franco Suizo | Suiza |
| AUD | Dólar Australiano | Australia |
| NZD | Dólar Neozelandés | Nueva Zelanda |

### 2. Conversión Automática

- **API Externa:** exchangerate-api.com
- **Actualización:** Diaria
- **Cache:** 12 horas (optimización de rendimiento)
- **Fallback:** Tasas predefinidas si la API falla
- **Timeout:** 10 segundos máximo

### 3. Almacenamiento de Datos

Cada pago registra:
- `monto`: Monto en la moneda original del pago
- `id_moneda`: Referencia a la moneda utilizada
- `tipo_cambio`: Tipo de cambio aplicado en el momento del pago
- `monto_usd`: Monto convertido a USD (usado para totales)
- `referencia`: Referencia del pago (opcional)
- `notas`: Notas adicionales (opcional)

## Arquitectura Técnica

### Archivos Creados/Modificados

#### 1. Servicio de Tipos de Cambio
**Archivo:** [`app/Services/ExchangeRateService.php`](app/Services/ExchangeRateService.php)

**Métodos principales:**
```php
obtenerTiposDeCambio(): array
obtenerTipoCambio(string $codigoMoneda): float
convertirDesdeUSD(float $montoUSD, string $monedaDestino): array
convertirAUSD(float $monto, string $monedaOrigen): float
convertir(float $monto, string $monedaOrigen, string $monedaDestino): array
obtenerMonedasSoportadas(): array
estaMonedaSoportada(string $codigoMoneda): bool
limpiarCache(): bool
```

**Características:**
- Cache de 12 horas usando Laravel Cache
- Manejo de errores con fallback
- Timeout de 10 segundos
- Soporte para 16 monedas

#### 2. Migración de Base de Datos
**Archivo:** [`database/migrations/2025_10_14_170137_add_currency_fields_to_reserva_pago_table.php`](database/migrations/2025_10_14_170137_add_currency_fields_to_reserva_pago_table.php)

**Campos agregados a `reserva_pago`:**
```sql
id_moneda BIGINT UNSIGNED NULL
tipo_cambio DECIMAL(12, 6) DEFAULT 1.000000
monto_usd DECIMAL(10, 2) NULL
referencia VARCHAR(100) NULL
notas TEXT NULL
```

#### 3. Modelo ReservaPago
**Archivo:** [`app/Models/reserva/ReservaPago.php`](app/Models/reserva/ReservaPago.php)

**Campos fillable actualizados:**
```php
'id_moneda', 'tipo_cambio', 'monto_usd', 'referencia', 'notas'
```

**Nueva relación:**
```php
public function moneda()
{
    return $this->belongsTo(\App\Models\catalago_pago\Moneda::class, 'id_moneda');
}
```

**Accessors agregados:**
```php
getMontoFormateadoAttribute(): string
getTipoCambioFormateadoAttribute(): string
```

#### 4. Request de Validación
**Archivo:** [`app/Http/Requests/reserva/ProcesarPagoRequest.php`](app/Http/Requests/reserva/ProcesarPagoRequest.php)

**Reglas actualizadas:**
```php
'codigo_moneda' => [
    'required',
    'string',
    'size:3',
    function ($attribute, $value, $fail) use ($exchangeService) {
        if (!$exchangeService->estaMonedaSoportada($value)) {
            $fail('La moneda seleccionada no está soportada.');
        }
    },
]
```

#### 5. Controlador de Reservas
**Archivo:** [`app/Http/Controllers/Api/reserva/ReservaController.php`](app/Http/Controllers/Api/reserva/ReservaController.php)

**Métodos actualizados/agregados:**
- `procesarPago()`: Ahora maneja conversión de monedas
- `listarPagos()`: Incluye información de monedas
- `monedasSoportadas()`: Lista monedas disponibles
- `tiposDeCambio()`: Tipos de cambio actuales
- `convertirMoneda()`: Conversor de monedas

#### 6. Modelo Reserva
**Archivo:** [`app/Models/reserva/Reserva.php`](app/Models/reserva/Reserva.php)

**Método actualizado:**
```php
public function calcularMontoPagado(): float
{
    // Ahora suma monto_usd en lugar de monto
    return $this->pagos()
        ->whereIn('id_estado_pago', [
            EstadoPago::ESTADO_COMPLETADO,
            EstadoPago::ESTADO_PARCIAL
        ])
        ->sum('monto_usd');
}
```

#### 7. Rutas API
**Archivo:** [`routes/api.php`](routes/api.php)

**Rutas agregadas:**
```php
// Sistema de Monedas y Tipos de Cambio
Route::get('monedas/soportadas', [ReservaController::class, 'monedasSoportadas']);
Route::get('monedas/tipos-cambio', [ReservaController::class, 'tiposDeCambio']);
Route::get('monedas/convertir', [ReservaController::class, 'convertirMoneda']);
```

## Flujo de Pago

### Escenario 1: Pago en USD

```
1. Cliente selecciona USD
2. Monto: $100.00
3. Sistema:
   - tipo_cambio = 1.0
   - monto_usd = 100.00
   - No requiere conversión
4. Se registra el pago
```

### Escenario 2: Pago en Colones (CRC)

```
1. Cliente selecciona CRC
2. Monto: ₡52,050.00
3. Sistema:
   - Consulta API: 1 USD = 520.50 CRC
   - Convierte: 52,050 ÷ 520.50 = 100.00 USD
   - tipo_cambio = 520.50
   - monto = 52050.00
   - monto_usd = 100.00
4. Se registra el pago
5. Observer actualiza totales usando monto_usd
```

### Escenario 3: Pago en Euros (EUR)

```
1. Cliente selecciona EUR
2. Monto: €92.00
3. Sistema:
   - Consulta API: 1 USD = 0.92 EUR
   - Convierte: 92.00 ÷ 0.92 = 100.00 USD
   - tipo_cambio = 0.92
   - monto = 92.00
   - monto_usd = 100.00
4. Se registra el pago
5. Observer actualiza totales
```

## Endpoints API

### 1. Consultar Monedas Soportadas

```http
GET /api/monedas/soportadas
```

**Respuesta:**
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
    ...
  ]
}
```

### 2. Consultar Tipos de Cambio

```http
GET /api/monedas/tipos-cambio
```

**Respuesta:**
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
      ...
    }
  }
}
```

### 3. Convertir Entre Monedas

```http
GET /api/monedas/convertir?monto=100&desde=USD&hasta=CRC
```

**Respuesta:**
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

### 4. Procesar Pago

```http
POST /api/reservas/{reserva}/pagos
Content-Type: application/json

{
  "codigo_moneda": "CRC",
  "id_metodo_pago": 1,
  "monto": 52050.00,
  "id_estado_pago": 2,
  "referencia": "REF-001",
  "notas": "Pago en colones"
}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Pago procesado exitosamente",
  "data": {
    "id_reserva_pago": 123,
    "monto": 52050.00,
    "moneda": {
      "codigo": "CRC",
      "nombre": "Colón Costarricense"
    },
    "tipo_cambio": 520.50,
    "tipo_cambio_formateado": "1 USD = 520.500000 CRC",
    "monto_usd": 100.00,
    "metodo_pago": "Tarjeta de Crédito",
    "estado_pago": "Completado",
    ...
  },
  "reserva": {
    "total_monto_reserva": 300.00,
    "monto_pagado": 100.00,
    "monto_pendiente": 200.00,
    "pago_completo": false,
    "porcentaje_pagado": 33.33
  }
}
```

### 5. Listar Pagos de Reserva

```http
GET /api/reservas/{reserva}/pagos
```

**Respuesta:**
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
        ...
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
        ...
      }
    ]
  }
}
```

## Consideraciones Importantes

### 1. Moneda Base
- **Todos** los precios de habitaciones están en USD
- **Todos** los totales de reservas se calculan en USD
- Los pagos se pueden hacer en cualquier moneda soportada

### 2. Tipos de Cambio
- Se cachean por 12 horas
- Se actualizan automáticamente al expirar el cache
- Si la API falla, se usan tasas predefinidas de fallback

### 3. Precisión
- Montos: 2 decimales (10, 2)
- Tipos de cambio: 6 decimales (12, 6)
- Conversiones se redondean a 2 decimales

### 4. Historial
- Cada pago guarda el `tipo_cambio` aplicado
- Permite auditoría histórica
- Los cambios futuros en tasas NO afectan pagos pasados

### 5. Estados de Pago
Los estados válidos son:
- `ESTADO_PENDIENTE` (1): Pago pendiente
- `ESTADO_COMPLETADO` (2): Pago completado
- `ESTADO_PARCIAL` (5): Pago parcial

**NOTA:** Solo los pagos COMPLETADOS y PARCIALES se suman al `monto_pagado`

## Mantenimiento

### Limpiar Cache de Tipos de Cambio

```php
$exchangeService = app(ExchangeRateService::class);
$exchangeService->limpiarCache();
```

### Agregar Nueva Moneda

1. Agregar a la constante `MONEDAS_SOPORTADAS` en `ExchangeRateService`
2. Insertar registro en tabla `moneda`:
```sql
INSERT INTO moneda (codigo, nombre) VALUES ('XXX', 'Nombre de la Moneda');
```

### Cambiar Proveedor de API

Modificar el método `obtenerTiposDeCambio()` en `ExchangeRateService.php` para usar un nuevo proveedor.

## Ejemplos de Uso

Ver el archivo [`TESTING_EXAMPLES.md`](TESTING_EXAMPLES.md) para ejemplos detallados de:
- Consultar monedas soportadas
- Obtener tipos de cambio
- Convertir entre monedas
- Procesar pagos en diferentes monedas
- Listar pagos multi-moneda

## Seguridad

- ✅ Validación de códigos de moneda (solo soportadas)
- ✅ Timeout de 10 segundos en llamadas API
- ✅ Fallback automático si API falla
- ✅ Validación de montos (mínimo 0.01)
- ✅ Protección contra SQL injection (uso de Eloquent)
- ✅ Validación de datos en Request classes

## Performance

- ✅ Cache de 12 horas para tipos de cambio
- ✅ Reducción de llamadas API (solo cuando expira cache)
- ✅ Índices en base de datos (id_moneda)
- ✅ Eager loading de relaciones (with())

---

**Fecha de Implementación:** 2025-10-15
**Versión:** 1.0
**Sistema:** Backend-SistemaHotelero
