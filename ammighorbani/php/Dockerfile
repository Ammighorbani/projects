#FROM php:8.3-fpm
#
#RUN printf "Types: deb\nURIs: http://mirror.arvancloud.ir/debian\nSuites: trixie trixie-updates\nComponents: main\nSigned-By: /usr/share/keyrings/debian-archive-keyring.pgp\n" > /etc/apt/sources.list.d/debian.sources
#
#RUN apt-get update && apt-get install -y \
#    pkg-config \
#    git \
#    unzip \
#    php-mongodb \
#    && docker-php-ext-install mysqli pdo pdo_mysql \
#    && apt-get clean && rm -rf /var/lib/apt/lists/*
#
#WORKDIR /var/www/html

FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

# تغییر mirror به آروان و حذف security repo
RUN sed -i 's|http://archive.ubuntu.com/ubuntu|http://mirror.arvancloud.ir/ubuntu|g' /etc/apt/sources.list \
 && sed -i '/security.ubuntu.com/d' /etc/apt/sources.list

# نب ابزارهای پایه
RUN apt update && apt install -y \
    software-properties-common \
    curl \
    git \
    unzip \
    ca-certificates \
    lsb-release

# اضافه کردن repo PHP
RUN add-apt-repository ppa:ondrej/php -y

# نصب PHP
RUN apt update && apt install -y \
    php8.3 \
    php8.3-fpm \
    php8.3-cli \
    php8.3-mysql \
    php8.3-mongodb \
    php8.3-xml \
    php8.3-mbstring \
    php8.3-curl \
    php8.3-zip \
    php8.3-bcmath

WORKDIR /var/www/html

# اجرای php-fpm
CMD ["php-fpm8.3", "-F"]

