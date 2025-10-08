#!/bin/bash
set -e

echo "🚀 Starting Laravel Application..."

# 安裝 Composer 依賴（如果 vendor 不存在）
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo "📦 Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# 複製 .env 檔案（如果不存在）
if [ ! -f .env ]; then
    echo "📝 Creating .env file..."
    cp .env.example .env
fi

# 更新 .env 中的資料庫和 Redis 主機設定（使用環境變數）
if [ -n "$DB_HOST" ]; then
    sed -i "s/^DB_HOST=.*/DB_HOST=${DB_HOST}/" .env
fi
if [ -n "$REDIS_HOST" ]; then
    sed -i "s/^REDIS_HOST=.*/REDIS_HOST=${REDIS_HOST}/" .env
fi

# 生成 APP_KEY（如果未設定）
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Generating application key..."
    php artisan key:generate --ansi
fi

# 等待資料庫啟動
echo "⏳ Waiting for database..."
until php artisan db:show 2>/dev/null; do
    echo "Database is unavailable - sleeping"
    sleep 2
done
echo "✅ Database is up!"

# 清除快取
echo "🧹 Clearing cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 執行資料庫遷移
echo "🗄️  Running database migrations..."
php artisan migrate --force

# 優化應用程式
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✨ Laravel Application is ready!"

# 執行傳入的指令
exec "$@"
