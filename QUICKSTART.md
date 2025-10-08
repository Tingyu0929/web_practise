# 快速啟動指南

## 🚀 一鍵啟動

### Windows
```bash
setup.bat
```

### Linux/macOS
```bash
chmod +x setup.sh
./setup.sh
```

## 📋 服務列表

執行 `docker-compose up -d` 後，會啟動以下服務：

| 服務 | 容器名稱 | 端口 | 說明 |
|------|---------|------|------|
| MySQL | animation_web_mysql | 3306 | 資料庫 |
| Redis | animation_web_redis | 6379 | 快取服務 |
| Laravel | animation_web_laravel | 8000 | 後端 API |
| Vue | animation_web_frontend | 5173 | 前端介面 |
| Scheduler | animation_web_scheduler | - | 定時任務 |

## ⏰ 自動化任務

**每天中午 12:00（台北時區）** 自動執行爬蟲更新動漫資料

查看排程器狀態：
```bash
docker-compose logs -f scheduler
```

查看爬蟲日誌：
```bash
docker-compose exec scheduler cat storage/logs/scheduler.log
```

## 🔧 常用指令

```bash
# 啟動所有服務
docker-compose up -d

# 停止所有服務
docker-compose down

# 查看服務狀態
docker-compose ps

# 查看日誌
docker-compose logs -f [service_name]

# 手動執行爬蟲
docker-compose exec laravel php artisan anime:scrape

# 查看動漫統計
docker-compose exec laravel php artisan anime:stats

# 測試排程器
docker-compose exec scheduler php artisan schedule:run
```

## 🌐 訪問地址

- **前端**: http://localhost:5173
- **後端 API**: http://localhost:8000

## ⚠️ 注意事項

1. 首次啟動需要執行 `setup.bat` 或 `setup.sh` 進行初始化
2. 確保 3306、6379、8000、5173 端口未被占用
3. 資料會持久化保存在 Docker volumes 中
4. 停止服務不會刪除資料，除非使用 `docker-compose down -v`
