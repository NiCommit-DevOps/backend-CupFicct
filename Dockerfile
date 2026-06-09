# syntax=docker/dockerfile:1
# CUP FICCT — Backend (Laravel 12 / PHP 8.2) para Railway.
FROM php:8.2-cli

# --- Dependencias del sistema y extensiones PHP (PostgreSQL incluido) ---
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libpq-dev libzip-dev libonig-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring bcmath zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer (desde la imagen oficial).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiamos el código (el .env local queda fuera vía .dockerignore: la config
# llega por variables de entorno de Railway).
COPY . .

# Dependencias de producción + autoloader optimizado.
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Railway inyecta $PORT; usamos 8080 como valor por defecto local.
ENV PORT=8080
EXPOSE 8080

# Arranca Laravel en el puerto que Railway espera.
CMD php artisan serve --host=0.0.0.0 --port=${PORT}
