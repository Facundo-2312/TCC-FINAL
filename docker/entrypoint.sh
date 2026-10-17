#!/bin/sh
set -e

# Railway inyecta PORT; en local se usa 8080 por defecto.
PORT="${PORT:-8080}"
sed -ri "s/Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Restaura imagenes de ejemplo sin sobrescribir las subidas (el volumen puede estar vacio).
mkdir -p /var/www/html/MVCsix1.0/files /var/www/html/storage/logs
cp -an /opt/seed-files/. /var/www/html/MVCsix1.0/files/
chown -R www-data:www-data /var/www/html/MVCsix1.0/files /var/www/html/storage

exec "$@"
