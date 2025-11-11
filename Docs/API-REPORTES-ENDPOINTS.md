# 📊 API - Endpoints de Reportes y Estadísticas

**Versión:** 1.0  
**Fecha:** 20 de Octubre, 2025  
**Módulo:** Reportes de Reservaciones  
**Base URL:** `http://127.0.0.1:8000/api`

---

## 🎯 Resumen

El frontend ya tiene implementada la interfaz de reportes y estadísticas. Necesitamos que el backend implemente **3 endpoints principales** optimizados para visualización de datos.

**Prioridades:**

- 🚀 **Performance**: Datos agregados, no registros individuales
- 📊 **Gráficos**: Soporte para charts (line, bar, pie, donut)
- 🕒 **Períodos**: Filtros por tiempo predefinidos (`7d`, `30d`, `3m`, `6m`, `1y`)
- 📥 **Exportación**: PDF con gráficos y tablas

---

## 🔐 Autenticación

Todos los endpoints requieren token de admin/staff:

```http
Authorization: Bearer {admin_token}
```

---

## ✅ Endpoints Ya Implementados

Estos ya funcionan en el backend:

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/reservas` | GET | Lista de reservas con filtros |
| `/reservas/{id}` | GET | Detalle de una reserva |
| `/clientes/{id}` | GET | Información de un cliente |

---

## 🆕 Endpoints Nuevos Requeridos

### 1. GET `/api/reservas/reportes/kpis`

**Objetivo:** Retornar métricas clave (KPIs) de forma **ligera y rápida**.

**Parámetros Query:**

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `periodo` | string | Período predefinido | `7d`, `30d`, `3m`, `6m`, `1y`, `all` |
| `fecha_desde` | date | Fecha inicio (alternativa a período) | `2025-01-01` |
| `fecha_hasta` | date | Fecha fin (alternativa a período) | `2025-12-31` |
| `tipo_habitacion` | string | Filtrar por tipo | `suite`, `doble`, `simple` |
| `estado` | string | Filtrar por estado | `confirmed`, `cancelled`, `pending` |
| `fuente` | string | Filtrar por fuente | `web`, `telefono`, `email`, `presencial` |

**Períodos disponibles:**

- `7d` → Últimos 7 días
- `30d` → Último mes (30 días)
- `3m` → Últimos 3 meses
- `6m` → Últimos 6 meses
- `1y` → Último año
- `all` → Todo el historial

**Respuesta (200 OK):**

```json
{
  "success": true,
  "data": {
    "occupancyRate": 75.5,
    "totalRevenue": 125000.00,
    "confirmedReservations": 45,
    "cancelledReservations": 5,
    "totalReservations": 50,
    "averageDailyRate": 85.50,
    "revPAR": 64.63
  }
}
```

**Descripción de KPIs:**

| KPI | Fórmula | Descripción |
|-----|---------|-------------|
| `occupancyRate` | `(habitaciones_ocupadas / total_habitaciones) * 100` | % de ocupación |
| `totalRevenue` | `SUM(reservas.precio_total)` | Ingresos totales |
| `confirmedReservations` | `COUNT(WHERE estado = 'confirmed')` | Reservas confirmadas |
| `cancelledReservations` | `COUNT(WHERE estado = 'cancelled')` | Reservas canceladas |
| `totalReservations` | `COUNT(reservas)` | Total de reservas |
| `averageDailyRate` | `totalRevenue / total_noches_reservadas` | Tarifa promedio |
| `revPAR` | `totalRevenue / (total_habitaciones * días)` | Revenue per available room |

**Uso en Frontend:**

- Dashboard principal con KPIs destacados
- Actualización cada 5 minutos (polling automático)
- No requiere cargar datos de gráficos completos

---

### 2. GET `/api/reservas/reportes/series-temporales`

**Objetivo:** Datos agregados por fecha para gráficos de línea/barras.

**Parámetros Query:** Los mismos que el endpoint de KPIs

**Respuesta (200 OK):**

```json
{
  "success": true,
  "data": [
    {
      "date": "2025-01-01",
      "reservations": 12,
      "revenue": 10500.00,
      "occupancy": 80.0,
      "cancellations": 1
    },
    {
      "date": "2025-01-02",
      "reservations": 15,
      "revenue": 12750.00,
      "occupancy": 85.0,
      "cancellations": 0
    },
    {
      "date": "2025-01-03",
      "reservations": 18,
      "revenue": 15200.00,
      "occupancy": 90.0,
      "cancellations": 2
    }
  ]
}
```

**Cálculos por fecha:**

```sql
-- Ejemplo de query (tú decides cómo implementarlo)
SELECT 
    DATE(fecha_entrada) as date,
    COUNT(*) as reservations,
    SUM(precio_total) as revenue,
    (COUNT(DISTINCT id_habitacion) * 100.0 / total_habitaciones) as occupancy,
    SUM(CASE WHEN estado = 'cancelled' THEN 1 ELSE 0 END) as cancellations
