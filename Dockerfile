# ============================================
# DataForge - Imagen de produccion
# ============================================
# Construye la aplicacion con sus dependencias y la deja servida por Apache
# con el front controller en public/.
#
# Construccion:
#   docker build -t dataforge .
#   docker run --rm -p 8080:80 -e DB_HOST=... dataforge

FROM php:8.5-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1

WORKDIR /var/www/html

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/apache-global.conf /etc/apache2/conf-available/dataforge.conf
RUN a2enconf dataforge

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader --no-progress

COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts \
    && chmod +x bin/console \
    && mkdir -p storage/logs storage/cache/rate-limit storage/framework/sessions storage/uploads/avatars \
    && chown -R www-data:www-data storage \
    && chmod -R 750 storage

COPY docker/php-production.ini /usr/local/etc/php/conf.d/dataforge.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
