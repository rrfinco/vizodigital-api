FROM php:8.3-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        zip \
        bcmath \
        intl \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-portal.ini
COPY docker/php/entrypoint.sh /usr/local/bin/portal-entrypoint

RUN chmod +x /usr/local/bin/portal-entrypoint \
    && usermod -u 1000 www-data \
    && groupmod -g 1000 www-data

WORKDIR /var/www/html

ENTRYPOINT ["portal-entrypoint"]
CMD ["php-fpm"]
