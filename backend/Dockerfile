FROM php:8.4-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    netcat-openbsd \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl mbstring pcntl bcmath zip \
    && curl -Lf "https://github.com/DataDog/dd-trace-php/releases/latest/download/datadog-setup.php" -o /tmp/datadog-setup.php \
    && php /tmp/datadog-setup.php --php-bin="$(command -v php)" \
    && rm -f /tmp/datadog-setup.php \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

# Liveness para orquestadores / `docker ps` (misma ruta que en docker-compose).
HEALTHCHECK --interval=30s --timeout=5s --start-period=45s --retries=5 \
  CMD curl -fsS http://127.0.0.1:8000/up >/dev/null || exit 1
