# Dockerfile для Laravel (ManySales) - оптимизирован для Dokploy
FROM php:8.3-fpm-alpine

# Установка зависимостей системы
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    git \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev \
    libzip-dev \
    icu-dev \
    libxml2-dev \
    oniguruma-dev \
    postgresql-dev \
    imagemagick \
    imagemagick-dev \
    ${PHPIZE_DEPS}

# Установка PHP расширений
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pdo_mysql \
        pgsql \
        mysqli \
        gd \
        zip \
        intl \
        bcmath \
        opcache \
        pcntl \
        exif

# Установка Redis расширения
RUN pecl install redis && docker-php-ext-enable redis

# Установка Imagick
RUN pecl install imagick && docker-php-ext-enable imagick

# Очистка
RUN apk del ${PHPIZE_DEPS} && rm -rf /var/cache/apk/*

# Установка Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Настройка рабочей директории
WORKDIR /var/www/html

# Копирование конфигурационных файлов
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Копирование composer файлов отдельно для кэширования
COPY composer.json composer.lock ./

# Установка зависимостей (без dev для production)
RUN COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

# Копирование всего проекта
COPY . .

# Генерация autoload и оптимизация
RUN COMPOSER_MEMORY_LIMIT=-1 composer dump-autoload --optimize --no-dev

# Создание директорий для Laravel
RUN mkdir -p storage/logs \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/framework/cache/data \
    && mkdir -p bootstrap/cache

# Настройка прав доступа (ПОСЛЕ создания директорий)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Копирование и настройка entrypoint скрипта
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Экспорт порта
EXPOSE 80

# Запуск
ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
