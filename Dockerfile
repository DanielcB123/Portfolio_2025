# Stage 1: build frontend assets with Node + Vite
FROM node:22-alpine AS assets

WORKDIR /app

# Copy only what Vite needs first (faster rebuilds)
COPY package.json package-lock.json* ./
COPY vite.config.* postcss.config.* tailwind.config.* ./

# Install deps
RUN npm ci

# Now copy the rest of the app so Vite can see all Vue components etc
COPY resources ./resources

# Build assets to /app/public/build
RUN npm run build


# Stage 2: PHP + Nginx container
FROM richarvey/nginx-php-fpm:3.1.6

# Laravel app lives here in this base image
WORKDIR /var/www/html

# Copy full app code into container
COPY . .

# Overwrite the build folder with the freshly compiled assets
COPY --from=assets /app/public/build ./public/build

# Ensure Laravel writeable directories are owned by php-fpm user
# and that SQLite can be written
RUN mkdir -p storage bootstrap/cache database \
    && touch database/database.sqlite \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R u+rwX,g+rwX storage bootstrap/cache database

# Image config
ENV SKIP_COMPOSER=0
ENV WEBROOT=/var/www/html/public
ENV PHP_ERRORS_STDERR=1
ENV RUN_SCRIPTS=1
ENV REAL_IP_HEADER=1

# Laravel config
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr

# Allow composer to run as root
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Start PHP-FPM + Nginx (base image entrypoint)
ENTRYPOINT ["/entrypoint.sh"]