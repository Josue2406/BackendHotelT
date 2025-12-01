# ✅ Checklist Completo para Despliegue

## 📦 ANTES DE DESPLEGAR

### 1. Limpiar Docker (si venías de versión anterior)
```bash
# Detener y eliminar contenedores viejos
docker-compose down -v

# Limpiar sistema Docker
docker system prune -a

# Verificar que no queden contenedores
docker ps -a
```

### 2. Verificar archivos críticos
- [x] `.env` con credenciales correctas
- [x] `docker-compose.yml` sin MySQL local
- [x] `Dockerfile` con supervisord
- [x] `supervisord.conf` con 3 procesos (web + queue + reverb)
- [x] `config/reverb.php` con CORS restringido
- [x] `.dockerignore` configurado

### 3. Variables de entorno críticas en `.env`
```env
# ✅ Base de datos externa (Railway)
DB_CONNECTION=mysql
DB_HOST=yamanote.proxy.rlwy.net
DB_PORT=31248
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=GXQOumMdKxjXpVwRxOagzxiZNoZXJlNo

# ✅ Broadcasting
BROADCAST_CONNECTION=reverb

# ✅ Queue
QUEUE_CONNECTION=database

# ✅ Redis (nombre del servicio Docker)
REDIS_HOST=redis

# ✅ Reverb WebSocket
REVERB_APP_ID=897815
REVERB_APP_KEY=wibscmvwkk1ndpotbxdw
REVERB_APP_SECRET=smjzmkrz7ztwlqcejngw
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_ALLOWED_ORIGINS=https://una-hotel-system.vercel.app,http://localhost:5173,http://localhost:5174

# ⚠️ PARA PRODUCCIÓN cambiar:
# REVERB_HOST="tu-dominio.com"
# REVERB_SCHEME=https
# REVERB_PORT=443
```

---

## 🚀 OPCIÓN 1: Desarrollo Local (Docker Compose)

### Paso 1: Levantar servicios
```bash
# Construir e iniciar
docker-compose up -d --build

# Ver qué servicios están corriendo (deberías ver 7 servicios)
docker-compose ps
```

**Servicios esperados:**
- ✅ `laravel.test` - Aplicación principal (puerto 80)
- ✅ `queue` - Queue worker
- ✅ `reverb` - WebSocket server (puerto 8080)
- ✅ `redis` - Cache y sessions
- ✅ `meilisearch` - Búsqueda
- ✅ `mailpit` - Email testing
- ✅ `selenium` - Browser testing

### Paso 2: Ejecutar migraciones
```bash
# Primera vez solamente
docker-compose exec laravel.test php artisan migrate --seed

# O sin seeders
docker-compose exec laravel.test php artisan migrate
```

### Paso 3: Verificar que todo funciona

#### 3.1 Verificar BD externa
```bash
docker-compose exec laravel.test php artisan tinker
# Dentro de tinker:
>>> DB::connection()->getPdo();
>>> DB::table('users')->count();
>>> exit
```

#### 3.2 Verificar queue worker
```bash
# Ver logs del queue
docker-compose logs -f queue

# Debe mostrar: "Processing jobs from the queue..."
```

#### 3.3 Verificar Reverb WebSocket
```bash
# Ver logs de Reverb
docker-compose logs -f reverb

# Debe mostrar: "Reverb server started on 0.0.0.0:8080"
```

#### 3.4 Probar broadcasting
```bash
docker-compose exec laravel.test php artisan tinker
# Dentro de tinker:
>>> broadcast(new App\Events\NuevaLimpiezaAsignada(['habitacion' => 101, 'tipo' => 'completa']));
>>> exit

# Luego verifica los logs del queue
docker-compose logs queue
```

### Paso 4: Acceder a la aplicación
- **API**: http://localhost
- **Reverb WebSocket**: ws://localhost:8080
- **Mailpit UI**: http://localhost:8025
- **Meilisearch**: http://localhost:7700

