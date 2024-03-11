FROM php:8.1-fpm-buster

# Zainstaluj Nginx
RUN apt-get update && apt-get install -y nginx

# Zainstaluj narzędzie do instalacji rozszerzeń PHP
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/

# Zainstaluj Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Zainstaluj i aktywuj rozszerzenia PHP
RUN install-php-extensions grpc-1.62.0

# Skopiuj pliki projektu
COPY . /var/www/html

# Skonfiguruj Nginx
COPY nginx.conf /etc/nginx/sites-available/default

# Ustaw katalog roboczy
WORKDIR /var/www/html

# Uruchom Nginx i PHP-FPM
CMD service nginx start && php-fpm