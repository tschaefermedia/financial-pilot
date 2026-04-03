#!/bin/sh

# If public/index.php is missing, this is an empty volume — sync app files
if [ ! -f /var/www/html/public/index.php ]; then
    echo "Initializing application files..."
    cp -r /opt/app-source/. /var/www/html/
fi

# Always sync built frontend assets (they live in the image, not the volume)
mkdir -p /var/www/html/public/build
cp -r /opt/app-source/public/build/. /var/www/html/public/build/

# Ensure storage and cache directories exist and are writable
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/app/exports
mkdir -p /var/www/html/storage/app/imports
mkdir -p /var/www/html/bootstrap/cache
mkdir -p /var/www/html/database

# Create SQLite database if it doesn't exist
touch /var/www/html/database/database.sqlite

# Fix permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

exec "$@"
