FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git unzip curl libpq-dev libzip-dev zip \
    && docker-php-ext-install pdo pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN useradd -m -u 1000 appuser

WORKDIR /var/www/html

COPY . .

RUN composer install --no-interaction --no-progress --prefer-dist

RUN chown -R appuser:appuser /var/www/html

USER appuser

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
