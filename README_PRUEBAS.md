# 📚 Guía Rápida de Documentación

Bienvenido al sistema de gestión hotelero. Aquí encontrarás toda la documentación de las funcionalidades implementadas.

---

## 📖 Documentos Disponibles

### 1. 📋 [RESUMEN_IMPLEMENTACION.md](RESUMEN_IMPLEMENTACION.md)
**¿Qué es?** Resumen ejecutivo completo de todo lo implementado.

**Incluye:**
- ✅ Descripción de cada funcionalidad
- ✅ Código de ejemplo
- ✅ Archivos creados/modificados
- ✅ Resultados de pruebas
- ✅ Características técnicas

**Cuándo usarlo:** Para entender qué se implementó y cómo funciona internamente.

---

### 2. 🧪 [TESTING_EXAMPLES.md](TESTING_EXAMPLES.md)
**¿Qué es?** Ejemplos prácticos de peticiones HTTP para probar todas las funcionalidades.

**Incluye:**
- ✅ Ejemplos de peticiones POST/GET/PUT
- ✅ Respuestas esperadas
- ✅ Casos de error
- ✅ Scripts cURL
- ✅ Checklist de pruebas

**Cuándo usarlo:** Para probar el sistema con Postman, cURL o cualquier cliente HTTP.

---

### 3. 📬 [Sistema_Hotelero_Tests.postman_collection.json](Sistema_Hotelero_Tests.postman_collection.json)
**¿Qué es?** Colección de Postman lista para importar.

**Incluye:**
- ✅ 20+ peticiones organizadas por categoría
- ✅ Tests automatizados
- ✅ Variables de entorno
- ✅ Ejemplos de datos

**Cuándo usarlo:** Si usas Postman. Solo importa y empieza a probar.

**Cómo importar:**
1. Abre Postman
2. Click en "Import"
3. Selecciona el archivo JSON
4. Configura la variable `{{token}}` con tu token de autenticación
5. ¡Listo para probar!

---

## 🚀 Inicio Rápido

### Paso 1: Verificar que todo está aplicado
```bash
# Verificar migraciones
php artisan migrate:status

# Debe mostrar estas 3 como "Ran":
# ✓ 2025_10_14_104714_add_business_constraints_to_reserva_tables
# ✓ 2025_10_14_113505_add_payment_tracking_to_reserva_table
# ✓ 2025_10_14_161117_add_codigo_reserva_to_reserva_table

# Verificar políticas de cancelación
php artisan tinker
>>> \App\Models\reserva\PoliticaCancelacion::count();
# Debe retornar: 5
```

### Paso 2: Crear tu primera reserva con código
```bash
curl -X POST http://localhost:8000/api/reservas \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TU_TOKEN" \
  -d '{
    "id_cliente": 1,
    "id_estado_res": 1,
    "id_tipo_res": 1,
    "id_origen_res": 1,
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

Deberías recibir algo como:
```json
{
  "success": true,
  "data": {
    "id_reserva": 31,
    "codigo_reserva": "TCA4ZJJY",
    "codigo_formateado": "TCA4-ZJJY",
    "total_monto_reserva": 300.00,
    "monto_pagado": 0.00,
    "monto_pendiente": 300.00
  }
}
```

### Paso 3: Probar otras funcionalidades
Usa los ejemplos en [TESTING_EXAMPLES.md](TESTING_EXAMPLES.md) o la colección de Postman.

---

## 📊 Funcionalidades Implementadas

| # | Funcionalidad | Estado | Documentación |
|---|---------------|--------|---------------|
| 1 | Constraints de negocio en DB | ✅ | [Ver](RESUMEN_IMPLEMENTACION.md#1--reglas-de-negocio-como-constraints-de-base-de-datos) |
| 2 | Validación de capacidad | ✅ | [Ver](RESUMEN_IMPLEMENTACION.md#2--validación-de-capacidad-de-habitaciones) |
| 3 | Estados y transiciones | ✅ | [Ver](RESUMEN_IMPLEMENTACION.md#3--estados-de-reserva-y-transiciones-válidas) |
| 4 | Liberación automática de habitaciones | ✅ | [Ver](RESUMEN_IMPLEMENTACION.md#4--liberación-automática-de-habitaciones-al-cancelar) |
| 5 | Sistema de pagos parciales | ✅ | [Ver](RESUMEN_IMPLEMENTACION.md#5--sistema-de-pagos-parciales-y-completos) |
| 6 | Confirmación automática por pago | ✅ | [Ver](RESUMEN_IMPLEMENTACION.md#6--cambio-automático-de-estado-al-alcanzar-pago-mínimo) |
| 7 | Políticas de cancelación | ✅ | [Ver](RESUMEN_IMPLEMENTACION.md#7--políticas-de-cancelación-con-cálculo-de-reembolsos) |
| 8 | Extensión de estadía | ✅ | [Ver](RESUMEN_IMPLEMENTACION.md#8--sistema-de-extensión-de-estadía) |
| 9 | Códigos únicos de reserva | ✅ | [Ver](RESUMEN_IMPLEMENTACION.md#9--códigos-únicos-de-reserva-auto-generados) |

---

## 🧪 Ejemplos Rápidos por Funcionalidad

### 1️⃣ Crear Reserva (genera código automático)
```http
POST /api/reservas
{
  "id_cliente": 1,
  "habitaciones": [{
    "id_habitacion": 1,
    "fecha_llegada": "2025-11-01",
    "fecha_salida": "2025-11-04",
    "adultos": 2,
    "tarifa_noche": 100.00
  }]
}
```
→ Genera código automáticamente (ej: "TCA4-ZJJY")

### 2️⃣ Procesar Pago Parcial (30%)
```http
POST /api/reservas/31/pagos
{
  "id_metodo_pago": 1,
  "monto": 90.00,
  "id_estado_pago": 4
}
```
→ Cambia estado a "Confirmada" automáticamente

### 3️⃣ Preview de Cancelación
```http
GET /api/reservas/31/cancelacion/preview
```
→ Muestra reembolso según días de anticipación

### 4️⃣ Buscar por Código
```http
GET /api/reservas/buscar?codigo=TCA4-ZJJY
```
→ Busca reserva (acepta con o sin guiones)

### 5️⃣ Extender Estadía
```http
POST /api/reservas/31/extender
{
  "id_reserva_habitacion": 1,
  "noches_adicionales": 2,
  "nueva_fecha_salida": "2025-11-06"
}
```
→ Verifica disponibilidad y extiende

---

## 🔍 Verificar Estado del Sistema

### Usando PHP Artisan Tinker:
```bash
php artisan tinker
```

```php
// Verificar generación de códigos
$service = app(\App\Services\CodigoReservaService::class);
$codigo = $service->generarCodigoUnico();
echo "Código generado: {$codigo}\n";
echo "Formateado: " . $service->formatearCodigo($codigo) . "\n";

