#!/bin/sh
# Startup script for the OSEP API container.
# Render (and most PaaS) inject the port to listen on via $PORT.
set -e

PORT="${PORT:-8080}"
echo "==> Booting OSEP API on port ${PORT}"

# Bind Apache to the assigned port.
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Laravel boot — all guarded so a transient hiccup never blocks startup.
php artisan storage:link 2>/dev/null || true
php artisan config:cache || true
php artisan migrate --force || true

exec apache2-foreground
