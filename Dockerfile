FROM php:8.4-fpm

ARG USER=www-data
ARG UID=1000
ARG GID=1000

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    sqlite3 \
    libsqlite3-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    gosu \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions using the official extension installer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
    pdo_sqlite \
    sqlite3 \
    pdo_mysql \
    bcmath \
    ctype \
    curl \
    dom \
    fileinfo \
    filter \
    gd \
    exif \
    intl \
    mbstring \
    openssl \
    opcache \
    pcntl \
    session \
    tokenizer \
    xml \
    zip \
    redis

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Adjust www-data UID and GID for host volume compatibility
RUN if [ "$UID" != "33" ]; then \
        usermod -u $UID www-data 2>/dev/null || true; \
    fi \
    && if [ "$GID" != "33" ]; then \
        groupmod -g $GID www-data 2>/dev/null || true; \
    fi

# Copy PHP custom configurations
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

# Copy entrypoint script
COPY docker/php/entrypoint.sh /usr/local/bin/docker-php-entrypoint-app
RUN chmod +x /usr/local/bin/docker-php-entrypoint-app

ENTRYPOINT ["/usr/local/bin/docker-php-entrypoint-app"]

CMD ["php-fpm"]
