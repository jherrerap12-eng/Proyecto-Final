#!/bin/sh
set -eu

# Las credenciales se reciben en tiempo de ejecución desde Render.
: "${APP_KEY:?Configura APP_KEY en Render}"
: "${DB_HOST:?Configura DB_HOST en Render}"
: "${MYSQL_ATTR_SSL_CA:?Configura la ruta del certificado de Aiven}"
test -r "$MYSQL_ATTR_SSL_CA"
# Los Secret Files pueden ser legibles solo por root. Artisan arranca como root,
# pero Apache atiende como www-data: darle acceso solo a una copia del CA público.
mkdir -p /var/www/html/storage/certificados
install -o www-data -g www-data -m 0644 "$MYSQL_ATTR_SSL_CA" /var/www/html/storage/certificados/aiven-ca.pem
export MYSQL_ATTR_SSL_CA=/var/www/html/storage/certificados/aiven-ca.pem
sed -i 's/^Listen 80$/Listen 10000/' /etc/apache2/ports.conf
php artisan config:cache
php artisan migrate --force
php artisan view:cache
exec apache2-foreground
