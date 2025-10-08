<template>
  <div class="anime-list">
    <!-- 頁首 -->
    <header class="header">
      <h1 class="title">
        <i class="pi pi-video"></i>
        動漫資訊平台
      </h1>
      <div class="stats" v-if="stats">
        <span><i class="pi pi-database"></i> {{ stats.total_animes }} 部動漫</span>
        <span><i class="pi pi-desktop"></i> {{ stats.platforms_count }} 個平台</span>
      </div>
    </header>

    <!-- 搜尋和篩選區域 -->
    <div class="search-section">
      <div class="search-container">
        <span class="p-input-icon-left search-input">
          <i class="pi pi-search"></i>
          <InputText
              style="margin-left: 10px;"
            v-model="searchQuery"
            placeholder="搜尋動漫名稱..."
            @input="onSearch"
            class="w-full"
          />
        </span>

        <Select
          v-model="selectedPlatform"
          :options="platformOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="選擇平台"
          @change="onFilterChange"
          class="platform-select"
        />

        <Button
          label="清除篩選"
          icon="pi pi-times"
          severity="secondary"
          outlined
          @click="clearFilters"
          v-if="searchQuery || selectedPlatform"
        />
      </div>
    </div>

    <!-- 載入中 -->
    <div v-if="loading" class="loading-container">
      <ProgressSpinner />
      <p>載入中...</p>
    </div>

    <!-- 動漫卡片網格 -->
    <div v-else-if="animes.length > 0" class="anime-grid">
      <Card
        v-for="anime in animes"
        :key="anime.id"
        class="anime-card"
      >
        <template #header>
          <div class="anime-image">
            <img
              :src="anime.image_url || '/placeholder.png'"
              :alt="anime.title"
              @error="handleImageError"
            />
            <div class="anime-overlay">
              <Button
                icon="pi pi-external-link"
                rounded
                text
                severity="secondary"
                @click="openAnimeLink(anime.source_url)"
                v-if="anime.source_url"
              />
            </div>
          </div>
        </template>
        <template #title>
          <div class="anime-title">{{ anime.title }}</div>
        </template>
        <template #content>
          <div class="anime-info">
            <div class="info-item" v-if="anime.platforms && anime.platforms.length > 0">
              <i class="pi pi-desktop"></i>
              <span>{{ anime.platforms.map(p => p.platform).join(', ') }}</span>
            </div>
            <div class="info-item" v-if="anime.type">
              <i class="pi pi-tag"></i>
              <span>{{ anime.type }}</span>
            </div>
            <div class="info-item" v-if="anime.status">
              <i class="pi pi-info-circle"></i>
              <span>{{ anime.status }}</span>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- 無資料 -->
    <div v-else class="no-data">
      <i class="pi pi-inbox"></i>
      <p>找不到動漫資料</p>
      <Button label="重新載入" icon="pi pi-refresh" @click="fetchAnimes" />
    </div>

    <!-- 分頁 -->
    <div v-if="totalPages > 1" class="pagination">
      <Paginator
        :rows="perPage"
        :totalRecords="totalRecords"
        @page="onPageChange"
        :rowsPerPageOptions="[12, 24, 48, 96]"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { animeAPI } from '../services/api'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Paginator from 'primevue/paginator'
import ProgressSpinner from 'primevue/progressspinner'

// 狀態
const animes = ref([])
const platforms = ref([])
const stats = ref(null)
const loading = ref(false)
const searchQuery = ref('')
const selectedPlatform = ref(null)
const currentPage = ref(1)
const perPage = ref(24)
const totalRecords = ref(0)

// 計算屬性
const totalPages = computed(() => Math.ceil(totalRecords.value / perPage.value))

const platformOptions = computed(() => [
  { label: '所有平台', value: null },
  ...platforms.value.map(p => ({ label: p.name, value: p.name }))
])

// 方法
const fetchAnimes = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value
    }

    if (searchQuery.value) {
      params.search = searchQuery.value
    }

    if (selectedPlatform.value) {
      params.platform = selectedPlatform.value
    }

    const response = await animeAPI.getAnimes(params)
    animes.value = response.data
    totalRecords.value = response.total
    currentPage.value = response.current_page
  } catch (error) {
    console.error('Failed to fetch animes:', error)
  } finally {
    loading.value = false
  }
}

const fetchPlatforms = async () => {
  try {
    platforms.value = await animeAPI.getPlatforms()
  } catch (error) {
    console.error('Failed to fetch platforms:', error)
  }
}

const fetchStats = async () => {
  try {
    stats.value = await animeAPI.getStats()
  } catch (error) {
    console.error('Failed to fetch stats:', error)
  }
}

// 防抖搜尋
let searchTimeout
const onSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    currentPage.value = 1
    fetchAnimes()
  }, 500)
}

const onFilterChange = () => {
  currentPage.value = 1
  fetchAnimes()
}

const onPageChange = (event) => {
  currentPage.value = event.page + 1
  perPage.value = event.rows
  fetchAnimes()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const clearFilters = () => {
  searchQuery.value = ''
  selectedPlatform.value = null
  currentPage.value = 1
  fetchAnimes()
}

const openAnimeLink = (url) => {
  if (url) {
    window.open(url, '_blank')
  }
}

const handleImageError = (event) => {
  event.target.src = 'https://via.placeholder.com/300x420/1a1a1a/ffffff?text=No+Image'
}

// 生命週期
onMounted(() => {
  fetchAnimes()
  fetchPlatforms()
  fetchStats()
})
</script>

<style scoped>
.anime-list {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.header {
  margin-bottom: 2rem;
  text-align: center;
}

.title {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--color-text);
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
}

.title i {
  font-size: 2rem;
}

.stats {
  display: flex;
  gap: 2rem;
  justify-content: center;
  color: var(--color-text-secondary);
  font-size: 0.95rem;
}

.stats span {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.search-section {
  margin-bottom: 2rem;
}

.search-container {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
}

.search-input {
  flex: 1;
  min-width: 300px;
}

.platform-select {
  min-width: 200px;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  gap: 1rem;
  color: var(--color-text-secondary);
}

.anime-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.anime-card {
  transition: transform 0.2s, box-shadow 0.2s;
  cursor: pointer;
}

.anime-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 16px rgba(255, 255, 255, 0.1);
}

.anime-image {
  position: relative;
  width: 100%;
  padding-top: 140%;
  overflow: hidden;
  background: var(--color-surface);
}

.anime-image img {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.anime-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.2s;
}

.anime-card:hover .anime-overlay {
  opacity: 1;
}

.anime-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text);
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  min-height: 2.8em;
}

.anime-info {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--color-text-secondary);
  font-size: 0.875rem;
}

.info-item i {
  color: var(--color-text-muted);
}

.no-data {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  gap: 1rem;
  color: var(--color-text-secondary);
}

.no-data i {
  font-size: 4rem;
  color: var(--color-text-muted);
}

.pagination {
  display: flex;
  justify-content: center;
  margin-top: 2rem;
}

@media (max-width: 768px) {
  .anime-list {
    padding: 1rem;
  }

  .title {
    font-size: 1.8rem;
  }

  .search-container {
    flex-direction: column;
  }

  .search-input,
  .platform-select {
    width: 100%;
    min-width: unset;
  }

  .anime-grid {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
  }
}
</style>
