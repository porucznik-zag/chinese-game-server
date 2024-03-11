FROM php:8.1-fpm-buster


# RUN apt-get update && apt-get install -y libmcrypt-dev \
#     mysql-client libmagickwand-dev --no-install-recommends \
#     && pecl install imagick \
#     && docker-php-ext-enable imagick \
# && docker-php-ext-install mcrypt pdo_mysql

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


# Zainstaluj narzędzie do instalacji rozszerzeń PHP
# COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/

# Zainstaluj Composer
# COPY --from=composer /usr/bin/composer /usr/bin/composer

# Install Composer
# RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Zainstaluj i aktywuj rozszerzenia PHP
# RUN install-php-extensions grpc

# Skopiuj pliki projektu
COPY . /var/www/html


# Zainstaluj zależności za pomocą Composer
RUN composer install

# Ustaw katalog roboczy
WORKDIR /var/www/html

# Uruchom serwer PHP-FPM
CMD ["php-fpm"]