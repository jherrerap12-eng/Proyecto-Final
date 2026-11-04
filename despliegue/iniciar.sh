#!/bin/sh
set -eu

# Las credenciales se reciben en tiempo de ejecución desde Render.
: "${APP_KEY:?Configura APP_KEY en Render}"
: "${DB_HOST:?Configura DB_HOST en Render}"
: "${MYSQL_ATTR_SSL_CA:?Configura la ruta del certificado de Aiven}"
test -r "$MYSQL_ATTR_SSL_CA"
sed -i 's/^Listen 80$/Listen 10000/' /etc/apache2/ports.conf
php artisan config:cache
php artisan migrate --force
php artisan view:cache
exec apache2-foreground
