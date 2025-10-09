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

        <Dropdown
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
        @click="showAnimeDetail(anime)"
      >
        <template #header>
          <div class="anime-image">
            <img
              :src="anime.image_url || '/placeholder.png'"
              :alt="anime.title"
              @error="handleImageError"
            />
            <!-- 每周更新時間標籤 -->
            <div v-if="anime.weekly_schedule" class="weekly-schedule-badge">
              <i class="pi pi-clock"></i>
              <span>{{ anime.weekly_schedule }}</span>
            </div>
            <div class="anime-overlay">
              <Button
                icon="pi pi-info-circle"
                label="詳細資訊"
                rounded
                severity="secondary"
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
            <div class="info-item" v-if="anime.release_date">
              <i class="pi pi-calendar"></i>
              <span>{{ formatDate(anime.release_date) }}</span>
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

    <!-- 動漫詳細資訊彈出視窗 -->
    <Dialog
      v-model:visible="showDetailDialog"
      :header="selectedAnime?.title"
      :modal="true"
      :style="{ width: '50vw' }"
      :breakpoints="{ '960px': '75vw', '641px': '90vw' }"
    >
      <div v-if="selectedAnime" class="anime-detail">
        <div class="detail-image">
          <img :src="selectedAnime.image_url" :alt="selectedAnime.title" />
        </div>

        <div class="detail-content">
          <!-- 基本資訊 -->
          <div class="detail-section">
            <h3><i class="pi pi-info-circle"></i> 基本資訊</h3>
            <div class="detail-grid">
              <div v-if="selectedAnime.type" class="detail-item">
                <strong>類型：</strong>{{ selectedAnime.type }}
              </div>
              <div v-if="selectedAnime.release_date" class="detail-item">
                <strong>發布日期：</strong>{{ formatDate(selectedAnime.release_date) }}
              </div>
              <div v-if="selectedAnime.weekly_schedule" class="detail-item">
                <strong>更新時間：</strong>{{ selectedAnime.weekly_schedule }}
              </div>
              <div v-if="selectedAnime.copyright" class="detail-item">
                <strong>版權：</strong>{{ selectedAnime.copyright }}
              </div>
            </div>
          </div>

          <!-- 描述 -->
          <div v-if="selectedAnime.description" class="detail-section">
            <h3><i class="pi pi-book"></i> 故事簡介</h3>
            <p>{{ selectedAnime.description }}</p>
          </div>

          <!-- 配音員 -->
          <div v-if="selectedAnime.voice_actors && selectedAnime.voice_actors.length > 0" class="detail-section">
            <h3><i class="pi pi-users"></i> 配音員</h3>
            <div class="voice-actors-list">
              <div v-for="(actor, index) in selectedAnime.voice_actors" :key="index" class="actor-item">
                <strong>{{ actor.character }}：</strong>{{ actor.actor }}
              </div>
            </div>
          </div>

          <!-- 製作人員 -->
          <div v-if="selectedAnime.staff && selectedAnime.staff.length > 0" class="detail-section">
            <h3><i class="pi pi-briefcase"></i> 製作人員</h3>
            <div class="staff-list">
              <div v-for="(member, index) in selectedAnime.staff" :key="index" class="staff-item">
                <strong>{{ member.position }}：</strong>{{ member.name }}
              </div>
            </div>
          </div>

          <!-- 播放平台 -->
          <div v-if="selectedAnime.platforms && selectedAnime.platforms.length > 0" class="detail-section">
            <h3><i class="pi pi-desktop"></i> 播放平台</h3>
            <div class="platforms-list">
              <div v-for="(platform, index) in selectedAnime.platforms" :key="index" class="platform-item">
                <Button
                  v-if="platform.notes"
                  :label="`${platform.platform} (${platform.region})`"
                  icon="pi pi-external-link"
                  @click="openLink(platform.notes)"
                  outlined
                  size="small"
                  severity="info"
                  class="platform-btn"
                />
                <Tag
                  v-else
                  :value="`${platform.platform} (${platform.region})`"
                  severity="info"
                  class="platform-tag"
                />
              </div>
            </div>
          </div>

          <!-- 預告片 -->
          <div v-if="selectedAnime.trailer_url" class="detail-section">
            <h3><i class="pi pi-play"></i> 預告片</h3>
            <Button
              :label="'觀看預告片'"
              icon="pi pi-external-link"
              @click="openLink(selectedAnime.trailer_url)"
              outlined
            />
          </div>

          <!-- 影片連結 -->
          <div v-if="selectedAnime.video_links && selectedAnime.video_links.length > 0" class="detail-section">
            <h3><i class="pi pi-video"></i> 相關影片</h3>
            <div class="video-links">
              <Button
                v-for="(link, index) in selectedAnime.video_links"
                :key="index"
                :label="`影片 ${index + 1}`"
                icon="pi pi-external-link"
                @click="openLink(link)"
                text
                size="small"
              />
            </div>
          </div>

          <!-- 外部連結 -->
          <div v-if="selectedAnime.external_links && selectedAnime.external_links.length > 0" class="detail-section">
            <h3><i class="pi pi-globe"></i> 外部連結</h3>
            <div class="external-links">
              <Button
                v-for="(link, index) in selectedAnime.external_links"
                :key="index"
                :label="link.name"
                icon="pi pi-external-link"
                @click="openLink(link.url)"
                outlined
                size="small"
                class="external-link-btn"
              />
            </div>
          </div>

          <!-- 來源連結 -->
          <div v-if="selectedAnime.source_url" class="detail-section">
            <Button
              label="查看原始資料"
              icon="pi pi-link"
              @click="openLink(selectedAnime.source_url)"
              text
              size="small"
            />
          </div>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { animeAPI } from '../services/api'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Button from 'primevue/button'
