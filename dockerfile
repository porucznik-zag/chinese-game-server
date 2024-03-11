FROM php:8.1-fpm-buster

# Zainstaluj narzędzie do instalacji rozszerzeń PHP
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Zainstaluj Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Zainstaluj i aktywuj rozszerzenia PHP
RUN install-php-extensions grpc protobuf

# Skopiuj pliki projektu
COPY . /var/www/html

# Zainstaluj zależności za pomocą Composer
RUN composer install --no-dev --optimize-autoloader

# Ustaw katalog roboczy
WORKDIR /var/www/html

# Uruchom serwer PHP-FPM
CMD ["php-fpm"]