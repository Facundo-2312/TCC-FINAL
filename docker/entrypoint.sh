#!/bin/sh
set -e

# mod_php exige un unico MPM (prefork); evita "More than one MPM loaded".
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*
a2enmod mpm_prefork >/dev/null 2>&1 || true

# Railway inyecta PORT; en local se usa 8080 por defecto.
PORT="${PORT:-8080}"
sed -ri "s/Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Restaura imagenes de ejemplo sin sobrescribir las subidas (el volumen puede estar vacio).
mkdir -p /var/www/html/MVCsix1.0/files /var/www/html/storage/logs
cp -an /opt/seed-files/. /var/www/html/MVCsix1.0/files/
chown -R www-data:www-data /var/www/html/MVCsix1.0/files /var/www/html/storage

exec "$@"
