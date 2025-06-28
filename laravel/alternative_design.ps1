# Alternative: Single Table Design Option
Write-Host "🤔 Alternative Design: Single Table Option" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan

Write-Host ""
Write-Host "Current design (normalized):" -ForegroundColor Yellow
Write-Host "- animes table: stores anime basic info" -ForegroundColor Gray
Write-Host "- anime_platforms table: stores anime-platform relationships" -ForegroundColor Gray
Write-Host "- Advantage: proper database normalization, flexible queries" -ForegroundColor Green
Write-Host "- Disadvantage: more complex, two tables to manage" -ForegroundColor Red

Write-Host ""
Write-Host "Alternative design (denormalized):" -ForegroundColor Yellow  
Write-Host "- Single animes table with platform info as JSON" -ForegroundColor Gray
Write-Host "- Advantage: simpler, only one table" -ForegroundColor Green
Write-Host "- Disadvantage: harder to query by platform, less efficient" -ForegroundColor Red

Write-Host ""
$choice = Read-Host "Do you want to see the current data analysis first? (y/n)"

if ($choice -eq 'y' -or $choice -eq 'Y') {
    Write-Host ""
    Write-Host "Analyzing current data structure..." -ForegroundColor Yellow
    php artisan anime:analyze-data
    
    Write-Host ""
    $convert = Read-Host "Do you want to convert to single table design? (y/n)"
    
    if ($convert -eq 'y' -or $convert -eq 'Y') {
        Write-Host ""
        Write-Host "⚠️  This will merge platform data into anime table and drop anime_platforms table" -ForegroundColor Red
        $confirm = Read-Host "Are you sure? This cannot be undone easily (y/n)"
        
        if ($confirm -eq 'y' -or $confirm -eq 'Y') {
            Write-Host ""
            Write-Host "Converting to single table design..." -ForegroundColor Yellow
            # We would implement the conversion here
            Write-Host "❌ Conversion not implemented yet - let's analyze first" -ForegroundColor Red
        }
    }
} else {
    Write-Host ""
    Write-Host "Skipping analysis. The current two-table design is actually standard practice." -ForegroundColor Gray
    Write-Host ""
    Write-Host "Benefits of current design:" -ForegroundColor Cyan
    Write-Host "✅ Can easily query 'all anime on Netflix'" -ForegroundColor Green
    Write-Host "✅ Can easily query 'all platforms for specific anime'" -ForegroundColor Green  
    Write-Host "✅ No data duplication" -ForegroundColor Green
    Write-Host "✅ Flexible for future features" -ForegroundColor Green
}

Write-Host ""
Write-Host "Press any key to continue..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
