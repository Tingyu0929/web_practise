# Test the fixed scraper
Write-Host "Testing Fixed Anime Scraper" -ForegroundColor Cyan
Write-Host "===========================" -ForegroundColor Cyan

Write-Host "Testing connection to acgsecrets.hk..." -ForegroundColor Yellow

# Test basic connectivity
try {
    $response = Invoke-WebRequest -Uri "https://acgsecrets.hk" -UseBasicParsing -TimeoutSec 10
    Write-Host "✅ Website is reachable (Status: $($response.StatusCode))" -ForegroundColor Green
} catch {
    Write-Host "⚠️  Website might be slow or blocked: $_" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Testing anime scraper..." -ForegroundColor Yellow

# Run the scraper
php artisan anime:scrape --detail --verbose

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "✅ Scraper test completed!" -ForegroundColor Green
    
    # Show some statistics
    Write-Host ""
    Write-Host "Quick database check:" -ForegroundColor Cyan
    php artisan tinker --execute="echo 'Total animes: ' . \App\Models\Anime::count(); echo 'Total platforms: ' . \App\Models\AnimePlatform::count();"
    
} else {
    Write-Host ""
    Write-Host "❌ Scraper still having issues" -ForegroundColor Red
    Write-Host "Let's check the debug HTML file..." -ForegroundColor Yellow
    
    # Check if debug file was created
    $debugFiles = Get-ChildItem "storage\app\debug_anime_*.html" -ErrorAction SilentlyContinue | Sort-Object LastWriteTime -Descending | Select-Object -First 1
    
    if ($debugFiles) {
        Write-Host "Found debug file: $($debugFiles.Name)" -ForegroundColor Gray
        Write-Host "File size: $([math]::Round($debugFiles.Length / 1KB, 2)) KB" -ForegroundColor Gray
        
        if ($debugFiles.Length -gt 1000) {
            Write-Host "✅ HTML content was downloaded successfully" -ForegroundColor Green
        } else {
            Write-Host "⚠️  HTML file is too small, might be an error page" -ForegroundColor Yellow
        }
    } else {
        Write-Host "❌ No debug HTML file found" -ForegroundColor Red
    }
}
