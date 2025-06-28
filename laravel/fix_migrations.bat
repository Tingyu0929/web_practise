@echo off
echo 正在修復 migration 問題...

echo 步驟 1: 重置資料庫...
php artisan migrate:fresh

if %errorlevel% equ 0 (
    echo ✅ Migration 重置成功！
    echo.
    echo 步驟 2: 檢查 migration 狀態...
    php artisan migrate:status
    echo.
    echo 步驟 3: 測試爬蟲...
    php artisan anime:scrape
) else (
    echo ❌ Migration 重置失敗，嘗試手動清理...
    echo 請手動執行以下命令：
    echo 1. php artisan tinker
    echo 2. Schema::dropIfExists('anime_platforms');
    echo 3. Schema::dropIfExists('animes');
    echo 4. exit
    echo 5. php artisan migrate
)

pause
