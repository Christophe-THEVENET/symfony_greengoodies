FROM php:8.3-fpm
RUN apt-get update && apt-get install -y libicu-dev default-mysql-client git unzip zip \
    && docker-php-ext-install intl pdo_mysql opcache \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
RUN usermod -u 1000 www-data || true
