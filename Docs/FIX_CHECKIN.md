# Fix: Check-in Endpoint

## Problema Identificado

El método `generarEstadia()` en `ReservaController` estaba diseñado para crear estadías desde cero (walk-ins), requiriendo todos los campos manualmente. Esto no funcionaba para el flujo de check-in de reservas existentes.

## Error Original

Al intentar hacer check-in con:

```json
POST /api/reservas/49/checkin
{
  "fecha_entrada": "2026-04-20 14:30:00",
  "notas": "Cliente llegó temprano"
}
```

Se recibía:

```json
{
  "message": "The id cliente titular field is required. (and 6 more errors)",
  "errors": {
    "id_cliente_titular": ["The id cliente titular field is required."],
    "fecha_llegada": ["The fecha llegada field is required."],
    "fecha_salida": ["The fecha salida field is required."],
    "adultos": ["The adultos field is required."],
    "ninos": ["The ninos field is required."],
    "bebes": ["The bebes field is required."]
  }
}
```

## Solución Implementada

Se reescribió completamente el método `generarEstadia()` para:

1. **Validación simplificada**: Solo requiere campos opcionales
   - `fecha_entrada`: nullable|date (por defecto: now())
   - `notas`: nullable|string|max:500

2. **Extracción automática de datos**: Obtiene toda la información de la reserva existente
   - `id_cliente_titular` → `reserva.id_cliente`
   - `fecha_llegada` / `fecha_salida` → Primera habitación de la reserva
   - `adultos` / `ninos` / `bebes` → Primera habitación de la reserva
   - `id_fuente` → `reserva.id_fuente`

3. **Validaciones previas**:
   - Reserva debe estar en estado "Confirmada" (id_estado_res = 3)
   - Reserva debe haber alcanzado el pago mínimo del 30%
   - Reserva debe tener al menos una habitación asignada

4. **Flujo automático**:
   - Crea la estadía con datos de la reserva
   - Cambia el estado de la reserva a "Check-in" (id_estado_res = 5)
   - El `ReservaObserver` automáticamente cambia las habitaciones a "Ocupadas"

## Archivo Modificado

**app/Http/Controllers/Api/reserva/ReservaController.php**

Método: `generarEstadia(Reserva $reserva, Request $req)`

Línea aproximada: ~600

## Endpoint

```
POST /api/reservas/{id_reserva}/checkin
```

## Request Body (Nuevo)

```json
{
  "fecha_entrada": "2026-04-20 14:30:00",  // Opcional, default: now()
  "notas": "Cliente llegó temprano"        // Opcional
}
```

## Response Exitoso

```json
{
  "success": true,
  "message": "Check-in realizado exitosamente",
  "data": {
    "id_estadia": 123,
    "id_reserva": 49,
    "codigo_reserva": "RES-000049",
    "fecha_entrada": "2026-04-20 14:30:00",
    "estado_reserva": "Check-in",
    "habitaciones_ocupadas": [
      {
        "id_habitacion": 101,
        "numero_habitacion": "101",
        "nuevo_estado": "Ocupada"
      }
    ]
  }
}
```

## Errores Posibles

```json
{
  "success": false,
  "message": "Error al realizar check-in",
  "error": "Solo se puede hacer check-in a reservas confirmadas"
}
```

```json
{
  "success": false,
  "message": "Error al realizar check-in",
  "error": "La reserva no ha alcanzado el pago mínimo del 30%"
}
```

```json
{
  "success": false,
  "message": "Error al realizar check-in",
  "error": "La reserva no tiene habitaciones asignadas"
}
```

## Flujo Completo de Check-in

1. **Cliente hace pago** (mínimo 30% del total)
   - Se crea registro en `reserva_pago`
   - `ReservaPagoObserver` actualiza montos en `reserva`
   - Si alcanza 30%+, cambia estado de "Pendiente" → "Confirmada"

2. **Recepcionista hace check-in**
   - POST /api/reservas/{id}/checkin
   - Validaciones automáticas (estado, pago, habitaciones)
   - Se crea registro en `estadia`
   - Reserva cambia a estado "Check-in"

3. **Observer actualiza habitaciones**
   - `ReservaObserver.updated()` detecta cambio a Check-in
   - Cambia todas las habitaciones asignadas a "Ocupada"
   - Log de operación

## Archivos Relacionados

- [ReservaController.php](app/Http/Controllers/Api/reserva/ReservaController.php)
- [ReservaObserver.php](app/Observers/ReservaObserver.php)
- [ReservaPagoObserver.php](app/Observers/ReservaPagoObserver.php)
- [Estadia.php](app/Models/estadia/Estadia.php)
- [Reserva.php](app/Models/reserva/Reserva.php)
- [FLUJO_COMPLETO_RESERVAS.md](FLUJO_COMPLETO_RESERVAS.md)
