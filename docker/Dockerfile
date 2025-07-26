FROM php:8.2-apache

# Install required PHP extensions
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev zip \
    && docker-php-ext-install zip

# Enable Apache mod_rewrite
RUN a2enmod rewrite

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