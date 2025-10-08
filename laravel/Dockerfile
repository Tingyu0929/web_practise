FROM php:8.2-cli

# 安裝系統依賴
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# 安裝 Redis 擴展
RUN pecl install redis && docker-php-ext-enable redis

# 安裝 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 設定工作目錄
WORKDIR /var/www/html

# 複製 composer 檔案並安裝依賴（利用 Docker 快取）
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --no-autoloader --prefer-dist

# 複製應用程式檔案
COPY . /var/www/html

# 完成 composer 安裝
RUN composer dump-autoload --optimize

# 建立必要的目錄並設定權限
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# 暴露端口
EXPOSE 8000

# 啟動服務
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
