# 🧪 Guía de Pruebas - Endpoints de Reportes

**Fecha:** 20 de Octubre, 2025
**Implementación:** Nuevos endpoints de reportes y estadísticas
**Base URL:** `http://127.0.0.1:8000/api`

---

## 📋 Resumen de Implementación

Se han creado **4 nuevos endpoints** para el sistema de reportes:

| Endpoint | Método | Estado | Descripción |
|----------|--------|--------|-------------|
| `/reservas/reportes/kpis` | GET | ✅ Implementado | KPIs principales |
| `/reservas/reportes/series-temporales` | GET | ✅ Implementado | Datos para gráficos de línea |
| `/reservas/reportes/distribuciones` | GET | ✅ Implementado | Datos para gráficos pie/donut |
| `/reservas/reportes/export/pdf` | GET | ⚠️ Pendiente | Exportar a PDF (requiere librería) |

---

## 🔑 Autenticación

Todos los endpoints requieren **token de admin/staff**:

```http
Authorization: Bearer {tu_token_aqui}
```

### Obtener Token de Prueba

```bash
# 1. Login como admin
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@hotel.com",
    "password": "tu_password"
  }'
```

Respuesta:
```json
{
  "access_token": "1|aBcDeFgHiJkLmNoPqRsTuVwXyZ...",
  "token_type": "Bearer"
}
```

---

## 🧪 Pruebas de Endpoints

### 1. Endpoint: KPIs

**GET** `/api/reservas/reportes/kpis`

#### Prueba 1.1: KPIs del último mes (default)

```bash
curl -X GET "http://127.0.0.1:8000/api/reservas/reportes/kpis" \
  -H "Authorization: Bearer {tu_token}"
```

#### Prueba 1.2: KPIs de los últimos 7 días

```bash
curl -X GET "http://127.0.0.1:8000/api/reservas/reportes/kpis?periodo=7d" \
  -H "Authorization: Bearer {tu_token}"
```

#### Prueba 1.3: KPIs con rango de fechas específico

```bash
curl -X GET "http://127.0.0.1:8000/api/reservas/reportes/kpis?fecha_desde=2025-01-01&fecha_hasta=2025-10-20" \
  -H "Authorization: Bearer {tu_token}"
```

#### Prueba 1.4: KPIs filtrados por estado "confirmed"

```bash
curl -X GET "http://127.0.0.1:8000/api/reservas/reportes/kpis?periodo=30d&estado=confirmed" \
  -H "Authorization: Bearer {tu_token}"
```

#### Respuesta Esperada:

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

---

### 2. Endpoint: Series Temporales

**GET** `/api/reservas/reportes/series-temporales`

#### Prueba 2.1: Series de los últimos 30 días

```bash
curl -X GET "http://127.0.0.1:8000/api/reservas/reportes/series-temporales?periodo=30d" \
  -H "Authorization: Bearer {tu_token}"
```

#### Prueba 2.2: Series de los últimos 3 meses

```bash
curl -X GET "http://127.0.0.1:8000/api/reservas/reportes/series-temporales?periodo=3m" \
  -H "Authorization: Bearer {tu_token}"
```

#### Prueba 2.3: Series con fechas específicas

```bash
curl -X GET "http://127.0.0.1:8000/api/reservas/reportes/series-temporales?fecha_desde=2025-09-01&fecha_hasta=2025-10-20" \
  -H "Authorization: Bearer {tu_token}"
```

#### Respuesta Esperada:

```json
{
  "success": true,
  "data": [
    {
      "date": "2025-10-01",
      "reservations": 12,
      "revenue": 10500.00,
      "occupancy": 80.0,
      "cancellations": 1
    },
    {
      "date": "2025-10-02",
      "reservations": 15,
      "revenue": 12750.00,
      "occupancy": 85.0,
      "cancellations": 0
    }
  ]
}
```

---

### 3. Endpoint: Distribuciones

**GET** `/api/reservas/reportes/distribuciones`

#### Prueba 3.1: Distribuciones del último mes

```bash
curl -X GET "http://127.0.0.1:8000/api/reservas/reportes/distribuciones?periodo=30d" \
  -H "Authorization: Bearer {tu_token}"
```

#### Prueba 3.2: Distribuciones de los últimos 6 meses

```bash
curl -X GET "http://127.0.0.1:8000/api/reservas/reportes/distribuciones?periodo=6m" \
  -H "Authorization: Bearer {tu_token}"
```

#### Prueba 3.3: Distribuciones con fechas personalizadas

```bash
curl -X GET "http://127.0.0.1:8000/api/reservas/reportes/distribuciones?fecha_desde=2025-01-01&fecha_hasta=2025-12-31" \
  -H "Authorization: Bearer {tu_token}"
```

#### Respuesta Esperada:

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

---

### 4. Endpoint: Exportar PDF

**GET** `/api/reservas/reportes/export/pdf`

⚠️ **Estado:** Pendiente de implementación (requiere instalar librería de PDF)

