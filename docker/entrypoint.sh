#!/bin/bash
set -e

# Мы уже в /var/www/html (из-за WORKDIR)
# Создаем структуру. Эти папки появятся в ./laravel на хосте
mkdir -p storage/framework/{views,cache,sessions}
mkdir -p storage/logs
mkdir -p storage/app/public
mkdir -p storage/app/private

# Права для www-data (пользователь PHP)
chown -R www-data:www-data storage
chmod -R 775 storage

exec "$@"