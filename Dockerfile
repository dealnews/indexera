# Indexera Docker Image
#
# Production-ready container running Nginx + PHP 8.4-FPM supervised by runit.
#
# ## Architecture
#
# - Base:     Ubuntu 24.04 LTS (Phusion Baseimage)
# - Services: Nginx + PHP 8.4-FPM (supervised by runit)
# - Doc root: /app/public
#
# ## Environment Variables
#
# Database (maps to db.indexera.* in config):
#   DB_INDEXERA_TYPE    Database type: mysql | pgsql | sqlite
#   DB_INDEXERA_SERVER  Database hostname
#   DB_INDEXERA_PORT    Database port
#   DB_INDEXERA_DB      Database name
#   DB_INDEXERA_USER    Database username
#   DB_INDEXERA_PASS    Database password
#
# Alternatively, mount a config.ini file at /app/etc/config.ini.
#
# ## Volumes
#
#   /app/etc        Configuration files (mount config.ini here)
#   /var/log/nginx  Nginx access and error logs
#   /var/log/php    PHP-FPM logs
#
# ## Ports
#
#   80  HTTP
#
# ## Usage
#
# Build:
#   docker build -t indexera:latest .
#
# Run with environment variables:
#   docker run -d \
#     -p 8000:80 \
#     -e DB_INDEXERA_TYPE=mysql \
#     -e DB_INDEXERA_SERVER=db.example.com \
#     -e DB_INDEXERA_PORT=3306 \
#     -e DB_INDEXERA_DB=indexera \
#     -e DB_INDEXERA_USER=app \
#     -e DB_INDEXERA_PASS=secret \
#     indexera:latest
#
# Run with volume-mounted config:
#   docker run -d \
#     -p 8000:80 \
#     -v /path/to/config.ini:/app/etc/config.ini:ro \
#     indexera:latest

FROM phusion/baseimage:noble-1.0.3
ARG TARGETARCH

ENV DEBIAN_FRONTEND=noninteractive \
    LANG=C.UTF-8 \
    LC_ALL=C.UTF-8

# Add Ondřej Surý PHP PPA for PHP 8.4
RUN apt-get update && \
    apt-get install -y software-properties-common && \
    add-apt-repository ppa:ondrej/php && \
    apt-get update

# Install Nginx, PHP 8.4, and required extensions
RUN apt-get install -y \
    nginx \
    php8.4-fpm \
    php8.4-cli \
    php8.4-mbstring \
    php8.4-xml \
    php8.4-curl \
    php8.4-pgsql \
    php8.4-mysql \
    php8.4-sqlite3 \
    php8.4-bcmath \
    php8.4-intl \
    php8.4-yaml \
    curl \
    unzip \
    git && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install Dockerize used in the entrypoint
ENV DOCKERIZE_VERSION=v0.11.0
RUN curl -L -O https://github.com/jwilder/dockerize/releases/download/$DOCKERIZE_VERSION/dockerize-linux-${TARGETARCH}-$DOCKERIZE_VERSION.tar.gz \
    && tar -C /usr/local/bin -xzvf dockerize-linux-${TARGETARCH}-$DOCKERIZE_VERSION.tar.gz \
    && rm dockerize-linux-${TARGETARCH}-$DOCKERIZE_VERSION.tar.gz


# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin \
    --filename=composer

# Create application directory
RUN mkdir -p /app && \
    chown -R www-data:www-data /app

WORKDIR /app

# Copy application files (.dockerignore excludes vendor, etc/config.ini, .dev, etc.)
COPY --chown=www-data:www-data . /app/

# Install Composer dependencies (production, optimized autoloader)
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    --prefer-dist && \
    chown -R www-data:www-data /app/vendor

# Configure PHP-FPM
COPY docker/php-fpm/pool.conf.tmpl /etc/php/8.4/fpm/pool.d/www.conf.tmpl

# Configure Nginx
COPY docker/nginx/indexera.conf.tmpl /etc/nginx/sites-available/indexera.conf.tmpl
RUN rm -f /etc/nginx/sites-enabled/default && \
    ln -s /etc/nginx/sites-available/indexera.conf /etc/nginx/sites-enabled/indexera.conf

# Install runit service scripts
RUN mkdir -p /etc/service/nginx /etc/service/php-fpm
COPY docker/runit/nginx/run /etc/service/nginx/run
COPY docker/runit/php-fpm/run /etc/service/php-fpm/run
RUN chmod +x /etc/service/nginx/run /etc/service/php-fpm/run

# Create required runtime directories
RUN mkdir -p \
    /app/etc \
    /var/log/php \
    /var/run/php && \
    chown -R www-data:www-data \
    /var/log/php \
    /var/run/php \
    /app/etc

# Set file permissions
RUN find /app -type f -exec chmod 644 {} \; && \
    find /app -type d -exec chmod 755 {} \; && \
    chown -R www-data:www-data /app

EXPOSE 80

CMD ["/sbin/my_init"]