FROM reservas
WHERE fecha_entrada BETWEEN ? AND ?
GROUP BY DATE(fecha_entrada)
ORDER BY date ASC
```

**Uso en Frontend:**

- Gráfico de líneas (tendencia de reservas)
- Gráfico de barras (ingresos diarios)
- Selector de métrica (reservas, ingresos, ocupación, cancelaciones)

---

### 3. GET `/api/reservas/reportes/distribuciones`

**Objetivo:** Datos agregados por categorías para gráficos de pie/donut.

**Parámetros Query:** Los mismos que el endpoint de KPIs

**Respuesta (200 OK):**

```json
{
  "success": true,
  "data": {
    "byRoomType": [
      {
        "name": "Suite Ejecutiva",
        "value": 35,
        "percentage": 25.93
      },
      {
        "name": "Habitación Doble",
        "value": 68,
        "percentage": 50.37
      },
      {
        "name": "Habitación Simple",
        "value": 32,
        "percentage": 23.70
      }
    ],
    "bySource": [
      {
        "name": "Web",
        "value": 81,
        "percentage": 60.0
      },
      {
        "name": "Teléfono",
        "value": 27,
        "percentage": 20.0
      },
      {
        "name": "Email",
        "value": 18,
        "percentage": 13.33
      },
      {
        "name": "Presencial",
        "value": 9,
        "percentage": 6.67
      }
    ],
    "byStatus": [
      {
        "name": "Confirmada",
        "value": 127,
        "percentage": 94.07
      },
      {
        "name": "Cancelada",
        "value": 8,
        "percentage": 5.93
      }
    ]
  }
}
```

**Cálculos por categoría:**

```sql
-- Por tipo de habitación
SELECT 
    tipo_habitacion.nombre as name,
    COUNT(*) as value,
    (COUNT(*) * 100.0 / total_reservas) as percentage
FROM reservas
JOIN habitaciones ON reservas.id_habitacion = habitaciones.id
JOIN tipo_habitacion ON habitaciones.id_tipo = tipo_habitacion.id
WHERE fecha_entrada BETWEEN ? AND ?
GROUP BY tipo_habitacion.nombre

-- Por fuente
SELECT 
    fuente_reserva as name,
    COUNT(*) as value,
    (COUNT(*) * 100.0 / total_reservas) as percentage
FROM reservas
WHERE fecha_entrada BETWEEN ? AND ?
GROUP BY fuente_reserva

-- Por estado
SELECT 
    estado as name,
    COUNT(*) as value,
    (COUNT(*) * 100.0 / total_reservas) as percentage
FROM reservas
WHERE fecha_entrada BETWEEN ? AND ?
GROUP BY estado
```

**Uso en Frontend:**

- Gráfico circular (distribución por tipo de habitación)
- Gráfico de donut (distribución por fuente)
- Gráfico de donut (distribución por estado)

---

### 4. GET `/api/reservas/reportes/export/pdf`

**Objetivo:** Generar y descargar reporte completo en PDF.

**Parámetros Query:**

| Parámetro | Tipo | Descripción | Default |
|-----------|------|-------------|---------|
| `periodo` | string | Período del reporte | `30d` |
| `fecha_desde` | date | Fecha inicio | - |
| `fecha_hasta` | date | Fecha fin | - |
| `incluir_graficos` | boolean | Incluir gráficos | `true` |
| `incluir_tabla` | boolean | Incluir tabla de reservas | `true` |
| `idioma` | string | Idioma del reporte | `es` |

**Respuesta (200 OK):**

```http
Content-Type: application/pdf
Content-Disposition: attachment; filename="reporte-reservas-2025-01-01.pdf"

