#!/bin/bash
set -e

# Ensure storage, bootstrap, and database directories exist
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache \
         /var/www/html/database

# Ensure database.sqlite exists for SQLite connection
if [ ! -f /var/www/html/database/database.sqlite ]; then
    touch /var/www/html/database/database.sqlite
fi

# Fix permissions
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database 2>/dev/null || true

# Create storage symlink if it doesn't already exist
if [ ! -L /var/www/html/public/storage ]; then
    php /var/www/html/artisan storage:link 2>/dev/null || true
fi

# Remove public/hot if present
rm -f /var/www/html/public/hot 2>/dev/null || true

exec "$@"
