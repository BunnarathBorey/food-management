
FROM php:8.2-fpm-alpine

# Install required packages
RUN apk update && apk add \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    shadow \
    && docker-php-ext-install pdo pdo_mysql \
    && apk --no-cache add nodejs npm

# Set the working directory
WORKDIR /var/www

# Create the public directory if it doesn't exist
RUN mkdir -p public

# Set the correct permissions for the /var/www/public directory
RUN chown -R www-data:www-data /var/www/public

# Optionally, you can add the following if you're running as a different user:
# USER www-data
