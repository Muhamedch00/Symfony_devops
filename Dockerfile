FROM php:8.2-cli

# 1. Installer les paquets nécessaires
RUN apt-get update && apt-get install -y \
    git unzip curl libpq-dev libzip-dev zip \
    && docker-php-ext-install pdo pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

# 2. Ajouter Composer depuis l'image officielle
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 3. Créer un utilisateur non-root
RUN useradd -m -u 1000 appuser

# 4. Définir le répertoire de travail
WORKDIR /var/www/html

# 5. Copier les fichiers
COPY . .

# 6. Installer les dépendances avec Composer
RUN composer install --no-interaction --no-progress --prefer-dist

# 7. Donner les bons droits à l'utilisateur non-root
RUN chown -R appuser:appuser /var/www/html

# 8. Passer à l'utilisateur non-root
USER appuser

# 9. Exposer le port et démarrer le serveur PHP
EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
