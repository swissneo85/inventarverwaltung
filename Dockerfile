# ============================================
# Inventarverwaltung - Production Dockerfile
# ============================================
# Baut alles in einem Stage (funktioniert zuverlässig)
# Kein Multi-Stage — vermeidet Build-Probleme

FROM php:8.4-fpm-alpine

ARG APP_VERSION=dev

# Install dependencies
RUN apk add --no-cache nginx sqlite sqlite-dev curl supervisor nodejs npm git unzip \
    && docker-php-ext-install pdo_sqlite pdo_mysql

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

# Copy backend
COPY backend/ .

# Create .env file directly (not from gitignored file)
RUN cat > /var/www/html/.env <<'EOF'
APP_NAME=Inventarverwaltung
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost:3004
FRONTEND_URL=http://localhost:3004
DB_CONNECTION=sqlite
DB_DATABASE=/app/data/database.sqlite
SESSION_DRIVER=file
SESSION_LIFETIME=120
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
LOG_CHANNEL=errorlog
LOG_LEVEL=warning
EOF
RUN echo "APP_VERSION=${APP_VERSION}" >> /var/www/html/.env

# Build frontend
COPY frontend/package.json /tmp/frontend/package.json
RUN cd /tmp/frontend && npm install && mkdir -p /var/www/html/public
COPY frontend/ /tmp/frontend/
RUN cd /tmp/frontend && echo "VITE_APP_VERSION=${APP_VERSION}" > .env && npm run build && cp -r dist/* /var/www/html/public/

# Permissions VOR composer install (bootstrap/cache muss existieren)
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache /app/data /run/php \
    && chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache /app/data

# Install PHP dependencies (Laravel braucht post-autoload-dump!)
RUN composer install --no-dev --ignore-platform-reqs --no-interaction \
    && composer dump-autoload --optimize

# Create DB
RUN touch /app/data/database.sqlite && chmod 666 /app/data/database.sqlite

# Symlinks für PHP-FPM (arbeitet aus /var/www/html/public)
RUN ln -s /var/www/html/routes /var/www/html/public/routes \
    && ln -s /var/www/html/storage/app/public /var/www/html/public/storage

# Copy configs
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor.conf /etc/supervisord.conf
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80
CMD ["/start.sh"]
