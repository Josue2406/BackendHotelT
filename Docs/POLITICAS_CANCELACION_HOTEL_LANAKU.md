# 📜 Políticas de Cancelación - Hotel Lanaku

## 📋 Resumen de Políticas Implementadas

El Hotel Lanaku cuenta con 5 políticas de cancelación diseñadas para diferentes escenarios y tipos de reservas.

---

## 1️⃣ POLÍTICA ESTÁNDAR

**Código:** `POLITICA_ESTANDAR` (ID: 1)
**Ventana de cancelación:** 72 horas antes de la llegada

### Reglas:
- ✅ **Cancelación SIN cargo:** 72+ horas antes de la fecha de llegada
- ❌ **Cancelación CON cargo:** Menos de 72 horas
  - Se cobra: **Primera noche con impuestos** (aprox. 30% del total)

### Modificaciones:
Las modificaciones fuera del plazo de 72 horas aplican las mismas penalizaciones que las cancelaciones.

### Casos de uso:
- Reservas regulares en temporada normal
- Reservas de huéspedes individuales
- Tarifa estándar del hotel

---

## 2️⃣ TARIFAS NO REEMBOLSABLES

**Código:** `POLITICA_NO_REEMBOLSABLE` (ID: 2)
**Características:** Pago total al momento de reservar

### Reglas:
- ❌ **NO aplican reembolsos** bajo ninguna circunstancia
- ❌ **NO aplican modificaciones** de fechas
- ⚠️ Requiere **pago del 100%** al momento de la reserva

### Casos de uso:
- Tarifas promocionales especiales
- Ofertas de última hora
- Descuentos significativos (Early Bird, etc.)

---

## 3️⃣ NO-SHOW (No Presentación)

**Código:** `POLITICA_NO_SHOW` (ID: 3)
**Aplicación:** Automática cuando el huésped no se presenta

### Reglas:
- ❌ **Se cobra el 100% del total** de la estancia reservada
- ❌ **Sin reembolso** posible
- ⚠️ Se mantiene el cargo completo incluso si no ocupó la habitación

### Casos de uso:
- Huésped confirmó pero no llegó el día del check-in
- No notificó cancelación previa
- Llegó después de la fecha de check-in sin aviso

---

## 4️⃣ TEMPORADA ALTA O EVENTOS ESPECIALES

**Código:** `POLITICA_TEMPORADA_ALTA` (ID: 4)
**Ventana de cancelación:** 15 días antes de la llegada

### Reglas:
- ✅ **Cancelación SIN cargo:** 15+ días antes de la llegada
- ❌ **Cancelación CON cargo:** Menos de 15 días
  - Se cobra: **100% de la primera noche**

### Casos de uso:
- Temporadas altas (Navidad, Año Nuevo, Semana Santa)
- Festividades locales
- Eventos especiales (conciertos, conferencias, festivales)
- Fines de semana largos

### Nota:
Esta política se activa automáticamente cuando la reserva coincide con:
- Fechas marcadas como "temporada alta" en el sistema
- Eventos especiales registrados en el calendario del hotel

---

## 5️⃣ FUERZA MAYOR

**Código:** `POLITICA_FUERZA_MAYOR` (ID: 5)
**Aplicación:** Evaluación caso por caso

### Reglas:
- 🔍 **Evaluación individual** de cada situación
- 📋 Requiere **documentación justificativa**
- ✅ El hotel puede ofrecer:
  - Cambio de fecha sin cargo
  - Crédito para futuras estancias
  - Reembolso parcial o total (según el caso)

### Situaciones consideradas:
- ✅ Desastres naturales (terremotos, huracanes, inundaciones)
- ✅ Emergencias médicas documentadas
- ✅ Pandemias o restricciones gubernamentales de viaje
- ✅ Fallecimiento de familiar directo
- ✅ Accidentes graves documentados

### Documentación requerida:
- Certificados médicos
- Constancias oficiales
- Documentos que respalden la situación de fuerza mayor

### Proceso:
1. Cliente contacta al hotel explicando la situación
2. Envía documentación de respaldo
3. Gerencia evalúa el caso en un plazo de 24-48 horas
4. Se notifica decisión y opciones disponibles

