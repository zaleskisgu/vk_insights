#!/bin/sh
set -e
cd /var/www/html

if [ ! -f .env ]; then
  cp .env.example .env
fi

if ! grep -qE '^APP_KEY=base64:' .env 2>/dev/null; then
  php artisan key:generate --force
fi

mkdir -p database storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
touch database/database.sqlite

php artisan migrate --force

exec php artisan serve --host=0.0.0.0 --port=8000
