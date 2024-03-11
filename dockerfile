FROM php:8.1-fpm


RUN apk update && apk add curl && \
  curl -sS https://getcomposer.org/installer | php \
  && chmod +x composer.phar && mv composer.phar /usr/local/bin/composer

# Zainstaluj rozszerzenia PHP
RUN docker-php-ext-install grpc

# Zainstaluj Composer
# COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Skopiuj pliki projektu
COPY . /var/www/html

# Zainstaluj zależności za pomocą Composer
RUN composer install

# Ustaw katalog roboczy
WORKDIR /var/www/html

# Uruchom serwer PHP-FPM
CMD ["php-fpm"]