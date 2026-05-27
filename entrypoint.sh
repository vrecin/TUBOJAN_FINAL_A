#!/bin/bash
set -e
# Default PORT to 8080
export PORT=${PORT:-8080}
echo "Starting entrypoint, PORT=$PORT"
# Fix permissions
if [ -d /var/www/html/var ]; then
 chmod -R 777 /var/www/html/var || true
 chown -R www-data:www-data /var/www/html/var || true
fi
# START PHP-FPM FIRST (before running Symfony commands)
echo "Starting PHP-FPM..."
php-fpm -D
sleep 2
# Install JS assets and run Symfony setup commands AS www-data
if [ -f bin/console ]; then
 echo "Installing JS assets..."
 su -s /bin/bash www-data -c "php bin/console importmap:install" || true
 echo "Clearing cache..."
 su -s /bin/bash www-data -c "php bin/console cache:clear --env=prod --no-debug" || true
 echo "Running database migrations..."
 su -s /bin/bash www-data -c "php bin/console doctrine:migrations:migrate --no-interaction --env=prod" || true
fi
# Render nginx template and start nginx
if [ -f /etc/nginx/conf.d/default.conf.template ]; then
 echo "Configuring Nginx port ($PORT)..."
 envsubst '${PORT}' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf
fi
echo "Starting Nginx..."
exec nginx -g "daemon off;"