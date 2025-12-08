#!/bin/sh
set -e

echo "=== Starting Laravel Application ==="

# Создание директорий для логов
mkdir -p /var/log/php
mkdir -p /var/log/nginx
mkdir -p /var/log/supervisor

# Создание директорий Laravel если не существуют
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/bootstrap/cache

# Очистка кэша (для избежания проблем с правами от предыдущих сборок)
rm -rf /var/www/html/storage/framework/cache/data/*
rm -rf /var/www/html/storage/framework/views/*
rm -rf /var/www/html/bootstrap/cache/*.php

# Установка прав (с setgid для сохранения группы при создании файлов)
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache
# Установка setgid бита чтобы новые файлы наследовали группу
find /var/www/html/storage -type d -exec chmod g+s {} \;
find /var/www/html/bootstrap/cache -type d -exec chmod g+s {} \;

# Ожидание готовности базы данных (если переменная DB_HOST установлена)
if [ -n "$DB_HOST" ]; then
    echo "Waiting for database..."
    max_tries=30
    counter=0
    until nc -z -v -w30 $DB_HOST ${DB_PORT:-5432} 2>/dev/null; do
        counter=$((counter + 1))
        if [ $counter -ge $max_tries ]; then
            echo "Database connection failed after $max_tries attempts"
            break
        fi
        echo "Waiting for database connection... ($counter/$max_tries)"
        sleep 2
    done
    echo "Database is ready!"
fi

# Кэширование конфигурации (только если .env существует)
if [ -f /var/www/html/.env ]; then
    echo "Caching configuration..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Запуск миграций (опционально, если MIGRATE_ON_START=true)
if [ "$MIGRATE_ON_START" = "true" ]; then
    echo "Running migrations..."
    php artisan migrate --force || true
fi

# Линковка storage
php artisan storage:link 2>/dev/null || true

echo "=== Application Ready ==="

# Запуск основного процесса
exec "$@"
