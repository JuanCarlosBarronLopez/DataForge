# ============================================
# Entorno de desarrollo y CI
# ============================================
# Imagen con las extensiones que la aplicacion necesita en pruebas:
#   docker build -t dataforge-dev -f docker/dev.Dockerfile docker
#   docker run --rm -v "$(pwd)":/app -w /app dataforge-dev composer install
#   docker run --rm -v "$(pwd)":/app -w /app dataforge-dev composer check

FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip libzip-dev \
    && docker-php-ext-install pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
