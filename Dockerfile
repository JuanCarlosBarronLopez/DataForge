# ============================================
# DataForge CRUD Manager — Production Dockerfile
# ============================================
# Multi-stage ready PHP 8.2 + Apache image
# Supports Render/Railway via $PORT env variable
#
# Usage:
#   docker build -t dataforge .
#   docker run -p 8080:80 dataforge

FROM php:8.2-apache

LABEL maintainer="Raju Technology"
LABEL description="DataForge CRUD Manager — Professional MySQL Management"

# ─── Install PHP Extensions ──────────────────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libzip-dev \
        unzip \
        curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        mysqli \
        pdo_mysql \
        gd \
        zip \
    && apt-get purge -y --auto-remove \
    && rm -rf /var/lib/apt/lists/*

# ─── Enable Apache Modules ──────────────────────────────────────────────────
RUN a2enmod rewrite headers

# ─── Copy Custom Apache Config ──────────────────────────────────────────────
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# ─── Copy Application Code ──────────────────────────────────────────────────
COPY . /var/www/html/

# ─── Set Permissions ────────────────────────────────────────────────────────
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /var/www/html/uploads/avatars \
    && mkdir -p /var/www/html/logs/rate_limits \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/logs

# ─── PHP Configuration ──────────────────────────────────────────────────────
RUN echo "upload_max_filesize = 10M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 12M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 128M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 30" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "session.cookie_httponly = 1" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "session.cookie_samesite = Strict" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "expose_php = Off" >> /usr/local/etc/php/conf.d/uploads.ini

# ─── Copy Entrypoint ────────────────────────────────────────────────────────
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# ─── Expose & Run ───────────────────────────────────────────────────────────
EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
