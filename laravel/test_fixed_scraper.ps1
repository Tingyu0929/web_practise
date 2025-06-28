# Test Fixed Anime Scraper - Duplicate Prevention
Write-Host "🔧 Testing Fixed Anime Scraper" -ForegroundColor Cyan
Write-Host "==============================" -ForegroundColor Cyan

Write-Host ""
Write-Host "Current database state:" -ForegroundColor Yellow
$currentAnimes = php artisan tinker --execute="echo \App\Models\Anime::count();"
$currentPlatforms = php artisan tinker --execute="echo \App\Models\AnimePlatform::count();"
Write-Host "Animes: $currentAnimes, Platform records: $currentPlatforms" -ForegroundColor Gray

Write-Host ""
$cleanChoice = Read-Host "Do you want to start fresh? (y/n)"

if ($cleanChoice -eq 'y' -or $cleanChoice -eq 'Y') {
    Write-Host ""
    Write-Host "Starting fresh with clean data..." -ForegroundColor Yellow
    
    # Test the fixed clean-old functionality
    php artisan anime:scrape --clean-old
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Clean operation successful!" -ForegroundColor Green
    } else {
        Write-Host "❌ Clean operation failed" -ForegroundColor Red
        Write-Host "Let's try manual cleanup..." -ForegroundColor Yellow
        
        # Manual cleanup
        php artisan tinker --execute="\App\Models\AnimePlatform::query()->delete(); \App\Models\Anime::query()->delete(); echo 'Manual cleanup completed';"
        
        Write-Host ""
        Write-Host "Now testing scraper..." -ForegroundColor Yellow
        php artisan anime:scrape --detail
    }
} else {
    Write-Host ""
    Write-Host "Testing scraper with existing data..." -ForegroundColor Yellow
    php artisan anime:scrape --detail
}

Write-Host ""
Write-Host "Final Results:" -ForegroundColor Cyan

# Show final statistics
$finalAnimes = php artisan tinker --execute="echo \App\Models\Anime::count();"
$finalPlatforms = php artisan tinker --execute="echo \App\Models\AnimePlatform::count();"

Write-Host "Final: $finalAnimes animes, $finalPlatforms platform records" -ForegroundColor White

if ($finalAnimes -gt 0) {
    $avgPlatforms = [math]::Round($finalPlatforms / $finalAnimes, 2)
    Write-Host "Average platforms per anime: $avgPlatforms" -ForegroundColor White
    
    if ($avgPlatforms -le 2) {
        Write-Host "✅ Great! Low platform duplication" -ForegroundColor Green
    } elseif ($avgPlatforms -le 4) {
        Write-Host "⚠️  Moderate platform records (acceptable)" -ForegroundColor Yellow
    } else {
        Write-Host "❌ Still too many platform duplicates" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "Platform distribution:" -ForegroundColor Cyan
php artisan anime:stats

Write-Host ""
Write-Host "✅ Test completed!" -ForegroundColor Green
Write-Host ""
Write-Host "Key improvements:" -ForegroundColor Cyan
Write-Host "- Fixed clean-old foreign key issue" -ForegroundColor White
Write-Host "- Strict platform extraction (1 per platform max)" -ForegroundColor White
Write-Host "- Better duplicate prevention in database" -ForegroundColor White
Write-Host "- Enhanced logging for debugging" -ForegroundColor White

Write-Host ""
Write-Host "Press any key to continue..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
