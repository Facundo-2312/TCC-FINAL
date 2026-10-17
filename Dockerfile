FROM php:8.2-apache

# mbstring, fileinfo, json y session ya vienen compilados en la imagen oficial; solo falta mysqli.
RUN docker-php-ext-install mysqli \
    && a2enmod rewrite headers \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/apache.conf /etc/apache2/conf-available/app.conf
RUN a2enconf app

WORKDIR /var/www/html
COPY . /var/www/html

# Copia de las imagenes de ejemplo: el volumen montado sobre MVCsix1.0/files las oculta,
# por eso el entrypoint las restaura si faltan.
RUN mkdir -p /opt/seed-files storage/logs MVCsix1.0/files \
    && cp -a MVCsix1.0/files/. /opt/seed-files/ \
    && chown -R www-data:www-data storage MVCsix1.0/files

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

ENV APP_ENV=production
EXPOSE 8080
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
