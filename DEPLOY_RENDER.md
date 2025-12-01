# 🚀 Despliegue en Render con Docker

## ✅ Por qué Render es perfecto para este proyecto

- ✅ Soporta contenedores Docker nativamente
- ✅ Puede correr procesos persistentes (queue worker, reverb)
- ✅ Tier gratuito generoso (750 horas/mes)
- ✅ SSL automático
- ✅ Fácil de configurar
- ✅ Soporta variables de entorno
- ✅ Ya tienes la URL: `backendhotelt.onrender.com`

---

## 📋 PREPARACIÓN

### 1. Crear archivo `render.yaml` (Configuración Infrastructure as Code)

Este archivo le dice a Render cómo desplegar tu aplicación:

```yaml
services:
  # Servicio principal: API + Queue Worker + Reverb
  - type: web
    name: backend-hotel
    env: docker
    dockerfilePath: ./Dockerfile
    plan: free # Cambiar a 'starter' para más recursos
    region: oregon # ohio, oregon, frankfurt, singapore

    # Variables de entorno
    envVars:
      - key: APP_NAME
        value: Lanaku

      - key: APP_ENV
        value: production

      - key: APP_DEBUG
        value: false

      - key: APP_KEY
        sync: false
        # ⚠️ Genera con: php artisan key:generate --show
        value: base64:oaulL3b2rlL+N26JxhQXyTaJmkxCP7m1BMUIFA2p6sA=

      - key: APP_URL
        value: https://backendhotelt.onrender.com

      - key: APP_TIMEZONE
        value: America/Costa_Rica

      - key: CORS_ALLOWED_ORIGINS
        value: http://localhost:5173,https://una-hotel-system.vercel.app

      - key: SANCTUM_STATEFUL_DOMAINS
        value: localhost:5173,una-hotel-system.vercel.app

      # Base de datos (Railway)
      - key: DB_CONNECTION
        value: mysql

      - key: DB_HOST
        value: yamanote.proxy.rlwy.net

      - key: DB_PORT
        value: 31248

      - key: DB_DATABASE
        value: railway

      - key: DB_USERNAME
        value: root

      - key: DB_PASSWORD
        sync: false
        value: GXQOumMdKxjXpVwRxOagzxiZNoZXJlNo

      # Queue
      - key: QUEUE_CONNECTION
        value: database

      # Cache
      - key: CACHE_STORE
        value: database

      # Session
      - key: SESSION_DRIVER
        value: database

      - key: SESSION_SECURE_COOKIE
        value: true

      - key: SESSION_SAME_SITE
        value: none

      # Broadcasting
      - key: BROADCAST_CONNECTION
        value: reverb

      # Reverb WebSocket
      - key: REVERB_APP_ID
        value: 897815

      - key: REVERB_APP_KEY
        value: wibscmvwkk1ndpotbxdw

      - key: REVERB_APP_SECRET
        sync: false
        value: smjzmkrz7ztwlqcejngw

      - key: REVERB_HOST
        value: backendhotelt.onrender.com

      - key: REVERB_PORT
        value: 443

      - key: REVERB_SCHEME
        value: https

      - key: REVERB_ALLOWED_ORIGINS
        value: https://una-hotel-system.vercel.app,http://localhost:5173

      # Redis (interno de Render o externo)
      - key: REDIS_HOST
        value: 127.0.0.1

      # Mail
      - key: MAIL_MAILER
        value: smtp

      - key: MAIL_HOST
        value: smtp.gmail.com

      - key: MAIL_PORT
        value: 587

      - key: MAIL_USERNAME
        value: unaturismo3@gmail.com

      - key: MAIL_PASSWORD
        sync: false
        value: hjotiimviglomexc

      - key: MAIL_ENCRYPTION
        value: tls

      - key: MAIL_FROM_ADDRESS
        value: unaturismo3@gmail.com

      - key: MAIL_FROM_NAME
        value: Hotel Lanaku

    # Health check
    healthCheckPath: /

    # Comandos de inicialización
    # ⚠️ Render ejecutará las migraciones automáticamente si lo descomentas
    # buildCommand: "php artisan migrate --force"
```

