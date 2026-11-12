#!/bin/sh
# ============================================
# DataForge - Entrypoint del contenedor
# ============================================
set -e

# En Render y Railway el puerto lo fija la plataforma.
if [ -n "${PORT}" ]; then
    echo "Ajustando Apache al puerto ${PORT}"
    sed -ri "s!Listen 80!Listen ${PORT}!g" /etc/apache2/ports.conf
    sed -ri "s!<VirtualHost \*:80>!<VirtualHost *:${PORT}>!g" /etc/apache2/sites-available/000-default.conf
fi

# Los directorios de trabajo deben existir y ser escribibles por el usuario del
# servidor web.
mkdir -p \
    /var/www/html/storage/logs \
    /var/www/html/storage/cache/rate-limit \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/uploads/avatars

chown -R www-data:www-data /var/www/html/storage
chmod -R 750 /var/www/html/storage

# Las migraciones se aplican al arrancar. SKIP_MIGRATIONS=1 lo desactiva.
# En el primer arranque MySQL puede tardar en aceptar conexiones, asi que se
# reintenta unas cuantas veces antes de seguir.
if [ "${SKIP_MIGRATIONS:-0}" != "1" ]; then
    echo "Aplicando migraciones"
    ATTEMPTS=0
    until php /var/www/html/bin/console migrate; do
        ATTEMPTS=$((ATTEMPTS + 1))
        if [ "$ATTEMPTS" -ge 10 ]; then
            echo "Aviso: las migraciones no pudieron aplicarse en este arranque"
            break
        fi
        echo "Reintento $ATTEMPTS: la base de datos todavia no responde"
        sleep 3
    done
fi

exec "$@"
