# Comandos Rápidos para Docker

## Limpiar contenedores viejos

```bash
# Detener TODOS los contenedores
docker-compose down

# Eliminar volúmenes huérfanos (incluye mysql-1)
docker-compose down -v

# Limpiar contenedores, redes e imágenes sin usar
docker system prune -a

# Verificar que no haya contenedores corriendo
docker ps -a
```

## Levantar servicios limpios

```bash
# Construir e iniciar servicios
docker-compose up -d --build

# Ver qué servicios están corriendo
docker-compose ps

# Ver logs en tiempo real
docker-compose logs -f

# Ver logs solo del queue worker
docker-compose logs -f queue
```

## Verificar que todo funciona

```bash
# 1. Verificar conexión a base de datos externa
docker-compose exec laravel.test php artisan tinker
# Dentro de tinker: DB::connection()->getPdo();

# 2. Verificar que el queue worker está procesando
docker-compose exec laravel.test php artisan queue:work --once

# 3. Ver status del queue worker
docker-compose exec queue ps aux | grep queue

# 4. Probar broadcasting (en tinker)
# broadcast(new App\Events\NuevaLimpiezaAsignada(['test' => 'data']));
```

## Troubleshooting

```bash
# Si un servicio falla, ver sus logs
docker-compose logs laravel.test
docker-compose logs queue
docker-compose logs redis

# Reiniciar un servicio específico
docker-compose restart queue

# Entrar a la shell de un contenedor
docker-compose exec laravel.test bash
docker-compose exec queue bash

# Ver procesos corriendo en el queue worker
docker-compose exec queue supervisorctl status
```
