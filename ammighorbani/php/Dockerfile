FROM php:8.3-fpm

# نصب ابزارهای لازم
RUN apt-get update && apt-get install -y \
    libssl-dev \
    pkg-config \
    git \
    unzip \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
