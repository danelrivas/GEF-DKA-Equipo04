#!/bin/sh

set -e

# Esperar a que la base de datos esté lista
echo "Esperando a MySQL..."
until php -r "new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" 2>/dev/null; do
  echo -n "."
  sleep 1
done

echo "MySQL listo"

# Generar APP_KEY si no existe
if [ ! -f /var/www/html/.env ]; then
    cp /var/www/html/.env.example /var/www/html/.env
fi

php artisan key:generate --force
php artisan migrate --force --seed
php artisan config:clear
php artisan config:cache
php artisan route:cache

exec php-fpm
