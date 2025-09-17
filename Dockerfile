# ===== PHP 8.3 con extensiones comunes para Laravel =====
FROM php:8.3-cli

# Opcional: variable para controlar si instalas dev deps en composer
ARG COMPOSER_NO_DEV=1

# Paquetes del sistema y extensiones PHP
RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libjpeg-dev libfreetype6-dev libzip-dev libonig-dev libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql mbstring zip exif bcmath opcache \
    && rm -rf /var/lib/apt/lists/*

# Composer (desde imagen oficial)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Directorio de trabajo
WORKDIR /var/www/html

# Aprovecha caché: primero manifiestos de Composer
COPY composer.json composer.lock ./
RUN if [ "$COMPOSER_NO_DEV" = "1" ]; then \
      composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts; \
    else \
      composer install --prefer-dist --no-interaction --no-progress --no-scripts; \
    fi

# Copia el resto del proyecto
COPY . .

# Permisos para Laravel
RUN chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# Render inyecta $PORT, así que lo usamos para el servidor embebido
ENV PORT=10000

# Exponer el puerto (Render igualmente inyecta uno)
EXPOSE 10000

# Arrancar servidor embebido de PHP sirviendo la carpeta public
CMD ["sh", "-lc", "php -S 0.0.0.0:$PORT -t public"]
