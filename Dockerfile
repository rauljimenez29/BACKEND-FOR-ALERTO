# Use the official PHP image with Apache
FROM php:8.2-apache

# Enable Apache mod_rewrite (optional, but common for PHP apps)
RUN a2enmod rewrite

# Install system dependencies for PostgreSQL extensions
RUN apt-get update && apt-get install -y libpq-dev

# Install PostgreSQL extensions for PHP
RUN docker-php-ext-install pgsql pdo_pgsql

# Set the working directory to Apache's web root
WORKDIR /var/www/html

# Copy the PHP app files into the container
COPY mnl911.atwebpages.com/ /var/www/html/

# Set permissions (optional, adjust as needed)
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 for web traffic
EXPOSE 80

# Start Apache in the foreground
CMD ["apache2-foreground"] 