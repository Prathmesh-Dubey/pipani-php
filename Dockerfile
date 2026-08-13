FROM php:8.2-apache

# Enable Apache modules required for .htaccess
RUN a2enmod rewrite headers

# Install PHP extensions required
RUN docker-php-ext-install pdo pdo_mysql

# Set appropriate permissions
RUN chown -R www-data:www-data /var/www/html
