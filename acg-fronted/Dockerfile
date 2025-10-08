FROM node:20-alpine

# 安裝必要工具
RUN apk add --no-cache wget

# 設定工作目錄
WORKDIR /app

# 複製 package 檔案並安裝依賴（利用 Docker 快取）
COPY package*.json ./
RUN npm ci --prefer-offline --no-audit

# 複製應用程式檔案
COPY . .

# 暴露端口
EXPOSE 5173

# 啟動開發伺服器
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
