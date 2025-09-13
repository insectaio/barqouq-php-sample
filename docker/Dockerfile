FROM php:8.2-apache

# Install required system packages and PHP extensions (zip, grpc, protobuf)
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev zip autoconf pkg-config libssl-dev zlib1g-dev \
    && docker-php-ext-install zip \
    && pecl install grpc \
    && pecl install protobuf \
    && docker-php-ext-enable grpc protobuf

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure Apache vhost to point to /public and allow .htaccess overrides
COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf
RUN a2ensite 000-default

# Copy app
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install

# Use custom php.ini
COPY php.ini /usr/local/etc/php/

# Fix permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80