// Ver estadísticas
print_r($service->obtenerEstadisticas());

// Verificar políticas
\App\Models\reserva\PoliticaCancelacion::all(['nombre', 'penalidad_valor']);

// Calcular reembolso de ejemplo
$resultado = \App\Models\reserva\PoliticaCancelacion::calcularReembolso(300.00, 20);
print_r($resultado);

// Ver reservas con código
\App\Models\reserva\Reserva::whereNotNull('codigo_reserva')
    ->get(['id_reserva', 'codigo_reserva', 'total_monto_reserva']);
```

---

## 📞 Troubleshooting

### ❌ Error: "Column not found: codigo_reserva"
**Solución:**
```bash
php artisan migrate
```

### ❌ Error: "Call to undefined method calcularReembolso"
**Solución:** Verifica que las políticas estén creadas:
```bash
php artisan db:seed --class=PoliticaCancelacionSeeder
```

### ❌ Error: "Observer not registered"
**Solución:** Verifica [AppServiceProvider.php](app/Providers/AppServiceProvider.php):
```php
Reserva::observe(ReservaObserver::class);
ReservaPago::observe(ReservaPagoObserver::class);
```

### ❌ Código no se genera automáticamente
**Solución:**
1. Verifica que el Observer esté registrado
2. Limpia cache: `php artisan config:clear`
3. Reinicia servidor

---

## 🎯 Checklist de Pruebas Completas

Usa este checklist para verificar que todo funciona:

- [ ] ✅ Crear reserva nueva (debe tener código)
- [ ] ✅ Código tiene formato correcto (8 chars, sin 0OI1l)
- [ ] ✅ Buscar reserva por código (con y sin guiones)
- [ ] ✅ Intentar exceder capacidad (debe fallar)
- [ ] ✅ Intentar fecha_salida < fecha_llegada (debe fallar)
- [ ] ✅ Procesar pago del 30% (debe confirmar)
- [ ] ✅ Verificar monto_pagado actualizado
- [ ] ✅ Procesar pago completo (pago_completo = true)
- [ ] ✅ Preview cancelación con diferentes días
- [ ] ✅ Cancelar reserva y ver habitaciones liberadas
- [ ] ✅ Verificar habitación en mantenimiento NO se libera
- [ ] ✅ Intentar extender estadía (misma habitación)
- [ ] ✅ Ver alternativas cuando no disponible
- [ ] ✅ Validar transiciones de estado
- [ ] ✅ Ver resumen de pagos

---

## 📚 Archivos de Referencia

### Servicios
- [CodigoReservaService.php](app/Services/CodigoReservaService.php) - Generación de códigos
- [ExtensionEstadiaService.php](app/Services/ExtensionEstadiaService.php) - Extensión de estadía

### Observers
- [ReservaObserver.php](app/Observers/ReservaObserver.php) - Auto-código y liberación
- [ReservaPagoObserver.php](app/Observers/ReservaPagoObserver.php) - Auto-actualización pagos

### Modelos
- [Reserva.php](app/Models/reserva/Reserva.php) - Modelo principal
- [EstadoReserva.php](app/Models/reserva/EstadoReserva.php) - Estados y transiciones
- [PoliticaCancelacion.php](app/Models/reserva/PoliticaCancelacion.php) - Cálculos de reembolso

### Requests
- [StoreReservaRequest.php](app/Http/Requests/reserva/StoreReservaRequest.php) - Validación creación
- [ProcesarPagoRequest.php](app/Http/Requests/reserva/ProcesarPagoRequest.php) - Validación pagos
- [ExtenderEstadiaRequest.php](app/Http/Requests/reserva/ExtenderEstadiaRequest.php) - Validación extensión

---

## 🎉 ¡Todo Listo!

El sistema está completamente implementado y documentado.

**Siguiente paso:** Importa la colección de Postman y empieza a probar las funcionalidades.

Si tienes dudas, consulta [RESUMEN_IMPLEMENTACION.md](RESUMEN_IMPLEMENTACION.md) para detalles técnicos o [TESTING_EXAMPLES.md](TESTING_EXAMPLES.md) para ejemplos de uso.

---

**Última actualización:** 2025-10-14
**Estado:** ✅ Producción Ready