---

## 🌐 OPCIÓN 2: Producción (Render/Railway/etc)

### Paso 1: Preparar `.env` para producción
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

# Reverb en producción
REVERB_HOST="tu-dominio.com"
REVERB_SCHEME=https
REVERB_PORT=443
REVERB_ALLOWED_ORIGINS=https://una-hotel-system.vercel.app

# Si usas Redis externo (recomendado)
REDIS_HOST=tu-redis-externo.com
REDIS_PASSWORD=tu-password
```

### Paso 2: Construir imagen
```bash
docker build -t backend-hotel:latest .
```

### Paso 3: Ejecutar contenedor
```bash
docker run -d \
  --name backend-hotel \
  -p 10000:10000 \
  -p 8080:8080 \
  -e APP_ENV=production \
  -e APP_DEBUG=false \
  -e APP_KEY=base64:oaulL3b2rlL+N26JxhQXyTaJmkxCP7m1BMUIFA2p6sA= \
  -e APP_URL=https://backendhotelt.onrender.com \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=yamanote.proxy.rlwy.net \
  -e DB_PORT=31248 \
  -e DB_DATABASE=railway \
  -e DB_USERNAME=root \
  -e DB_PASSWORD=GXQOumMdKxjXpVwRxOagzxiZNoZXJlNo \
  -e BROADCAST_CONNECTION=reverb \
  -e QUEUE_CONNECTION=database \
  -e REVERB_APP_ID=897815 \
  -e REVERB_APP_KEY=wibscmvwkk1ndpotbxdw \
  -e REVERB_APP_SECRET=smjzmkrz7ztwlqcejngw \
  -e REVERB_HOST="backendhotelt.onrender.com" \
  -e REVERB_PORT=443 \
  -e REVERB_SCHEME=https \
  -e REVERB_ALLOWED_ORIGINS=https://una-hotel-system.vercel.app \
  backend-hotel:latest
```

### Paso 4: Verificar procesos en el contenedor
```bash
# Ver status de supervisord
docker exec backend-hotel supervisorctl status

# Deberías ver:
# php-server    RUNNING   pid X, uptime 0:00:XX
# queue-worker  RUNNING   pid X, uptime 0:00:XX
# reverb        RUNNING   pid X, uptime 0:00:XX
```

### Paso 5: Ver logs
```bash
# Logs del servidor web
docker exec backend-hotel tail -f /var/log/supervisor/php-server.out.log

# Logs del queue worker
docker exec backend-hotel tail -f /var/log/supervisor/queue-worker.out.log

# Logs de Reverb
docker exec backend-hotel tail -f /var/log/supervisor/reverb.out.log
```

### Paso 6: Ejecutar migraciones en producción
```bash
docker exec backend-hotel php artisan migrate --force
```

---

## 🔍 VERIFICACIÓN FINAL

### ✅ Checklist de funcionalidad

```bash
# 1. ¿La API responde?
curl http://localhost:10000/api/health

# 2. ¿La BD externa está conectada?
docker exec backend-hotel php artisan tinker
>>> DB::connection()->getPdo();

# 3. ¿El queue worker está procesando?
docker exec backend-hotel supervisorctl status queue-worker
# Debe mostrar: RUNNING

# 4. ¿Reverb está corriendo?
docker exec backend-hotel supervisorctl status reverb
# Debe mostrar: RUNNING

# 5. ¿Los eventos de broadcasting funcionan?
# Desde tu frontend conectado a ws://localhost:8080
# Dispara un evento y verifica que llegue

# 6. ¿Las notificaciones se envían?
# Crea una limpieza desde el API y verifica logs:
docker-compose logs -f queue
```

---

## 🐛 TROUBLESHOOTING

### Problema: MySQL no se levanta
**Solución**: Es normal, ya lo removimos. Usas Railway MySQL externo.

### Problema: Queue worker no procesa trabajos
```bash
# 1. Verifica que la tabla jobs existe
docker exec backend-hotel php artisan migrate

