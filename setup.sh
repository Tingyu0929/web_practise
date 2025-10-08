#!/bin/bash

echo "🚀 Animation Web Setup Script"
echo "=============================="

# 啟動 Docker 服務
echo ""
echo "📦 Starting Docker services..."
docker-compose up -d

# 等待服務就緒
echo ""
echo "⏳ Waiting for services to be ready..."
sleep 15

# 設定 Laravel 環境
echo ""
echo "🔧 Setting up Laravel..."
docker-compose exec -T laravel sed -i "s/^DB_HOST=.*/DB_HOST=mysql/" .env
docker-compose exec -T laravel sed -i "s/^REDIS_HOST=.*/REDIS_HOST=redis/" .env
docker-compose exec -T laravel php artisan key:generate
docker-compose exec -T laravel php artisan config:clear
docker-compose exec -T laravel php artisan migrate:fresh --force

# 執行爬蟲
echo ""
echo "🕷️  Running web scraper..."
docker-compose exec -T laravel php artisan anime:scrape

echo ""
echo "✨ Setup complete! Services are running:"
echo "   - Frontend: http://localhost:5173"
echo "   - Laravel API: http://localhost:8000"
echo "   - MySQL: localhost:3306"
echo "   - Redis: localhost:6379"
echo ""
echo "📅 Scheduled tasks:"
echo "   - Daily scraper: Every day at 12:00 PM (Asia/Taipei)"
