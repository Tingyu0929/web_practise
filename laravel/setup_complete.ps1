# PowerShell Script for Anime Scraper Setup
# UTF-8 Encoding

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "       Anime Scraper Project Setup         " -ForegroundColor Cyan  
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "Step 1: Checking migration files..." -ForegroundColor Yellow
Get-ChildItem "database\migrations\*anime*.php" | ForEach-Object { Write-Host "  $_" }
Write-Host ""

Write-Host "Step 2: Resetting database..." -ForegroundColor Yellow
$migrationResult = php artisan migrate:fresh
Write-Host ""

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Database setup successful!" -ForegroundColor Green
    Write-Host ""
    
    Write-Host "Step 3: Checking migration status..." -ForegroundColor Yellow
    php artisan migrate:status
    Write-Host ""
    
    Write-Host "Step 4: Testing database tables..." -ForegroundColor Yellow
    Write-Host "Checking if tables exist..." -ForegroundColor Gray
    php artisan tinker --execute="echo 'Animes table: ' . \App\Models\Anime::count() . ' records'; echo 'Platforms table: ' . \App\Models\AnimePlatform::count() . ' records';"
    Write-Host ""
    
    Write-Host "Step 5: Testing scraper..." -ForegroundColor Yellow
    php artisan anime:scrape --detail
    Write-Host ""
    
    Write-Host "✅ Setup completed successfully!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Available commands:" -ForegroundColor Cyan
    Write-Host "- php artisan anime:scrape                     (Scrape anime data)" -ForegroundColor White
    Write-Host "- php artisan anime:scrape --clean-old         (Clean old data first)" -ForegroundColor White
    Write-Host "- php artisan anime:scrape --detail            (Show detailed stats)" -ForegroundColor White
    Write-Host "- php artisan anime:scrape --verbose           (Show verbose output)" -ForegroundColor White
    Write-Host "- php artisan anime:analyze                    (Analyze webpage structure)" -ForegroundColor White
    
} else {
    Write-Host "❌ Migration failed!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please check:" -ForegroundColor Yellow
    Write-Host "1. Database connection in .env file" -ForegroundColor Gray
    Write-Host "2. Database exists and accessible" -ForegroundColor Gray  
    Write-Host "3. User has permission to create tables" -ForegroundColor Gray
    Write-Host ""
    Write-Host "Manual solution:" -ForegroundColor Yellow
    Write-Host "1. Check database settings in .env" -ForegroundColor Gray
    Write-Host "2. php artisan migrate:fresh --force" -ForegroundColor Gray
    Write-Host "3. Check error messages for details" -ForegroundColor Gray
}

Write-Host ""
Write-Host "Press any key to continue..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
