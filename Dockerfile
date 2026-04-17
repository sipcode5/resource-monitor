# ── Stage 1: Node – compile frontend assets ──────────────────────────────
FROM node:22-alpine AS frontend
WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY resources/     resources/
COPY vite.config.js .
COPY .env           .env

RUN npm run build

# ── Stage 2: PHP – application image ─────────────────────────────────────
FROM php:8.4-fpm-alpine AS app

# System deps
RUN apk add --no-cache \
        bash \
        git \
        curl \
        linux-headers \
        autoconf \
        g++ \
        make \
        mysql-client \
        netcat-openbsd \
        libpng-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        oniguruma-dev \
        libxml2-dev \
        zip \
        unzip \
        shadow \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
    && pecl install redis \
    && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files and install PHP deps (no dev, optimised autoload)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy full application
COPY . .

# Copy compiled frontend from Stage 1
COPY --from=frontend /app/public/build public/build

# Finish composer setup
RUN composer dump-autoload --optimize

# Permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh
CMD ["/usr/local/bin/start.sh"]

# ── Stage 3: Nginx – serves static files, proxies to php-fpm ────────────────
FROM nginx:1.25-alpine AS nginx-stage

# Copy compiled public assets from the app stage so nginx can serve them directly
COPY --from=app /var/www/html/public /var/www/html/public
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
