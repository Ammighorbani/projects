FROM php:8.3-fpm

RUN printf "Types: deb\nURIs: http://mirror.arvancloud.ir/debian\nSuites: trixie trixie-updates\nComponents: main\nSigned-By: /usr/share/keyrings/debian-archive-keyring.pgp\n" > /etc/apt/sources.list.d/debian.sources

RUN apt-get update && apt-get install -y \
    pkg-config \
    git \
    unzip \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
