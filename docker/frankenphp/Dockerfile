FROM dunglas/frankenphp:1-php8.4

ARG USER=www-data
ARG UID=1000
ARG GID=1000

WORKDIR /var/www/html

# Install required system dependencies
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
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install required PHP extensions
RUN install-php-extensions \
    pdo_sqlite \
    sqlite3 \
    pdo_mysql \
    bcmath \
    gd \
    exif \
    intl \
    zip \
    redis \
    pcntl

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Adjust www-data UID and GID for host volume compatibility
RUN if [ "$UID" != "33" ]; then \
        usermod -u $UID www-data 2>/dev/null || true; \
    fi \
    && if [ "$GID" != "33" ]; then \
        groupmod -g $GID www-data 2>/dev/null || true; \
    fi

# Copy configurations
COPY docker/frankenphp/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/frankenphp/Caddyfile /etc/caddy/Caddyfile

# Copy entrypoint script
COPY docker/frankenphp/entrypoint.sh /usr/local/bin/docker-entrypoint-frankenphp
RUN chmod +x /usr/local/bin/docker-entrypoint-frankenphp

ENTRYPOINT ["/usr/local/bin/docker-entrypoint-frankenphp"]

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
