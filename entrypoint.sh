#!/bin/bash
set -e

echo "==================================================="
echo "== Voting App: Railway Container Starting =="
echo "==================================================="

# 1. Resolve Apache AH00534 MPM conflict
echo "[1/3] Enforcing single prefork MPM module..."
a2dismod mpm_event 2>/dev/null || true
a2dismod mpm_worker 2>/dev/null || true
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* 2>/dev/null || true
a2enmod mpm_prefork rewrite headers 2>/dev/null || true

# 2. Configure dynamic Railway PORT
APP_PORT="${PORT:-8080}"
echo "[2/3] Configuring Apache to listen on port ${APP_PORT}..."
sed -i "s/Listen [0-9]*/Listen ${APP_PORT}/g" /etc/apache2/ports.conf 2>/dev/null || true
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:${APP_PORT}>/g" /etc/apache2/sites-available/*.conf 2>/dev/null || true

# 3. Ensure permissions for runtime uploads and backups
echo "[3/3] Setting directory permissions..."
mkdir -p /var/www/html/frontend/assets/uploads /var/www/html/backend/backups
chown -R www-data:www-data /var/www/html/frontend/assets/uploads /var/www/html/backend/backups
chmod -R 775 /var/www/html/frontend/assets/uploads /var/www/html/backend/backups

echo "Active MPMs in Apache:"
ls -la /etc/apache2/mods-enabled/mpm* 2>/dev/null || true

echo "=== Starting Apache in foreground on port ${APP_PORT} ==="
if [ "$#" -gt 0 ] && [ "$1" != "apache2-foreground" ]; then
    exec "$@"
else
    exec apache2-foreground
fi
