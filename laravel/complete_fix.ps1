# Complete Fix and Test Script for Anime Scraper
Write-Host "🔧 Anime Scraper - Complete Fix & Test" -ForegroundColor Cyan
Write-Host "=======================================" -ForegroundColor Cyan

Write-Host ""
Write-Host "Step 1: Checking current database state..." -ForegroundColor Yellow
$animeCount = php artisan tinker --execute="echo \App\Models\Anime::count();"
$platformCount = php artisan tinker --execute="echo \App\Models\AnimePlatform::count();"

Write-Host "Current: $animeCount animes, $platformCount platform records" -ForegroundColor Gray

Write-Host ""
Write-Host "Step 2: Preview duplicate data..." -ForegroundColor Yellow
php artisan anime:clean-duplicates --dry-run

Write-Host ""
$cleanup = Read-Host "Do you want to clean duplicate data? (y/n)"

if ($cleanup -eq 'y' -or $cleanup -eq 'Y') {
    Write-Host "Step 3: Cleaning duplicate data..." -ForegroundColor Yellow
    php artisan anime:clean-duplicates --force
} else {
    Write-Host "Step 3: Skipping data cleanup..." -ForegroundColor Gray
}

Write-Host ""
Write-Host "Step 4: Testing improved scraper..." -ForegroundColor Yellow
Write-Host "This will use the fixed extraction logic to avoid duplicates" -ForegroundColor Gray

# Test the scraper with a small sample first
php artisan anime:scrape --detail

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "✅ Scraper test successful!" -ForegroundColor Green
    
    Write-Host ""
    Write-Host "Final Statistics:" -ForegroundColor Cyan
    $finalAnimeCount = php artisan tinker --execute="echo \App\Models\Anime::count();"
    $finalPlatformCount = php artisan tinker --execute="echo \App\Models\AnimePlatform::count();"
    $validTitles = php artisan tinker --execute="echo \App\Models\Anime::whereNotNull('title')->where('title', '!=', '')->count();"
    
    Write-Host "Final: $finalAnimeCount animes ($validTitles with titles), $finalPlatformCount platform records" -ForegroundColor Green
    
    Write-Host ""
    Write-Host "Sample anime titles:" -ForegroundColor Cyan
    php artisan tinker --execute="\App\Models\Anime::whereNotNull('title')->where('title', '!=', '')->limit(5)->get(['id', 'title'])->each(function(\$a) { echo \$a->id . ': ' . \$a->title . PHP_EOL; });"
    
    Write-Host ""
    Write-Host "✅ All done! The scraper should now work better with:" -ForegroundColor Green
    Write-Host "  - Fixed HTTP connection issues" -ForegroundColor White
    Write-Host "  - Improved title extraction" -ForegroundColor White  
    Write-Host "  - Reduced duplicate platform records" -ForegroundColor White
    Write-Host "  - Better error handling" -ForegroundColor White
    
} else {
    Write-Host ""
    Write-Host "❌ Scraper still has issues. Let's diagnose..." -ForegroundColor Red
    
    Write-Host ""
    Write-Host "Checking debug files..." -ForegroundColor Yellow
    $debugFiles = Get-ChildItem "storage\app\debug_anime_*.html" -ErrorAction SilentlyContinue | Sort-Object LastWriteTime -Descending | Select-Object -First 1
    
    if ($debugFiles) {
        Write-Host "Latest debug file: $($debugFiles.Name) ($(([math]::Round($debugFiles.Length / 1KB, 2))) KB)" -ForegroundColor Gray
        
        if ($debugFiles.Length -gt 10000) {
            Write-Host "✅ HTML was downloaded successfully" -ForegroundColor Green
            Write-Host "❌ Issue is likely in the parsing logic" -ForegroundColor Red
        } else {
            Write-Host "❌ HTML download failed or returned error page" -ForegroundColor Red
        }
    }
    
    Write-Host ""
    Write-Host "Checking logs..." -ForegroundColor Yellow
    if (Test-Path "storage\logs\laravel.log") {
        Write-Host "Recent log entries:" -ForegroundColor Gray
        Get-Content "storage\logs\laravel.log" -Tail 10
    }
}

Write-Host ""
Write-Host "Available commands for further testing:" -ForegroundColor Cyan
Write-Host "- php artisan anime:scrape                     (Normal scraping)" -ForegroundColor White
Write-Host "- php artisan anime:scrape --detail            (With detailed stats)" -ForegroundColor White
Write-Host "- php artisan anime:clean-duplicates           (Clean duplicate data)" -ForegroundColor White
Write-Host "- php artisan anime:analyze                    (Analyze webpage structure)" -ForegroundColor White

Write-Host ""
Write-Host "Press any key to continue..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