---

## 💡 Uso en el Sistema

### Cálculo automático de reembolsos

```php
use App\Models\reserva\PoliticaCancelacion;

// Ejemplo 1: Política Estándar
$resultado = PoliticaCancelacion::calcularReembolsoHotelLanaku(
    montoPagado: 500.00,
    diasAnticipacion: 5, // Cancela 5 días antes
    esTemporadaAlta: false,
    esTarifaNoReembolsable: false
);

// Resultado:
// reembolso: $350.00
// penalidad: $150.00 (primera noche)
// mensaje: "Se cobra la primera noche con impuestos"
```

```php
// Ejemplo 2: Temporada Alta
$resultado = PoliticaCancelacion::calcularReembolsoHotelLanaku(
    montoPagado: 1000.00,
    diasAnticipacion: 10, // Cancela 10 días antes en temporada alta
    esTemporadaAlta: true,
    esTarifaNoReembolsable: false
);

// Resultado:
// reembolso: $700.00
// penalidad: $300.00 (100% primera noche)
// mensaje: "Temporada alta: se cobra 100% de la primera noche"
```

```php
// Ejemplo 3: Tarifa No Reembolsable
$resultado = PoliticaCancelacion::calcularReembolsoHotelLanaku(
    montoPagado: 800.00,
    diasAnticipacion: 30, // Aunque cancele con 30 días
    esTemporadaAlta: false,
    esTarifaNoReembolsable: true
);

// Resultado:
// reembolso: $0.00
// penalidad: $800.00
// mensaje: "Tarifa no reembolsable: no aplican reembolsos ni modificaciones"
```

### No-Show

```php
$resultado = PoliticaCancelacion::calcularPenalidadNoShow(
    montoTotal: 1200.00
);

// Resultado:
// reembolso: $0.00
// penalidad: $1200.00
// mensaje: "No-Show: se cobra el total de la estancia reservada"
```

---

## 📊 Tabla Comparativa

| Política | Ventana | Cancelación con anticipación | Cancelación tardía | Reembolso |
|----------|---------|------------------------------|-------------------|-----------|
| **Estándar** | 72 horas | ✅ Sin cargo | ❌ Primera noche | Parcial |
| **No Reembolsable** | N/A | ❌ Sin reembolso | ❌ Sin reembolso | Ninguno |
| **No-Show** | N/A | N/A | ❌ Cargo total | Ninguno |
| **Temporada Alta** | 15 días | ✅ Sin cargo | ❌ Primera noche (100%) | Parcial |
| **Fuerza Mayor** | Variable | 🔍 Evaluación | 🔍 Evaluación | Variable |

---

## 🎯 Recomendaciones para Clientes

### Para obtener máxima flexibilidad:
1. ✅ Reservar con tarifas estándar (no promocionales)
2. ✅ Evitar reservas no reembolsables si hay incertidumbre
3. ✅ Cancelar o modificar con al menos 72 horas de anticipación
4. ✅ En temporada alta: cancelar con 15+ días de anticipación

### Para obtener mejor precio:
1. 💰 Considerar tarifas no reembolsables si está 100% seguro
2. 💰 Aprovechar descuentos anticipados
3. 💰 Revisar promociones especiales (pero verificar política)

---

## 📞 Contacto para Cancelaciones

**Email:** reservas@hotellanaku.com
**Teléfono:** +506 XXXX-XXXX
**Horario:** Lunes a Domingo, 7:00 AM - 10:00 PM

---

## ⚖️ Términos Legales

- Todas las políticas son aplicables desde el momento de la confirmación de la reserva
- El hotel se reserva el derecho de actualizar las políticas con aviso previo
- Los reembolsos se procesan en 5-10 días hábiles a la cuenta original de pago
- Para casos de fuerza mayor, la decisión final es responsabilidad de la gerencia del hotel
- Los impuestos aplicables según legislación costarricense

---

**Hotel Lanaku** 🏨
**Actualizado:** Octubre 2025
**Versión:** 1.0
