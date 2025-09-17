# Utiliser l'image PHP avec Apache
FROM php:8.3-apache

# Installer les extensions PHP nécessaires
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql

# Copier le code de l'application dans le conteneur
COPY . /var/www/html/

# Configurer les permissions
RUN chown -R www-data:www-data /var/www/html

# Activer le module rewrite d'Apache
RUN a2enmod rewrite