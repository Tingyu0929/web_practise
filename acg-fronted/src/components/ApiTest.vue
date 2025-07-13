<template>
  <div class="api-test">
    <h2>前後端連接測試</h2>
    
    <div class="test-section">
      <button @click="testConnection" :disabled="loading">
        {{ loading ? '測試中...' : '測試API連接' }}
      </button>
      
      <div v-if="result" class="result" :class="result.status">
        <h3>✅ 測試結果:</h3>
        <pre>{{ JSON.stringify(result, null, 2) }}</pre>
      </div>
      
      <div v-if="error" class="error">
        <h3>❌ 錯誤信息:</h3>
        <pre>{{ error }}</pre>
      </div>
    </div>

    <div class="info-section">
      <h3>📊 系統信息</h3>
      <div class="info-grid">
        <div class="info-item">
          <strong>前端:</strong> Vue 3 + Vite (端口 5173)
        </div>
        <div class="info-item">
          <strong>後端:</strong> Laravel 11 (端口 8000)
        </div>
        <div class="info-item">
          <strong>HTTP庫:</strong> Axios
        </div>
        <div class="info-item">
          <strong>CORS:</strong> 已配置
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../api/axios'

export default {
  name: 'ApiTest',
  data() {
    return {
      loading: false,
      result: null,
      error: null
    }
  },
  methods: {
    async testConnection() {
      this.loading = true
      this.result = null
      this.error = null
      
      try {
        const response = await api.get('/api/test')
        this.result = response
        
        // 解碼Unicode字符
        if (response.message) {
          this.result.message = this.decodeUnicode(response.message)
        }
      } catch (err) {
        this.error = `連接失敗: ${err.response?.status || 'Network Error'} - ${err.message}`
        console.error('連接測試失敗:', err)
      } finally {
        this.loading = false
      }
    },
    
    decodeUnicode(str) {
      return str.replace(/\\u[\dA-F]{4}/gi, function (match) {
        return String.fromCharCode(parseInt(match.replace(/\\u/g, ''), 16))
      })
    }
  },
  
  mounted() {
    // 組件加載時自動測試連接
    this.testConnection()
  }
}
</script>

<style scoped>
.api-test {
  padding: 20px;
  max-width: 900px;
  margin: 0 auto;
}

.test-section, .info-section {
  margin-bottom: 30px;
  padding: 25px;
  border: 1px solid #ddd;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.95);
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  margin-top: 15px;
}

.info-item {
  padding: 15px;
  background: #f8f9fa;
  border-radius: 8px;
  border-left: 4px solid #007bff;
}

button {
  background: linear-gradient(135deg, #007bff, #0056b3);
  color: white;
  border: none;
  padding: 15px 30px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 16px;
  font-weight: bold;
  transition: all 0.3s ease;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

button:hover:not(:disabled) {
  background: linear-gradient(135deg, #0056b3, #004085);
  transform: translateY(-2px);
  box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}

button:disabled {
  background: #6c757d;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.result {
  margin-top: 20px;
  padding: 20px;
  border-radius: 8px;
  animation: fadeIn 0.5s ease-in;
}

.result.success {
  background: linear-gradient(135deg, #d4edda, #c3e6cb);
  border: 1px solid #c3e6cb;
  color: #155724;
}

.error {
  margin-top: 20px;
  padding: 20px;
  background: linear-gradient(135deg, #f8d7da, #f5c6cb);
  border: 1px solid #f5c6cb;
  color: #721c24;
  border-radius: 8px;
  animation: fadeIn 0.5s ease-in;
}

pre {
  background: rgba(0,0,0,0.05);
  padding: 15px;
  border-radius: 6px;
  overflow-x: auto;
  white-space: pre-wrap;
  font-family: 'Courier New', monospace;
  font-size: 14px;
  line-height: 1.4;
}

h2, h3, h4 {
  color: #2c3e50;
  margin-bottom: 15px;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
