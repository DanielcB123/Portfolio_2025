FROM richarvey/nginx-php-fpm:3.1.6

# Work inside the Laravel app directory
WORKDIR /var/www/html

# ===== Image / Laravel config =====
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr

# Render is already running composer for you (we saw "Running composer" in logs),
# so we keep SKIP_COMPOSER=1 so the base image does not try again on container start.
ENV SKIP_COMPOSER=1
ENV COMPOSER_ALLOW_SUPERUSER=1

# ===== Install Node + build frontend assets =====

# Install Node (you can bump version if you like, 22.x is fine with Vite)
RUN apt-get update \
    && apt-get install -y curl gnupg \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Copy only the JS build metadata first (better Docker cache)
COPY package.json package-lock.json* vite.config.js ./

# Install JS dependencies
RUN npm install

# Now copy the rest of your app code
COPY . .

# Build Vite assets into public/build
RUN npm run build

# Optional: shrink image a bit by removing node_modules after build
RUN rm -rf node_modules

# Start nginx + php-fpm
CMD ["/start.sh"]
