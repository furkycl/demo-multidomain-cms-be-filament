#!/usr/bin/env bash
set -e

cd /var/www/html

# Migrate on boot. Safe — Laravel skips already-applied migrations.
php artisan migrate --force || echo "Migration failed; container will continue."

# Re-cache in case env changed
php artisan config:cache
php artisan route:cache

exec "$@"
