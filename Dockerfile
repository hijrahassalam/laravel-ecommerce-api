FROM php:8.4-cli

WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libmariadb-dev \
    libpq-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application
COPY . .

# Install PHP dependencies (generate vendor/)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Expose port
EXPOSE 8000

# Start server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
