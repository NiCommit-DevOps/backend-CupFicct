#!/usr/bin/env bash
# CUP FICCT — arranque del backend en Railway.
set -e

PORT="${PORT:-8080}"

# --- Apache escuchando en el puerto dinámico de Railway ---
echo "Listen ${PORT}" > /etc/apache2/ports.conf

cat > /etc/apache2/sites-available/000-default.conf <<EOF
<VirtualHost *:${PORT}>
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

cd /var/www/html

# Permisos de escritura (storage/cache) por si el volumen cambió.
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# Limpia config cacheada y aplica migraciones (crítico) + seeders (idempotentes).
php artisan config:clear || true
php artisan migrate --force
php artisan db:seed --force || echo "[entrypoint] Seeding omitido/fallido — continuando."

exec apache2-foreground