---

## 🔧 PASOS PARA DESPLEGAR

### Paso 1: Preparar el repositorio

```bash
# 1. Asegúrate de que el proyecto esté en Git
git init
git add .
git commit -m "Preparar para despliegue en Render"

# 2. Sube a GitHub (crea un repo primero en github.com)
git remote add origin https://github.com/tu-usuario/backend-hotel.git
git branch -M main
git push -u origin main
```

### Paso 2: Ajustar el Dockerfile para Render

Render necesita algunas modificaciones. Actualiza tu `.env` para producción:

```env
# Cambiar estas variables para producción en Render
APP_ENV=production
APP_DEBUG=false
REVERB_HOST="backendhotelt.onrender.com"
REVERB_SCHEME=https
REVERB_PORT=443
REDIS_HOST=127.0.0.1
```

### Paso 3: Crear servicio en Render

1. **Ve a [render.com](https://render.com)** y crea una cuenta
2. **Conecta tu repositorio de GitHub**
3. **Selecciona "New Web Service"**
4. **Elige tu repositorio `backend-hotel`**
5. **Configura el servicio:**
   - **Name**: `backend-hotel`
   - **Environment**: `Docker`
   - **Region**: Oregon (o el más cercano)
   - **Branch**: `main`
   - **Plan**: Free (o Starter para más recursos)

6. **Variables de entorno**:
   - Puedes copiarlas del `render.yaml` o agregarlas manualmente
   - ⚠️ **IMPORTANTE**: Las credenciales sensibles márcalas como "secret"

7. **Click en "Create Web Service"**

### Paso 4: Verificar el despliegue

Render automáticamente:
1. ✅ Clonará tu repositorio
2. ✅ Construirá la imagen Docker
3. ✅ Iniciará supervisord (web + queue + reverb)
4. ✅ Asignará una URL pública
5. ✅ Configurará SSL/HTTPS automáticamente

**Tu aplicación estará en**: `https://backendhotelt.onrender.com`

---

## 🔍 VERIFICACIÓN POST-DESPLIEGUE

### 1. Ver logs en tiempo real
En el dashboard de Render → `Logs`:
```
[supervisor] php-server started
[supervisor] queue-worker started
[supervisor] reverb started
```

### 2. Ejecutar migraciones
En el dashboard → `Shell`:
```bash
php artisan migrate --force
php artisan db:seed
```

O agrega esto al `render.yaml`:
```yaml
buildCommand: "php artisan migrate --force && php artisan config:cache"
```

### 3. Verificar servicios
Desde el Shell de Render:
```bash
supervisorctl status

# Deberías ver:
# php-server    RUNNING
# queue-worker  RUNNING
# reverb        RUNNING
```

### 4. Probar API
```bash
curl https://backendhotelt.onrender.com/api/health
```

### 5. Probar WebSocket
Desde tu frontend:
```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: 'wibscmvwkk1ndpotbxdw',
    wsHost: 'backendhotelt.onrender.com',
    wsPort: 443,
    wssPort: 443,
    forceTLS: true,
    enabledTransports: ['ws', 'wss'],
});

Echo.channel('limpiezas')
    .listen('.NuevaLimpiezaAsignada', (e) => {
        console.log('Nueva limpieza:', e);
    });
```

---

## ⚡ OPTIMIZACIONES

### 1. Usar Redis externo (recomendado para producción)

**Opción A: Railway Redis**
```bash
# En Railway, crear un servicio Redis
# Luego agregar a render.yaml:
- key: REDIS_HOST
  value: tu-redis.railway.app
- key: REDIS_PASSWORD
  value: tu-password
```

**Opción B: Upstash Redis (gratis)**
1. Crear cuenta en [upstash.com](https://upstash.com)
2. Crear base de datos Redis
3. Copiar credenciales a Render

### 2. Mejorar el plan (si es necesario)

Plan Free de Render:
- ✅ 750 horas/mes
- ⚠️ Se duerme después de 15 min inactivo
- ⚠️ 512 MB RAM

Plan Starter ($7/mes):
- ✅ Siempre activo
- ✅ 1 GB RAM
- ✅ Mejor rendimiento

### 3. Configurar health checks

Agregar a `render.yaml`:
```yaml
healthCheckPath: /api/health
```

Y crear la ruta en Laravel:
```php
// routes/api.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected'
    ]);
});
```

---

## 🐛 TROUBLESHOOTING

### Problema: El servicio se reinicia constantemente
**Solución**: Verifica logs en Render dashboard. Probablemente falta una variable de entorno crítica.

```bash
# Ver variables configuradas
env | grep APP_KEY
env | grep DB_HOST
```

### Problema: No se conecta a la base de datos
**Solución**: Verifica que Railway MySQL permita conexiones desde Render.

```bash
# En el shell de Render:
php artisan tinker
>>> DB::connection()->getPdo();
```

### Problema: Reverb no se conecta desde el frontend
**Solución**: Asegúrate de usar `wss://` (no `ws://`) y puerto 443.

```javascript
// Frontend
wsHost: 'backendhotelt.onrender.com',
wsPort: 443,
wssPort: 443,
forceTLS: true,  // ← IMPORTANTE
```

### Problema: Queue worker no procesa trabajos
**Solución**: Verifica que supervisord esté corriendo el queue worker.

```bash
# En shell de Render:
supervisorctl status queue-worker
supervisorctl tail -f queue-worker
```

### Problema: Permisos de storage/
**Solución**: El Dockerfile ya configura permisos, pero si hay problemas:

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

---

## 📊 MONITOREO

### Ver logs en tiempo real
Dashboard → `Logs`:
- Logs de supervisord
- Logs de PHP
- Logs de errores

### Métricas
Dashboard → `Metrics`:
- CPU usage
- Memory usage
- Request latency
- HTTP status codes

### Alertas
Configura alertas en Render para:
- Deploy failures
- Service crashes
- High memory usage

---

## 🔐 SEGURIDAD EN PRODUCCIÓN

### ✅ Checklist de seguridad:

```env
# OBLIGATORIO en producción
APP_ENV=production
APP_DEBUG=false

# SSL/TLS
REVERB_SCHEME=https
SESSION_SECURE_COOKIE=true

# CORS restringido
REVERB_ALLOWED_ORIGINS=https://una-hotel-system.vercel.app

# Credenciales fuertes
DB_PASSWORD=<contraseña-fuerte>
REVERB_APP_SECRET=<secret-fuerte>
```

### Habilitar rate limiting

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Tus rutas protegidas
});
```

---

## 💰 COSTOS

### Plan Free:
- ✅ 0 USD/mes
- ✅ 750 horas/mes
- ✅ SSL incluido
- ✅ Builds ilimitados
- ⚠️ Se duerme tras inactividad

### Plan Starter:
- 💵 7 USD/mes por servicio
- ✅ Siempre activo
- ✅ 1 GB RAM
- ✅ Mejor rendimiento

**Total estimado**: $0 - $7/mes (dependiendo del plan)

---

## 🎯 RESUMEN RÁPIDO

```bash
# 1. Crear render.yaml con la configuración de arriba
# 2. Subir a GitHub
git add .
git commit -m "Add Render config"
git push

# 3. Ir a render.com
# 4. Conectar repositorio
# 5. Crear Web Service (Docker)
# 6. Esperar el build (5-10 min)
# 7. Verificar logs
# 8. Ejecutar migraciones desde Shell
php artisan migrate --force

# 9. ¡Listo! 🎉
```

---

## 📞 SIGUIENTE PASO

¿Quieres que te ayude a:
1. ✅ Crear el archivo `render.yaml` completo?
2. ✅ Configurar health checks?
3. ✅ Agregar Redis externo?
4. ✅ Optimizar el Dockerfile para Render?

Házmelo saber y te ayudo con el paso específico.
