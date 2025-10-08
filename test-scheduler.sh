#!/bin/bash

echo "🧪 Testing Scheduler Setup"
echo "=========================="

echo ""
echo "📋 Checking scheduled tasks..."
docker-compose exec -T scheduler php artisan schedule:list

echo ""
echo "🔍 Current time in container:"
docker-compose exec -T scheduler date

echo ""
echo "⏰ Testing schedule:run command..."
docker-compose exec -T scheduler php artisan schedule:run --verbose

echo ""
echo "📊 Checking anime stats..."
docker-compose exec -T laravel php artisan anime:stats