# 2. Verifica que QUEUE_CONNECTION=database
docker exec backend-hotel php artisan tinker
>>> config('queue.default');

# 3. Reinicia el queue worker
docker-compose restart queue
# O en producción:
docker exec backend-hotel supervisorctl restart queue-worker
```

### Problema: Reverb no se conecta desde el frontend
```bash
# 1. Verifica que está corriendo
docker exec backend-hotel supervisorctl status reverb

# 2. Verifica los allowed_origins
docker exec backend-hotel php artisan tinker
>>> config('reverb.apps.apps')[0]['allowed_origins'];

# 3. Verifica que el puerto 8080 está expuesto
docker ps
# Debe mostrar: 0.0.0.0:8080->8080/tcp

# 4. En producción, verifica que usas wss:// (no ws://)
```

### Problema: Error de permisos en storage/
```bash
docker exec backend-hotel chown -R www-data:www-data storage bootstrap/cache
docker exec backend-hotel chmod -R 775 storage bootstrap/cache
```

### Problema: No se pueden conectar a Redis
```bash
# En Docker Compose (desarrollo):
# Asegúrate que REDIS_HOST=redis (nombre del servicio)

# En producción con Redis externo:
# REDIS_HOST debe ser la IP/dominio del servicio externo
```

---

## 📊 MONITOREO

### Ver logs en tiempo real
```bash
# Todos los servicios
docker-compose logs -f

# Servicio específico
docker-compose logs -f queue
docker-compose logs -f reverb
docker-compose logs -f laravel.test

# En producción
docker logs -f backend-hotel
```

### Verificar tabla de trabajos pendientes
```bash
docker exec backend-hotel php artisan queue:work --once
docker exec backend-hotel php artisan queue:failed
```

### Reiniciar servicios sin downtime
```bash
# Desarrollo
docker-compose restart queue
docker-compose restart reverb

# Producción
docker exec backend-hotel supervisorctl restart queue-worker
docker exec backend-hotel supervisorctl restart reverb
```

---

## 🎯 RESUMEN DE PUERTOS

| Servicio | Puerto Local | Puerto Producción | Descripción |
|----------|-------------|------------------|-------------|
| API Laravel | 80 | 10000 | REST API |
| Reverb WebSocket | 8080 | 8080 (443 con SSL) | Broadcasting en tiempo real |
| Redis | 6379 | - | Cache/Sessions |
| Mailpit SMTP | 1025 | - | Email testing |
| Mailpit UI | 8025 | - | Ver emails enviados |
| Meilisearch | 7700 | - | Motor de búsqueda |

---

## 🔐 SEGURIDAD

### ✅ Configurado correctamente:
- [x] CORS restringido en Reverb
- [x] Credenciales en .env (no en código)
- [x] .dockerignore excluye .env
- [x] Session cookies: HttpOnly, Secure, SameSite

### ⚠️ ANTES DE PRODUCCIÓN:
- [ ] APP_DEBUG=false
- [ ] Cambiar REVERB_SCHEME=https
- [ ] Configurar certificados SSL/TLS
- [ ] Usar contraseñas fuertes
- [ ] Habilitar rate limiting
- [ ] Configurar backups automáticos de BD

---

## 📞 SOPORTE

Si encuentras problemas:

1. **Revisa logs primero**: `docker-compose logs -f`
2. **Verifica variables de entorno**: `docker exec laravel.test php artisan tinker` → `config('database.default')`
3. **Limpia cache**: `docker exec laravel.test php artisan config:clear`
4. **Reinicia servicios**: `docker-compose restart`

---

✅ **TODO LISTO PARA DESPLEGAR**

Ahora puedes ejecutar:
```bash
docker-compose down -v
docker-compose up -d --build
docker-compose logs -f
```
