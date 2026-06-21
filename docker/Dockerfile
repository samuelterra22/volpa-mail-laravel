FROM php:8.4-cli-alpine

RUN apk add --no-cache \
    git \
    zip \
    unzip \
    curl-dev \
    libzip-dev \
    linux-headers \
    $PHPIZE_DEPS \
    && docker-php-ext-install zip pcntl \
    && pecl install pcov \
    && docker-php-ext-enable pcov

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN echo "pcov.enabled=1" >> /usr/local/etc/php/conf.d/pcov.ini
RUN git config --global --add safe.directory /app

WORKDIR /app