import Paginator from 'primevue/paginator'
import ProgressSpinner from 'primevue/progressspinner'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'

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

// 詳細資訊對話框狀態
const showDetailDialog = ref(false)
const selectedAnime = ref(null)

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

const showAnimeDetail = (anime) => {
  selectedAnime.value = anime
  showDetailDialog.value = true
}

const openLink = (url) => {
  if (url) {
    window.open(url, '_blank')
  }
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('zh-TW', { year: 'numeric', month: 'long', day: 'numeric' })
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

/* 每周更新時間標籤 */
.weekly-schedule-badge {
  position: absolute;
  top: 10px;
  right: 10px;
  background: rgba(74, 144, 226, 0.9);
  color: white;
  padding: 0.4rem 0.8rem;
  border-radius: 1rem;
  font-size: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.3rem;
  z-index: 1;
  backdrop-filter: blur(4px);
}

.weekly-schedule-badge i {
  font-size: 0.7rem;
}

/* 詳細資訊對話框 */
.anime-detail {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.detail-image {
  width: 100%;
  max-height: 400px;
  overflow: hidden;
  border-radius: 8px;
}

.detail-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.detail-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.detail-section {
  border-bottom: 1px solid var(--color-border, #333);
  padding-bottom: 1rem;
}

.detail-section:last-child {
  border-bottom: none;
}

.detail-section h3 {
  color: var(--color-text);
  font-size: 1.1rem;
  margin-bottom: 0.8rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.detail-section h3 i {
  color: var(--color-primary, #4a90e2);
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 0.8rem;
}

.detail-item {
  color: var(--color-text-secondary);
  font-size: 0.95rem;
}

.detail-item strong {
  color: var(--color-text);
  margin-right: 0.5rem;
}

.detail-section p {
  color: var(--color-text-secondary);
  line-height: 1.6;
  margin: 0;
}

.voice-actors-list,
.staff-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 0.6rem;
}

.actor-item,
.staff-item {
  color: var(--color-text-secondary);
  font-size: 0.9rem;
  padding: 0.4rem 0;
}

.actor-item strong,
.staff-item strong {
  color: var(--color-text);
}

.platforms-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.platform-item {
  display: inline-block;
}

.platform-tag {
  font-size: 0.85rem;
}

.platform-btn {
  font-size: 0.85rem;
}

.video-links {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.external-links {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
}

.external-link-btn {
  flex-shrink: 0;
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

  .detail-grid,
  .voice-actors-list,
  .staff-list {
    grid-template-columns: 1fr;
  }

  .weekly-schedule-badge {
    font-size: 0.65rem;
    padding: 0.3rem 0.6rem;
  }
}
</style>
