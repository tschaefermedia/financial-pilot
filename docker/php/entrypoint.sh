#!/bin/sh

# Always sync application code from image into volume
# This ensures code updates (including seeders, config, etc.) reach the volume on every deploy
# Preserves: .env, storage/, database/database.sqlite (user data)
echo "Syncing application files from image..."

# Back up user data we want to preserve
[ -f /var/www/html/.env ] && cp /var/www/html/.env /tmp/.env.bak
[ -f /var/www/html/database/database.sqlite ] && cp /var/www/html/database/database.sqlite /tmp/database.sqlite.bak
[ -d /var/www/html/storage ] && cp -r /var/www/html/storage /tmp/storage.bak

# Sync all app files from image
cp -r /opt/app-source/. /var/www/html/

# Restore preserved user data
[ -f /tmp/.env.bak ] && mv /tmp/.env.bak /var/www/html/.env
[ -f /tmp/database.sqlite.bak ] && mv /tmp/database.sqlite.bak /var/www/html/database/database.sqlite
[ -d /tmp/storage.bak ] && cp -r /tmp/storage.bak/. /var/www/html/storage/ && rm -rf /tmp/storage.bak

# Create .env from example if it doesn't exist
if [ ! -f /var/www/html/.env ]; then
    echo "Creating .env from .env.example..."
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Generate app key if not set
if grep -q "^APP_KEY=" /var/www/html/.env && ! grep -q "^APP_KEY=base64:" /var/www/html/.env; then
    KEY=$(php -r "echo 'base64:'.base64_encode(random_bytes(32));")
    sed -i "s|^APP_KEY=.*|APP_KEY=${KEY}|" /var/www/html/.env
    echo "Application key generated: ${KEY}"
fi

# Apply environment variable overrides to .env
for var in APP_NAME APP_ENV APP_DEBUG APP_URL DB_CONNECTION SESSION_DRIVER CACHE_STORE QUEUE_CONNECTION LOG_CHANNEL LOG_LEVEL; do
    eval val=\$$var
    if [ -n "$val" ]; then
        if grep -q "^${var}=" /var/www/html/.env; then
            sed -i "s|^${var}=.*|${var}=${val}|" /var/www/html/.env
        else
            echo "${var}=${val}" >> /var/www/html/.env
        fi
    fi
done

# Ensure storage and cache directories exist
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

# Fix ALL permissions before running any PHP
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database /var/www/html/.env

# Run migrations
echo "Running database migrations..."
php /var/www/html/artisan migrate --force --no-interaction
if [ $? -ne 0 ]; then
    echo "WARNING: Database migrations failed. The application may not work correctly."
fi

# Seed only on fresh install (no categories exist yet)
CATEGORY_COUNT=$(php /var/www/html/artisan tinker --execute="echo \App\Models\Category::count();" 2>/dev/null | tr -d '[:space:]')
if [ "$CATEGORY_COUNT" = "0" ] || [ -z "$CATEGORY_COUNT" ]; then
    echo "Fresh install detected — seeding default categories..."
    php /var/www/html/artisan db:seed --no-interaction --force
fi

# Set up Laravel scheduler cron job
echo "* * * * * cd /var/www/html && php artisan schedule:run >> /var/www/html/storage/logs/scheduler.log 2>&1" | crontab -u www-data -
echo "Starting cron daemon for Laravel scheduler..."
cron

exec "$@"
