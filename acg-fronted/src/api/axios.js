import axios from 'axios'

// 創建axios實例
const api = axios.create({
  baseURL: 'http://localhost:8000', // Laravel後端地址
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// 請求攔截器
api.interceptors.request.use(
  config => {
    // 可以在這裡添加認證token
    // const token = localStorage.getItem('token')
    // if (token) {
    //   config.headers.Authorization = `Bearer ${token}`
    // }
    return config
  },
  error => {
    return Promise.reject(error)
  }
)

// 響應攔截器
api.interceptors.response.use(
  response => {
    return response.data
  },
  error => {
    console.error('API錯誤:', error)
    return Promise.reject(error)
  }
)

export default api
