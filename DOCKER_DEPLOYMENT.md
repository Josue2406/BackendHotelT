# Guía de Despliegue con Docker

## Cambios Realizados

### 1. Docker Compose (Desarrollo Local)
- **Removido**: Servicio MySQL local (usas BD externa)
- **Agregado**: Servicio dedicado `queue` para el queue worker
- **Configurado**: Variables de entorno para conectar a BD externa

### 2. Dockerfile (Producción)
- **Agregado**: Supervisord para gestionar múltiples procesos
- **Configurado**: Servidor web PHP + Queue Worker en un solo contenedor

### 3. Supervisord
- **Proceso 1**: Servidor web PHP en puerto configurado
- **Proceso 2**: Queue worker para procesar eventos de broadcasting

---

## Uso con Docker Compose (Desarrollo)

### 1. Configurar variables de entorno

Copia el archivo `.env.example` a `.env` y configura tus credenciales de BD externa:

```bash
cp .env.example .env
```

Edita el archivo `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=tu-host-mysql-externo.com
DB_PORT=3306
DB_DATABASE=nombre_base_datos
DB_USERNAME=usuario_bd
DB_PASSWORD=contraseña_bd

# Configuración de broadcasting (si usas Reverb)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=tu_app_id
REVERB_APP_KEY=tu_app_key
REVERB_APP_SECRET=tu_app_secret
```

### 2. Levantar los servicios

```bash
# Construir e iniciar todos los servicios
docker-compose up -d

# Ver logs del servidor web
docker-compose logs -f laravel.test

# Ver logs del queue worker
docker-compose logs -f queue
```

### 3. Comandos útiles

```bash
# Ejecutar migraciones
docker-compose exec laravel.test php artisan migrate

# Limpiar cache
docker-compose exec laravel.test php artisan cache:clear

# Ver estado de los servicios
docker-compose ps

# Detener servicios
docker-compose down
```

---

## Uso con Dockerfile (Producción)

### 1. Construir la imagen

```bash
docker build -t backend-hotel:latest .
```

### 2. Ejecutar el contenedor

```bash
docker run -d \
  --name backend-hotel \
  -p 10000:10000 \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=tu-host-mysql.com \
  -e DB_PORT=3306 \
  -e DB_DATABASE=tu_base_datos \
  -e DB_USERNAME=tu_usuario \
  -e DB_PASSWORD=tu_contraseña \
  -e REVERB_APP_ID=tu_app_id \
  -e REVERB_APP_KEY=tu_app_key \
  -e REVERB_APP_SECRET=tu_app_secret \
  backend-hotel:latest
```

### 3. Ver logs

```bash
# Logs generales del contenedor
docker logs -f backend-hotel

# Logs del servidor web (dentro del contenedor)
docker exec backend-hotel tail -f /var/log/supervisor/php-server.out.log

# Logs del queue worker (dentro del contenedor)
docker exec backend-hotel tail -f /var/log/supervisor/queue-worker.out.log
```

### 4. Ejecutar comandos dentro del contenedor

```bash
# Ejecutar migraciones
docker exec backend-hotel php artisan migrate

# Limpiar cache
docker exec backend-hotel php artisan cache:clear

# Acceder a la shell del contenedor
docker exec -it backend-hotel bash
```

---

## Servicios Incluidos

### Docker Compose
- **laravel.test**: Aplicación Laravel principal
- **queue**: Queue worker para eventos de broadcasting
- **redis**: Cache y sessions
- **meilisearch**: Motor de búsqueda
- **mailpit**: Servidor SMTP de desarrollo
- **selenium**: Testing browser

### Dockerfile (Producción)
- **php-server**: Servidor web PHP embebido
- **queue-worker**: Procesador de colas y eventos

---

## Variables de Entorno Requeridas

### Base de Datos Externa (Obligatorio)
```env
DB_CONNECTION=mysql
DB_HOST=tu-host.com
DB_PORT=3306
DB_DATABASE=nombre_bd
DB_USERNAME=usuario
DB_PASSWORD=contraseña
```

### Broadcasting (Para notificaciones en tiempo real)
```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=tu_app_id
REVERB_APP_KEY=tu_app_key
REVERB_APP_SECRET=tu_app_secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

### Laravel Básico
```env
APP_KEY=base64:tu-app-key-generada
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com
```

---

## Verificación de Funcionamiento

### 1. Verificar servidor web
```bash
curl http://localhost:10000/api/health
```

### 2. Verificar queue worker
```bash
# Docker Compose
docker-compose exec queue php artisan queue:work --once

# Dockerfile
docker exec backend-hotel supervisorctl status queue-worker
```

### 3. Verificar conexión a BD externa
```bash
# Docker Compose
docker-compose exec laravel.test php artisan tinker
# En tinker: DB::connection()->getPdo();

# Dockerfile
docker exec backend-hotel php artisan tinker
# En tinker: DB::connection()->getPdo();
```

---

## Troubleshooting

### Queue worker no procesa eventos
1. Verifica que `QUEUE_CONNECTION=database` en `.env`
2. Verifica que la tabla `jobs` existe: `php artisan migrate`
3. Revisa logs: `docker-compose logs -f queue`

### No se conecta a BD externa
1. Verifica credenciales en `.env`
2. Verifica que el host de BD permite conexiones desde Docker
3. Prueba conexión: `docker-compose exec laravel.test php artisan tinker`

### Broadcasting no funciona
1. Verifica configuración de Reverb en `.env`
2. Asegúrate de tener instalado Laravel Reverb: `composer require laravel/reverb`
3. Publica configuración: `php artisan reverb:install`

---

## Notas Importantes

1. **Base de datos externa**: Asegúrate de que el firewall permita conexiones desde tu contenedor Docker
2. **Queue worker**: Los eventos de broadcasting requieren que el queue worker esté corriendo
3. **Supervisord**: En producción, ambos procesos (web + queue) corren automáticamente
4. **Logs**: Supervisord gestiona logs en `/var/log/supervisor/`
5. **Reinicio automático**: Si un proceso falla, supervisord lo reinicia automáticamente
