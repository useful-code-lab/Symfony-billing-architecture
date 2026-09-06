FROM php:8.3-fpm-alpine

# Устанавливаем системные зависимости и расширение pdo_pgsql для PostgreSQL
RUN apk add --no-cache \
        postgresql-dev \
        bash \
    && docker-php-ext-install pdo_pgsql

# Копируем официальный бинарник Composer из его Docker-образа
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Настраиваем рабочую директорию внутри контейнера
WORKDIR /var/www/billing

# Запускаем контейнер от имени встроенного пользователя www-data (Senior-практика безопасности)
USER www-data

EXPOSE 9000
CMD ["php-fpm"]
