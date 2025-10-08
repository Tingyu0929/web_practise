# 資料庫與快取設定指南

## 已完成的配置

### ✅ 資料庫配置 (MySQL)
- 預設連接已改為 MySQL
- 配置檔案已更新
- .env 檔案已設定

### ✅ Redis 配置
- 快取 (CACHE_STORE) 改為 redis
- 佇列 (QUEUE_CONNECTION) 改為 redis  
- Session (SESSION_DRIVER) 改為 redis

## 需要手動完成的步驟

### 1. 安裝 MySQL 伺服器
```bash
# Windows (使用 Chocolatey)
choco install mysql

# 或下載 MySQL Community Server
# https://dev.mysql.com/downloads/mysql/
```

### 2. 安裝 Redis 伺服器
```bash
# Windows (使用 Chocolatey)
choco install redis-64

# 或下載 Redis for Windows
# https://github.com/microsoftarchive/redis/releases
```

### 3. 創建資料庫
```sql
CREATE DATABASE animation_web CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. 設定資料庫密碼
編輯 `.env` 檔案中的 `DB_PASSWORD` 欄位

### 5. 執行資料庫遷移
```bash
php artisan migrate
```

### 6. 測試連接
```bash
php artisan anime:stats
```

## 目前的配置

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=animation_web
DB_USERNAME=root
DB_PASSWORD=

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## 故障排除

如果遇到連接問題，請確認：
1. MySQL 和 Redis 服務正在運行
2. 防火牆允許連接埠 3306 (MySQL) 和 6379 (Redis)
3. 資料庫使用者權限正確設定