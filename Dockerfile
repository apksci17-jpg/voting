# Use official lightweight PHP 8.2 with Apache
FROM php:8.2-apache

# Install PDO MySQL driver required for database operations
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite and headers modules for CORS and .htaccess
RUN a2enmod rewrite headers

# Configure Apache to listen on port 8080 (Railway's default container port)
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Set working directory
WORKDIR /var/www/html

# Copy project files into web root
COPY . /var/www/html/

# Ensure upload and backup directories exist with appropriate permissions
RUN mkdir -p /var/www/html/frontend/assets/uploads /var/www/html/backend/backups \
    && chown -R www-data:www-data /var/www/html/frontend/assets/uploads /var/www/html/backend/backups \
    && chmod -R 775 /var/www/html/frontend/assets/uploads /var/www/html/backend/backups

# Expose port 8080
EXPOSE 8080

# Start Apache directly using official foreground runner
CMD ["apache2-foreground"]
