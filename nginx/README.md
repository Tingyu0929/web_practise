# Nginx 配置說明

## CORS 問題解決方案

當前遇到的 CORS 錯誤是因為：
- 前端在 `https://acg.404-studio.work`
- API 在 `https://acg-api.404-studio.work`
- 兩者屬於不同的域名（跨域）
- Nginx 需要正確配置 CORS headers

## 配置步驟

### 方法 1: 使用 Nginx（推薦）

1. **複製配置文件到 Nginx 配置目錄**

在電腦2上執行：

```bash
# Windows 上的 Nginx 通常在
# C:\nginx\conf\
# 或
# D:\nginx\conf\

# 複製 API 配置
copy E:\Project\Web\animation-web\nginx\api.conf C:\nginx\conf\sites-available\
copy E:\Project\Web\animation-web\nginx\frontend.conf C:\nginx\conf\sites-available\

# 創建 sites-enabled 目錄（如果不存在）
mkdir C:\nginx\conf\sites-enabled

# 創建軟連結或複製到 sites-enabled
copy C:\nginx\conf\sites-available\api.conf C:\nginx\conf\sites-enabled\
copy C:\nginx\conf\sites-available\frontend.conf C:\nginx\conf\sites-enabled\
```

2. **修改 nginx.conf 引入配置**

編輯 `C:\nginx\conf\nginx.conf`，在 `http` 區塊內添加：

```nginx
http {
    # ... 其他配置 ...

    # 引入自定義配置
    include sites-enabled/*.conf;
}
```

3. **測試並重啟 Nginx**

```bash
# 測試配置
nginx -t

# 重啟 Nginx
nginx -s reload
```

### 方法 2: 修改 Laravel 的 CORS 配置使其更明確

編輯 `laravel/config/cors.php`：

```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['https://acg.404-studio.work', 'http://acg.404-studio.work'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,  // 改為 true
];
```

然後在 Docker 中清除配置緩存：

```bash
docker exec animation_web_laravel php artisan config:clear
docker exec animation_web_laravel php artisan cache:clear
```

### 方法 3: 臨時測試 - 在前端使用代理

修改 `acg-fronted/vite.config.js`：

```javascript
export default defineConfig({
  server: {
    proxy: {
      '/api': {
        target: 'https://acg-api.404-studio.work',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api/, '/api')
      }
    }
  }
})
```

然後前端使用相對路徑 `/api/animes` 而不是絕對路徑。

## SSL 憑證配置

如果你使用 HTTPS，需要修改 Nginx 配置中的憑證路徑：

```nginx
ssl_certificate /path/to/your/fullchain.pem;
ssl_certificate_key /path/to/your/privkey.pem;
```

如果使用 Let's Encrypt：

```nginx
ssl_certificate /etc/letsencrypt/live/acg-api.404-studio.work/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/acg-api.404-studio.work/privkey.pem;
```

## 檢查配置是否生效

1. **檢查 CORS headers**

```bash
curl -I https://acg-api.404-studio.work/api/test
```

應該看到：
```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization
```

2. **測試 OPTIONS 請求**

```bash
curl -X OPTIONS https://acg-api.404-studio.work/api/animes -H "Origin: https://acg.404-studio.work" -v
```

應該返回 204 狀態碼。

## 常見問題

### Q: 為什麼需要 `proxy_hide_header`？

A: Laravel 也會設置 CORS headers，如果 Nginx 和 Laravel 都設置，會導致重複的 header，瀏覽器會報錯。使用 `proxy_hide_header` 隱藏 Laravel 的 header，只使用 Nginx 設置的。

### Q: `always` 參數是什麼意思？

A: 確保即使在錯誤響應（4xx, 5xx）時也添加 CORS headers，否則前端無法讀取錯誤訊息。

### Q: 為什麼要單獨處理 OPTIONS 請求？

A: 瀏覽器在發送跨域請求前會先發送 OPTIONS 預檢請求（preflight request），我們需要快速響應 204，告訴瀏覽器允許跨域。
