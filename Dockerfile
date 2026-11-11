# ============================================================
# REMTYS APP — Dockerfile (multi-stage, hardened)
# PHP 8.2 + Apache + PostgreSQL driver
# ============================================================

# --- Stage 1: vendor build --------------------------------------------------
FROM composer:2.7 AS vendor

WORKDIR /app
# Solo los archivos necesarios para `composer install` → cache-friendly
COPY composer.json composer.lock* ./
# No-dev en producción; fallback a install si no hay lock aún
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader \
    || composer update --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader


# --- Stage 2: runtime -------------------------------------------------------
FROM php:8.2-apache-bookworm AS runtime

# Variables de build — no ponen secretos; son tags operativas
ARG APP_VERSION=1.0.0
ARG BUILD_DATE
LABEL org.opencontainers.image.title="REMTYS" \
      org.opencontainers.image.version="${APP_VERSION}" \
      org.opencontainers.image.created="${BUILD_DATE}" \
      org.opencontainers.image.source="internal" \
      org.opencontainers.image.licenses="Proprietary"

# System deps + PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
        libzip-dev \
        curl \
        ca-certificates \
        tini \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip \
    && pecl install opcache-stable \
    && docker-php-ext-enable opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Apache modules
RUN a2enmod rewrite headers expires

# Apache hardening
RUN set -eux; \
    { \
      echo "ServerTokens Prod"; \
      echo "ServerSignature Off"; \
      echo "TraceEnable Off"; \
      echo "FileETag None"; \
    } > /etc/apache2/conf-available/z-remtys-hardening.conf \
    && a2enconf z-remtys-hardening

# Apache vhost
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# PHP production config — strict, cached, limited
RUN set -eux; \
    { \
      echo "expose_php = Off"; \
      echo "display_errors = Off"; \
      echo "display_startup_errors = Off"; \
      echo "log_errors = On"; \
      echo "error_log = /var/log/apache2/php-error.log"; \
      echo "upload_max_filesize = 20M"; \
      echo "post_max_size = 22M"; \
      echo "memory_limit = 256M"; \
      echo "max_execution_time = 60"; \
      echo "max_input_vars = 2000"; \
      echo "session.cookie_httponly = 1"; \
      echo "session.cookie_samesite = Strict"; \
      echo "session.use_strict_mode = 1"; \
      echo "session.use_only_cookies = 1"; \
      echo "allow_url_fopen = Off"; \
      echo "allow_url_include = Off"; \
      echo "opcache.enable = 1"; \
      echo "opcache.enable_cli = 0"; \
      echo "opcache.memory_consumption = 128"; \
      echo "opcache.max_accelerated_files = 10000"; \
      echo "opcache.validate_timestamps = 0"; \
      echo "opcache.revalidate_freq = 0"; \
      echo "opcache.interned_strings_buffer = 16"; \
      echo "realpath_cache_size = 4096K"; \
      echo "realpath_cache_ttl = 600"; \
    } > /usr/local/etc/php/conf.d/zz-remtys-prod.ini

WORKDIR /var/www/html/remtysapp

# Copy source (respecting .dockerignore)
COPY --chown=www-data:www-data . .

# Bring in vendored deps
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor

# Storage permissions
RUN mkdir -p storage/uploads storage/logs \
    && chown -R www-data:www-data storage \
    && chmod -R 775 storage \
    # Defensive: uploads no ejecutables
    && find storage/uploads -type d -exec chmod 755 {} \; 2>/dev/null || true

# Logs de Apache a stdout/stderr (12-factor)
RUN ln -sf /dev/stdout /var/log/apache2/access.log \
    && ln -sf /dev/stderr /var/log/apache2/error.log \
    && ln -sf /dev/stderr /var/log/apache2/php-error.log

# Drop to non-root
USER www-data

EXPOSE 80

# Healthcheck: must respond 2xx/3xx on the landing path
HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD curl -fsS -o /dev/null -w "%{http_code}" http://localhost/remtysapp/public/ | grep -Eq "^(2|3)" || exit 1

# Tini = PID 1 correcto (manejo limpio de señales)
ENTRYPOINT ["/usr/bin/tini", "--"]
CMD ["apache2-foreground"]
