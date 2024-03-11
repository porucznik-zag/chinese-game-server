# Użyj oficjalnego obrazu PHP 8.1 z rozszerzeniem grpc
FROM php:8.1-fpm

# Zainstaluj rozszerzenia PHP
RUN docker-php-ext-install grpc

# Zainstaluj Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Ustaw środowisko
ENV APP_ENV=production \
    APP_DEBUG=false

# Skopiuj pliki projektu
COPY . /var/www/html

# Zainstaluj zależności za pomocą Composer
RUN composer install --no-dev --optimize-autoloader

# Ustaw katalog roboczy
WORKDIR /var/www/html

# Uruchom serwer PHP-FPM
CMD ["php-fpm"]