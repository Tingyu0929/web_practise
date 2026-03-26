import axios from 'axios'

const api = axios.create({
    baseURL: '/api',
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
})

// 請求攔截器
api.interceptors.request.use(
    config => {
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
        console.error('API Error:', error)
        return Promise.reject(error)
    }
)

// API 方法
export const animeAPI = {
    // 取得動漫列表
    getAnimes(params = {}) {
        return api.get('/animes', { params })
    },

    // 取得單一動漫
    getAnime(id) {
        return api.get(`/animes/${id}`)
    },

    // 取得平台列表
    getPlatforms() {
        return api.get('/animes/platforms')
    },

    // 取得統計資訊
    getStats() {
        return api.get('/animes/stats')
    },

    // 測試連線
    test() {
        return api.get('/test')
    }
}

export default api
