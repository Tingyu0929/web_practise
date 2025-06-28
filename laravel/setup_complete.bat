@echo off
echo ============================================
echo       動漫爬蟲專案完整設置腳本
echo ============================================
echo.

echo 步驟 1: 檢查當前 migration 文件...
dir database\migrations\*anime*.php
echo.

echo 步驟 2: 清理資料庫並重新建立表格...
php artisan migrate:fresh
echo.

if %errorlevel% equ 0 (
    echo ✅ 資料庫設置成功！
    echo.
    
    echo 步驟 3: 檢查 migration 狀態...
    php artisan migrate:status
    echo.
    
    echo 步驟 4: 檢查資料表是否正確建立...
    php artisan tinker --execute="echo 'Animes table: ' . \App\Models\Anime::count() . ' records'; echo 'Platforms table: ' . \App\Models\AnimePlatform::count() . ' records';"
    echo.
    
    echo 步驟 5: 測試爬蟲功能...
    php artisan anime:scrape --verbose
    echo.
    
    echo ✅ 全部設置完成！
    echo.
    echo 可用的命令：
    echo - php artisan anime:scrape                     ^(爬取動漫資料^)
    echo - php artisan anime:scrape --clean-old         ^(清除舊資料後爬取^)
    echo - php artisan anime:scrape --verbose           ^(顯示詳細輸出^)
    echo - php artisan anime:analyze                    ^(分析網頁結構^)
    
) else (
    echo ❌ Migration 失敗！
    echo.
    echo 請檢查以下問題：
    echo 1. 資料庫連接是否正確 ^(.env 文件^)
    echo 2. 資料庫是否存在
    echo 3. 是否有權限創建表格
    echo.
    echo 手動解決方法：
    echo 1. 檢查 .env 中的資料庫設定
    echo 2. php artisan migrate:fresh --force
    echo 3. 如果還是失敗，請檢查錯誤訊息
)

echo.
pause
