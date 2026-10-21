FROM php:8.5-fpm
# OPcache est intégré d'office depuis PHP 8.5 : plus besoin de l'installer
RUN apt-get update && apt-get install -y libicu-dev default-mysql-client git unzip zip \
    && docker-php-ext-install intl pdo_mysql \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
# Pas d'en-tête X-Powered-By : la version exacte de PHP n'aide qu'à cibler des failles connues
# Upload progress désactivé : inutilisé ici, et vecteur de CVE-2026-109619 (défense en profondeur)
RUN printf "expose_php=Off\nsession.upload_progress.enabled=0\n" > /usr/local/etc/php/conf.d/security.ini
WORKDIR /var/www/html
RUN usermod -u 1000 www-data || true
