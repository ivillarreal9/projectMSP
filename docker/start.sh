#!/bin/bash

set -e

echo "🚀 Iniciando projectMSP..."

# Esperar a que MySQL esté listo
if [ "$DB_CONNECTION" = "mysql" ]; then
    echo "⏳ Esperando base de datos..."
    until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
        sleep 2
    done
    echo "✅ Base de datos lista"
fi

# Generar key si no existe
php artisan key:generate --no-interaction --force 2>/dev/null || true

# Cache de configuración
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migraciones automáticas
php artisan migrate --force --no-interaction

# Crear link de storage si no existe
php artisan storage:link 2>/dev/null || true

# Permisos de storage
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Crear directorio de logs de supervisor
mkdir -p /var/log/supervisor

echo "✅ projectMSP listo en puerto 80"

# Iniciar todos los procesos via Supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
