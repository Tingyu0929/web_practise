<template>
  <div id="app">
    <header>
      <h1>🎌 ACG 前後端測試</h1>
      <p>Vue 3 + Laravel API 連接測試</p>
    </header>
    
    <main>
      <ApiTest />
    </main>
    
    <footer>
      <p>前端端口: 5173 | 後端端口: 8000</p>
    </footer>
  </div>
</template>

<script>
import ApiTest from './components/ApiTest.vue'

export default {
  name: 'App',
  components: {
    ApiTest
  }
}
</script>

<style>
#app {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  color: #2c3e50;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

header {
  background: rgba(255, 255, 255, 0.95);
  padding: 20px;
  text-align: center;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

header h1 {
  color: #2c3e50;
  margin-bottom: 10px;
}

header p {
  color: #7f8c8d;
}

main {
  flex: 1;
  padding: 20px;
  background: rgba(255, 255, 255, 0.9);
  margin: 20px;
  border-radius: 10px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

footer {
  background: rgba(255, 255, 255, 0.95);
  padding: 15px;
  text-align: center;
  color: #7f8c8d;
  font-size: 14px;
}
</style>
