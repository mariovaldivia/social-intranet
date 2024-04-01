FROM php:8.2.17-fpm

ARG UID
ARG HOME_DIR=/appdata/www
# Create user and some useful stuff
RUN adduser --disabled-password --gecos "" appuser
# RUN adduser -u ${UID} --disabled-password --gecos "" appuser
RUN usermod -aG sudo appuser
RUN mkdir /home/appuser/.ssh
RUN chown -R appuser:appuser /home/appuser/
RUN echo "StrictHostKeyChecking no" >> /home/appuser/.ssh/config
RUN echo "export COLUMNS=300" >> /home/appuser/.bashrc
RUN echo "alias sf=/appdata/www/bin/console" >> /home/appuser/.bashrc

# Install packages and PHP extensions
RUN apt update \
    && apt install -y git acl openssl openssh-client wget zip vim libssh-dev coreutils

RUN set -eux; \
    apt install -y --no-install-recommends \
    zlib1g-dev libjpeg62-turbo-dev\
    libzip-dev libxml2-dev libicu-dev\
    libfreetype6 libonig-dev libxslt1-dev; 
    # rm -rf /var/lib/apt/lists/*

RUN set -eux; \
    apt-get update; \
    apt-get upgrade -y; \
    apt-get install -y --no-install-recommends \
            libmemcached-dev \
            libz-dev \
            libpq-dev \
            libjpeg-dev \
            libpng-dev \
            libfreetype6-dev \
            libssl-dev \
            libwebp-dev \
            libxpm-dev \
            libmcrypt-dev \
            libonig-dev; \
    rm -rf /var/lib/apt/lists/*
RUN pecl install xdebug apcu

# RUN docker-php-ext-configure gd --enable-gd --with-jpeg --with-freetype
RUN set -eux; \
    docker-php-ext-configure gd \
            --prefix=/usr \
            --with-jpeg \
            --with-webp \
            --with-xpm \
            --with-freetype; \
    docker-php-ext-install gd; \
    php -r 'var_dump(gd_info());'
# RUN docker-php-ext-install -j$(nproc) gd
# RUN pecl install gmagick
RUN docker-php-ext-install intl pdo pdo_mysql zip xsl exif mbstring 
RUN docker-php-ext-enable --ini-name 05-opcache.ini opcache xdebug
# RUN apt-get install -y libmagickwand-dev imagemagick \
#     && pecl install imagick \
#     && docker-php-ext-enable imagick

# Install and update composer
RUN curl https://getcomposer.org/composer.phar -o /usr/bin/composer && chmod +x /usr/bin/composer
RUN composer self-update

# Install PHP-CS-FIXER
RUN wget https://cs.symfony.com/download/php-cs-fixer-v3.phar -O php-cs-fixer
RUN chmod a+x php-cs-fixer
RUN mv php-cs-fixer /usr/local/bin/php-cs-fixer

## Install Symfony binary
# USER appuser
# RUN wget https://get.symfony.com/cli/installer -O - | bash
# USER root
# RUN mv /home/appuser/.symfony/bin/symfony /usr/local/bin/symfony
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash
RUN apt install symfony-cli

RUN mkdir -p /appdata/www

# Config XDEBUG
COPY ./xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

WORKDIR /appdata/www

RUN chown -R appuser:appuser ${HOME_DIR}
    # && usermod --uid appuser --home ${HOME_DIR} --shell /bin/bash www-data \
    # && groupmod --gid appuser www-data