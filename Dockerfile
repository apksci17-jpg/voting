# Use official lightweight PHP 8.2 with Apache
FROM php:8.2-apache

# Install PDO MySQL driver required for database operations
RUN docker-php-ext-install pdo pdo_mysql

# Configure ServerName to prevent Apache FQDN warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Disable event and worker MPMs during build and ensure prefork is active
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true \
    && rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* 2>/dev/null || true \
    && a2enmod mpm_prefork rewrite headers 2>/dev/null || true

# Set working directory
WORKDIR /var/www/html

# Copy project files into web root
COPY . /var/www/html/

# Copy and setup startup entrypoint script
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

# Ensure upload and backup directories exist with appropriate permissions
RUN mkdir -p /var/www/html/frontend/assets/uploads /var/www/html/backend/backups \
    && chown -R www-data:www-data /var/www/html/frontend/assets/uploads /var/www/html/backend/backups \
    && chmod -R 775 /var/www/html/frontend/assets/uploads /var/www/html/backend/backups

# Expose default container port
EXPOSE 8080

# Run entrypoint script on startup
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
