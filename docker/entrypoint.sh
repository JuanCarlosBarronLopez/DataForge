#!/bin/bash
# ============================================
# DataForge — Docker Entrypoint
# ============================================
# Adjusts Apache port for Render/Railway ($PORT env)
# Falls back to port 80 for local Docker usage

set -e

# If PORT env var is set (Render/Railway), update Apache to listen on it
if [ -n "$PORT" ]; then
    echo "🔧 Configuring Apache to listen on port $PORT (cloud deploy)..."
    sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
    sed -i "s/:80/:$PORT/" /etc/apache2/sites-available/000-default.conf
fi

# Ensure required directories exist with proper permissions
mkdir -p /var/www/html/uploads/avatars
mkdir -p /var/www/html/logs/rate_limits
chown -R www-data:www-data /var/www/html/uploads
chown -R www-data:www-data /var/www/html/logs

echo "🚀 Starting DataForge on port ${PORT:-80}..."
exec apache2-foreground