#### Prueba 4.1: Exportar PDF del último mes

```bash
curl -X GET "http://127.0.0.1:8000/api/reservas/reportes/export/pdf?periodo=30d" \
  -H "Authorization: Bearer {tu_token}" \
  --output reporte.pdf
```

#### Respuesta Actual:

```json
{
  "success": false,
  "message": "La exportación a PDF está pendiente de implementación. Se requiere instalar una librería de PDF (dompdf, mpdf o tcpdf)."
}
```

---

## 🎯 Parámetros de Query Disponibles

Todos los endpoints soportan los mismos parámetros:

| Parámetro | Tipo | Valores | Descripción |
|-----------|------|---------|-------------|
| `periodo` | string | `7d`, `30d`, `3m`, `6m`, `1y`, `all` | Período predefinido |
| `fecha_desde` | date | `YYYY-MM-DD` | Fecha inicio (alternativa a período) |
| `fecha_hasta` | date | `YYYY-MM-DD` | Fecha fin (alternativa a período) |
| `tipo_habitacion` | string | - | Filtrar por tipo (parcial) |
| `estado` | string | `confirmed`, `cancelled`, `pending` | Filtrar por estado |
| `fuente` | string | - | Filtrar por fuente (parcial) |

### Ejemplos Combinados:

```bash
# KPIs de reservas confirmadas de los últimos 3 meses desde Web
curl -X GET "http://127.0.0.1:8000/api/reservas/reportes/kpis?periodo=3m&estado=confirmed&fuente=web" \
  -H "Authorization: Bearer {tu_token}"

# Series temporales de suites en el último año
curl -X GET "http://127.0.0.1:8000/api/reservas/reportes/series-temporales?periodo=1y&tipo_habitacion=suite" \
  -H "Authorization: Bearer {tu_token}"
```

---

## ⚠️ Códigos de Error

### 400 Bad Request - Parámetros Inválidos

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

### 401 Unauthorized - Token Inválido

```json
{
  "success": false,
  "message": "Unauthenticated."
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

### Archivos Creados/Modificados:

1. **Nuevo Controlador:**
   - `app/Http/Controllers/Api/reserva/ReporteController.php`

2. **Rutas Modificadas:**
   - `routes/api.php` (líneas 160-165)

### Queries Optimizadas:

- Uso de `DB::raw()` para agregaciones
- Joins eficientes con tablas relacionadas
- Cálculos en base de datos (no en PHP)

### Middleware:

- Todos los endpoints requieren `auth:sanctum`
- Los usuarios deben estar autenticados con token válido

---

## 📊 Modelo de Datos

### KPIs Calculados:

| KPI | Fórmula | Descripción |
|-----|---------|-------------|
| `occupancyRate` | `(noches_ocupadas / (habitaciones * días)) * 100` | % de ocupación |
| `totalRevenue` | `SUM(total_monto_reserva)` | Ingresos totales |
| `confirmedReservations` | `COUNT(WHERE estado = 'Confirmada')` | Reservas confirmadas |
| `cancelledReservations` | `COUNT(WHERE estado = 'Cancelada')` | Reservas canceladas |
| `totalReservations` | `COUNT(*)` | Total de reservas |
| `averageDailyRate` | `totalRevenue / total_noches` | Tarifa promedio por noche |
| `revPAR` | `totalRevenue / (habitaciones * días)` | Revenue per available room |

---

## 🚀 Próximos Pasos

### Para Completar la Implementación de PDF:

1. Instalar librería de PDF:
   ```bash
   composer require barryvdh/laravel-dompdf
   ```

2. Publicar configuración:
   ```bash
   php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
   ```

3. Implementar método `exportPdf()` en `ReporteController`

### Optimizaciones Futuras:

- [ ] Agregar cache de 5 minutos para KPIs
- [ ] Implementar índices en BD para queries de reportes
- [ ] Agregar paginación para series muy largas (>365 días)
- [ ] Implementar exports a Excel/CSV

---

## 🧪 Checklist de Pruebas

- [ ] Probar endpoint KPIs sin parámetros (debe usar 30d por defecto)
- [ ] Probar todos los períodos: 7d, 30d, 3m, 6m, 1y, all
- [ ] Probar con fechas personalizadas
- [ ] Validar que fecha_hasta sea mayor que fecha_desde
- [ ] Probar filtros combinados (estado + fuente + tipo_habitacion)
- [ ] Verificar autenticación (debe fallar sin token)
- [ ] Verificar formato de respuesta JSON
- [ ] Probar con base de datos vacía
- [ ] Probar con datos reales del sistema
- [ ] Verificar performance con gran volumen de datos

---

## 📞 Contacto

**Backend Developer:** Andre
**Frontend Developer:** Jose
**Branch:** `DannyJ`
**Documentación Original:** `Docs/API-REPORTES-ENDPOINTS.md`

---

**Última actualización:** 20 de Octubre, 2025
