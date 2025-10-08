# Animation Web

動漫資訊平台，提供動漫搜尋、瀏覽、評分等功能。

## 技術堆疊

### 後端
- Laravel 12
- PHP 8.2
- MySQL 8.0
- Redis 7.x

### 前端
- Vue 3
- Vite
- Axios

## 專案結構

```
animation-web/
├── laravel/              # Laravel 後端
│   ├── app/
│   │   └── Console/Commands/  # Artisan 指令
│   ├── database/migrations/   # 資料庫遷移檔案
│   └── routes/
├── acg-fronted/          # Vue 前端
│   ├── src/
│   └── public/
├── docker-compose.yml    # Docker 完整環境配置
└── setup.bat / setup.sh  # 快速設定腳本
```

## 快速開始

### 使用 Docker（推薦）

#### Windows:
```bash
setup.bat
```

#### Linux/macOS:
```bash
chmod +x setup.sh
./setup.sh
```

### 手動設定

```bash
# 啟動所有服務（MySQL, Redis, Laravel, Frontend, Scheduler）
docker-compose up -d

# 等待服務就緒
sleep 15

# 設定 Laravel
docker-compose exec laravel sed -i "s/^DB_HOST=.*/DB_HOST=mysql/" .env
docker-compose exec laravel sed -i "s/^REDIS_HOST=.*/REDIS_HOST=redis/" .env
docker-compose exec laravel php artisan key:generate
docker-compose exec laravel php artisan config:clear
docker-compose exec laravel php artisan migrate:fresh

# 執行爬蟲
docker-compose exec laravel php artisan anime:scrape
```

## 可用的 Artisan 指令

```bash
# 爬取動漫資料（手動執行）
docker-compose exec laravel php artisan anime:scrape

# 清理重複資料
docker-compose exec laravel php artisan anime:clean-duplicates

# 顯示動漫統計
docker-compose exec laravel php artisan anime:stats

# 測試爬蟲修正
docker-compose exec laravel php artisan anime:test-fixes
```

## 服務端口

- **前端**: http://localhost:5173
- **後端 API**: http://localhost:8000
- **MySQL**: localhost:3306
- **Redis**: localhost:6379

## 定時任務

專案內建自動化爬蟲排程器：

- **執行時間**: 每天中午 12:00（台北時區）
- **執行任務**: 自動爬取最新動漫資料
- **配置位置**: `laravel/routes/console.php`
- **日誌位置**: `laravel/storage/logs/scheduler.log`

查看排程列表：
```bash
docker-compose exec scheduler php artisan schedule:list
```

查看排程器日誌：
```bash
docker-compose logs -f scheduler
```

手動觸發排程器（測試用）：
```bash
docker-compose exec scheduler php artisan schedule:run
```

## Docker 管理

### 啟動服務

```bash
# 啟動所有服務
docker-compose up -d

# 查看服務狀態
docker-compose ps
```

### 停止服務

```bash
# 停止所有服務
docker-compose down

# 停止並刪除 volumes（清除所有資料）
docker-compose down -v
```

### 查看日誌

```bash
# 後端日誌
docker-compose logs -f laravel

# 前端日誌
docker-compose logs -f frontend

# 排程器日誌
docker-compose logs -f scheduler

# 所有服務日誌
docker-compose logs -f
```

### 重啟特定服務

```bash
# 重啟 Laravel
docker-compose restart laravel

# 重啟前端
docker-compose restart frontend

# 重啟排程器
docker-compose restart scheduler
```

## 資料庫結構

### animes 表
- `id`: 主鍵
- `title`: 動漫標題
- `anime_id`: 動漫 ID
- `image_url`: 圖片 URL
- `rating`: 評分
- `status`: 狀態
- `platform_id`: 平台 ID
- `url`: 動漫連結
- `created_at`, `updated_at`: 時間戳

### anime_platforms 表
- `id`: 主鍵
- `name`: 平台名稱
- `base_url`: 平台網址
- `created_at`, `updated_at`: 時間戳

## 開發注意事項

- 後端 Docker 容器會自動安裝 Composer 依賴
- 前端 Docker 容器會自動安裝 npm 依賴
- `.env` 檔案需要手動配置資料庫主機（執行 setup 腳本會自動處理）
- 資料庫密碼預設為 `root`（本地開發用）
- Redis 用於快取和 Session 儲存
- 排程器容器會每分鐘檢查並執行定時任務
- 每天中午 12:00 會自動執行爬蟲更新動漫資料

## 容器說明

專案包含以下 Docker 容器：

1. **mysql** - MySQL 8.0 資料庫
2. **redis** - Redis 7 快取服務
3. **laravel** - Laravel API 服務
4. **frontend** - Vue 3 前端服務
5. **scheduler** - Laravel 任務排程器（每天 12:00 自動爬蟲）

## 授權

This project is private and proprietary.
