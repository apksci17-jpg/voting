# Use official lightweight PHP 8.2 with Apache
FROM php:8.2-apache

# Install PDO MySQL driver required for database operations
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite and headers modules for CORS and .htaccess
RUN a2enmod rewrite headers

# Set working directory
WORKDIR /var/www/html

# Copy project files into web root
COPY . /var/www/html/

# Ensure upload and backup directories exist with appropriate permissions
RUN mkdir -p /var/www/html/frontend/assets/uploads /var/www/html/backend/backups \
    && chown -R www-data:www-data /var/www/html/frontend/assets/uploads /var/www/html/backend/backups \
    && chmod -R 775 /var/www/html/frontend/assets/uploads /var/www/html/backend/backups

# Expose port (Railway injects $PORT at runtime)
EXPOSE 80

# Dynamically bind Apache to Railway's $PORT environment variable and start server
CMD sed -i "s/80/${PORT:-80}/g" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf && apache2-foreground
