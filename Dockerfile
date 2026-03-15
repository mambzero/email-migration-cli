FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git curl libzip-dev zip unzip libpq-dev netcat-openbsd \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_pgsql zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN composer install --no-interaction --no-scripts

COPY --chown=www-data:www-data . /app

RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

RUN chmod -R 775 /app/storage /app/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
