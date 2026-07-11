FROM php:8.4-fpm

ARG UID=1000
ARG GID=1000

# ── Instalador rápido de extensiones PHP (usa binarios precompilados cuando existen) ──
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/

# ── Dependencias del sistema ──────────────────────────────────────────
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && install-php-extensions \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        xml \
        redis \
        # xdebug \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ── Composer ──────────────────────────────────────────────────────────
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ── Usuario no-root ───────────────────────────────────────────────────
RUN groupadd --gid $GID appuser \
    && useradd --uid $UID --gid $GID -m appuser

WORKDIR /var/www

# ── Configuración PHP ─────────────────────────────────────────────────
COPY docker/php/php.ini $PHP_INI_DIR/conf.d/99-dev.ini

# ── Dependencias de Composer (cacheado por capas: solo se reinstala si  ──
# ── composer.json o composer.lock cambian)                             ──
COPY api/composer.json api/composer.lock ./
RUN --mount=type=cache,target=/root/.composer/cache \
    XDEBUG_MODE=off composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# ── Entrypoint ────────────────────────────────────────────────────────
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]