FROM php:8.2-fpm

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git unzip curl libpq-dev libzip-dev zip \
    && docker-php-ext-install pdo pdo_mysql zip

# Installer Composer (depuis image officielle)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers du projet Symfony dans le conteneur
COPY . .

# Installer les dépendances PHP de Symfony
RUN composer install --no-interaction --no-progress --prefer-dist

# Définir les droits (optionnel selon les besoins)
RUN chown -R www-data:www-data /var/www/html

# Exposer le port FPM
EXPOSE 9000

# Lancer PHP-FPM au démarrage du conteneur
CMD ["php-fpm"]
