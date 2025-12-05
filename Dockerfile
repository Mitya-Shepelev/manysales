# Dockerfile для Laravel (ManySales) - оптимизирован для Dokploy
FROM php:8.2-fpm-alpine

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
    libzip-dev \
    icu-dev \
    libxml2-dev \
    oniguruma-dev \
    postgresql-dev \
    imagemagick \
    imagemagick-dev \
    ${PHPIZE_DEPS}

# Установка PHP расширений
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
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
        exif \
        mbstring \
        xml \
        curl

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
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Копирование всего проекта
COPY . .

# Генерация autoload и оптимизация
RUN composer dump-autoload --optimize --no-dev

# Настройка прав доступа
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Создание директорий если не существуют
RUN mkdir -p storage/logs \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/framework/cache \
    && mkdir -p bootstrap/cache

# Копирование и настройка entrypoint скрипта
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Экспорт порта
EXPOSE 80

# Запуск
ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
