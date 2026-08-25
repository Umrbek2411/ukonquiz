FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends curl ca-certificates \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && a2enmod rewrite headers

ENV DB_SSL_CA=/etc/ssl/certs/ca-certificates.crt

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl -f http://localhost/index.html || exit 1

EXPOSE 80