[Binary PDF Data]
```

**Contenido del PDF:**

1. **Portada**
   - Título del reporte
   - Rango de fechas
   - Fecha de generación

2. **Sección KPIs**
   - Tasa de ocupación
   - Ingresos totales
   - Total de reservas
   - Reservas confirmadas/canceladas
   - ADR (Average Daily Rate)
   - RevPAR

3. **Gráficos** (opcional)
   - Serie temporal de reservas
   - Distribución por tipo de habitación
   - Distribución por fuente
   - Distribución por estado

4. **Tabla de Reservas** (opcional)
   - Número de confirmación
   - Huésped
   - Tipo de habitación
   - Check-in / Check-out
   - Estado
   - Total

**Uso en Frontend:**

```typescript
// El frontend descarga el PDF así:
const blob = await reservationReportsService.exportToPDF(filters);
const url = window.URL.createObjectURL(blob);
const link = document.createElement('a');
link.href = url;
link.download = `reporte-${filters.startDate}.pdf`;
link.click();
```

---

## 📦 Modelos de Datos TypeScript

El frontend ya tiene estos tipos definidos:

### KPI Metrics

```typescript
interface ReservationKpiDto {
  occupancyRate: number;          // 0-100
  totalRevenue: number;           // Decimal
  confirmedReservations: number;  // Integer
  cancelledReservations: number;  // Integer
  totalReservations: number;      // Integer
  averageDailyRate: number;       // Decimal
  revPAR: number;                 // Decimal
}
```

### Time Series Data Point

```typescript
interface TimeSeriesDataPoint {
  date: string;           // YYYY-MM-DD
  reservations: number;   // Integer
  revenue: number;        // Decimal
  occupancy: number;      // 0-100
  cancellations: number;  // Integer
}
```

### Distribution Data Point

```typescript
interface DistributionDataPoint {
  name: string;       // Nombre de la categoría
  value: number;      // Cantidad (integer)
  percentage: number; // Porcentaje (0-100)
}
```

---

## 🎨 Ejemplos de Request

### Ejemplo 1: KPIs del último mes

```http
GET /api/reservas/reportes/kpis?periodo=30d
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

### Ejemplo 2: Series temporales de los últimos 3 meses

```http
GET /api/reservas/reportes/series-temporales?periodo=3m
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

### Ejemplo 3: Distribuciones filtradas por tipo de habitación

```http
GET /api/reservas/reportes/distribuciones?periodo=6m&tipo_habitacion=suite
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

### Ejemplo 4: Exportar PDF con rango de fechas específico

```http
GET /api/reservas/reportes/export/pdf?fecha_desde=2025-01-01&fecha_hasta=2025-12-31&incluir_graficos=true
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

### Ejemplo 5: KPIs con múltiples filtros

```http
GET /api/reservas/reportes/kpis?periodo=1y&estado=confirmed&fuente=web
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

---

## ⚠️ Códigos de Error

### 400 Bad Request

```json
{
  "success": false,
  "message": "Parámetros inválidos",
  "errors": {
    "periodo": ["El período debe ser: 7d, 30d, 3m, 6m, 1y o all"],
    "fecha_desde": ["La fecha debe estar en formato YYYY-MM-DD"]
  }
}
```

### 401 Unauthorized

```json
{
  "success": false,
  "message": "No autenticado. Token inválido o expirado."
}
```

### 403 Forbidden

```json
{
  "success": false,
  "message": "No tienes permiso para acceder a reportes."
}
```

### 500 Internal Server Error

```json
{
  "success": false,
  "message": "Error al generar el reporte.",
  "error": "Database connection timeout"
}
```

---

## 🔧 Notas de Implementación

### 1. Optimización

- Usa **queries agregadas** (GROUP BY) en lugar de cargar todas las filas
- Implementa **cache** para KPIs (5 minutos es suficiente)
- Usa **índices** en las columnas: `fecha_entrada`, `estado`, `fuente_reserva`

### 2. Valores por Defecto

Si no se envían fechas ni período:

- Default: `periodo=30d` (último mes)

### 3. Validación de Fechas

- `fecha_desde` debe ser anterior o igual a `fecha_hasta`
- Formato obligatorio: `YYYY-MM-DD`
- Si se envía `periodo`, ignorar `fecha_desde` y `fecha_hasta`

### 4. Formateo de Números

- Todos los decimales con **2 decimales** (`round(value, 2)`)
- Porcentajes entre **0 y 100** (no 0 a 1)

### 5. Performance

- Límite máximo de fechas en series temporales: **365 días**
- Si el rango es mayor, agregar por semana o mes

---

## 📞 Contacto

**Frontend Developer:** Jose  
**Branch:** `Jose`  
**Pull Request:** #50

Si tienes dudas sobre la estructura de datos o necesitas más ejemplos, avísame.

---

**Última actualización:** 20 de Octubre, 2025