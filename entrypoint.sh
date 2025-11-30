#!/bin/sh

set -e

echo "Running migrations..."
php artisan migrate --force

echo "Running seeders..."
php artisan db:seed --force

echo "Starting Nginx + PHP-FPM..."
exec /start.sh
