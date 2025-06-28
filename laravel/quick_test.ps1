# Simple setup and test script
Write-Host "Anime Scraper - Quick Setup" -ForegroundColor Cyan
Write-Host "===========================" -ForegroundColor Cyan

# Test database connection
Write-Host "Testing database connection..." -ForegroundColor Yellow
php artisan migrate:status

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Database connection OK" -ForegroundColor Green
    
    # Run migrations
    Write-Host "Running migrations..." -ForegroundColor Yellow  
    php artisan migrate:fresh
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Migrations successful" -ForegroundColor Green
        
        # Test scraper
        Write-Host "Testing scraper..." -ForegroundColor Yellow
        php artisan anime:scrape --detail
    } else {
        Write-Host "❌ Migration failed" -ForegroundColor Red
    }
} else {
    Write-Host "❌ Database connection failed" -ForegroundColor Red
    Write-Host "Please check your .env database settings" -ForegroundColor Yellow
}
