FROM php:8.3-fpm
RUN apt-get update && apt-get install -y libicu-dev default-mysql-client git unzip zip \
    && docker-php-ext-install intl pdo_mysql opcache \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
# Pas d'en-tête X-Powered-By : la version exacte de PHP n'aide qu'à cibler des failles connues
RUN echo "expose_php=Off" > /usr/local/etc/php/conf.d/security.ini
WORKDIR /var/www/html
RUN usermod -u 1000 www-data || true
