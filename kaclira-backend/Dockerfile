# Builder aşaması - PHP ve gerekli extension'larla Composer çalıştırma
FROM php:8.3-cli-alpine AS builder

WORKDIR /app

# Sistem bağımlılıklarını ve PHP extension'larını kur
RUN apk update && \
    apk add --no-cache \
    libpng \
    libjpeg-turbo \
    freetype \
    libzip \
    # Build-time dependencies
    zlib-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev

# PHP extension'larını yükle
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) gd pdo pdo_mysql zip

# Composer'ı kur
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Sadece composer dosyalarını kopyala
COPY composer.json composer.lock ./

# Bağımlılıkları kur (development bağımlılıkları dahil)
RUN composer install --optimize-autoloader --no-scripts --ignore-platform-reqs

# Uygulama dosyalarını kopyala
COPY . .

# Development bağımlılıklarını kaldır
RUN composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-reqs

# Laravel Pail referansını kaldır
RUN if [ -f config/app.php ]; then \
    sed -i "/Laravel\\\\Pail\\\\PailServiceProvider/d" config/app.php; \
    sed -i "/Laravel\\Pail\\PailServiceProvider/d" config/app.php; \
    sed -i "/PailServiceProvider/d" config/app.php; \
    fi

# Production aşaması
FROM php:8.3-cli-alpine

WORKDIR /var/www/html

# Sistem bağımlılıklarını kur (sadece runtime)
RUN apk update && \
    apk add --no-cache \
    libpng \
    libjpeg-turbo \
    freetype \
    libzip

# PHP extension'larını production'da da yükle
RUN apk add --no-cache --virtual .build-deps \
    zlib-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) gd pdo pdo_mysql zip && \
    apk del .build-deps

# Uygulamayı builder aşamasından kopyala
COPY --from=builder /app /var/www/html

# Laravel Pail referansını tekrar kontrol et ve kaldır
RUN if [ -f config/app.php ]; then \
    sed -i "/Laravel\\\\Pail\\\\PailServiceProvider/d" config/app.php; \
    sed -i "/Laravel\\Pail\\PailServiceProvider/d" config/app.php; \
    sed -i "/PailServiceProvider/d" config/app.php; \
    fi

# Config cache'i temizle (artisan'ı doğrudan çalıştırmadan önce)
RUN php artisan config:clear 2>/dev/null || true

# .env dosyasını oluştur (eğer yoksa)
RUN if [ ! -f .env ]; then \
    cp .env.example .env 2>/dev/null || echo ".env.example not found, creating empty .env"; \
    php artisan key:generate 2>/dev/null || echo "Key generation skipped"; \
    fi

# Cache ve config dosyalarını temizle
RUN rm -f bootstrap/cache/*.php 2>/dev/null || true

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]