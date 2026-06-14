<template>
  <div class="page">
    <div class="page-header">
      <div class="header-row">
        <button class="btn btn-secondary btn-back" @click="$router.push({ name: 'Boxes' })">← Zurück</button>
        <div class="header-actions" v-if="box && canEdit">
          <router-link :to="`/boxes/${box.id}/edit`" class="btn btn-primary">Bearbeiten</router-link>
        </div>
      </div>
      <h1 v-if="box">{{ box.name }}</h1>
    </div>

    <div v-if="loading" class="loading">Wird geladen...</div>

    <template v-else-if="box">
      <!-- Images first -->
      <div class="card detail-card" v-if="imageCount !== 0">
        <ImageGallery type="boxes" :model-id="box.id" :readonly="true" @loaded="n => imageCount = n" />
      </div>

      <!-- Details -->
      <div class="card detail-card">
        <h2>Details</h2>
        <div class="detail-row"><span>ID</span><span class="id-badge">B{{ box.id }}</span></div>
        <div class="detail-row"><span>Raum</span><span>{{ box.room?.name || 'Inbox' }}</span></div>
        <div v-if="box.box_type" class="detail-row"><span>Typ</span><span>{{ box.box_type }}</span></div>
        <div v-if="box.description" class="detail-row detail-row--block">
          <span>Beschreibung</span><p>{{ box.description }}</p>
        </div>
        <div class="detail-row">
          <span>Items</span><span>{{ box.items_count ?? items.length }}</span>
        </div>
      </div>

      <!-- Items -->
      <div class="card detail-card" v-if="items.length > 0">
        <div class="section-header">
          <h2>Items ({{ items.length }})</h2>
          <div class="view-toggle">
            <button :class="['toggle-btn', { active: itemsViewMode === 'list' }]" @click="itemsViewMode = 'list'" title="Liste">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line>
                <line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line>
              </svg>
            </button>
            <button :class="['toggle-btn', { active: itemsViewMode === 'gallery' }]" @click="itemsViewMode = 'gallery'" title="Galerie">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect>
              </svg>
            </button>
            <button :class="['toggle-btn', { active: itemsViewMode === 'table' }]" @click="itemsViewMode = 'table'" title="Tabelle">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="3" y1="9" x2="21" y2="9"></line><line x1="3" y1="15" x2="21" y2="15"></line><line x1="9" y1="3" x2="9" y2="21"></line>
              </svg>
            </button>
          </div>
        </div>

        <!-- List View -->
        <div v-if="itemsViewMode === 'list'" class="sub-list">
          <router-link v-for="item in items" :key="item.id" :to="`/items/${item.id}`" class="sub-item">
            <div class="sub-thumb">
              <img v-if="item.image_url" :src="item.image_url" :alt="item.name" class="sub-thumb-img">
              <span v-else class="sub-thumb-ph">{{ item.display_id || 'I' + item.id }}</span>
            </div>
            <span class="sub-id">{{ item.display_id || 'I' + item.id }}</span>
            <span class="sub-name">{{ item.name }}</span>
            <span v-if="item.category" class="sub-meta">{{ item.category.name }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="sub-chevron">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </router-link>
        </div>

        <!-- Gallery View -->
        <div v-else-if="itemsViewMode === 'gallery'" class="detail-gallery">
          <router-link v-for="item in items" :key="item.id" :to="`/items/${item.id}`" class="gallery-card">
            <div class="gallery-img-wrap">
              <img v-if="item.cover_image" :src="item.cover_image.url" :alt="item.name" class="gallery-img">
              <div v-else class="gallery-placeholder">
                <span>{{ item.display_id || 'I' + item.id }}</span>
              </div>
            </div>
            <div class="gallery-info">
              <div class="gallery-id">{{ item.display_id || 'I' + item.id }}</div>
              <div class="gallery-name">{{ item.name }}</div>
              <div v-if="item.category" class="gallery-cat">{{ item.category.name }}</div>
            </div>
          </router-link>
        </div>

        <!-- Table View -->
        <div v-else class="detail-table-wrap">
          <table class="detail-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Kategorie</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in items" :key="item.id" class="detail-table-row">
                <td><router-link :to="`/items/${item.id}`" class="td-id">{{ item.display_id || 'I' + item.id }}</router-link></td>
                <td><router-link :to="`/items/${item.id}`" class="td-name">{{ item.name }}</router-link></td>
                <td class="td-meta">{{ item.category?.name || '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { useToast } from 'vue-toastification'
import ImageGallery from '@/components/ImageGallery.vue'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const canEdit = computed(() => authStore.isEditor)

const box = ref(null)
const items = ref([])
const loading = ref(true)
const imageCount = ref(null)

const itemsViewMode = ref(localStorage.getItem('box-detail-items-view') || 'list')
watch(itemsViewMode, val => localStorage.setItem('box-detail-items-view', val))

onMounted(async () => {
  const id = route.params.id
  try {
    const [boxRes, itemsRes] = await Promise.all([
      api.get(`/boxes/${id}`),
      api.get(`/boxes/${id}/items`)
    ])
    box.value = boxRes.data.data
    items.value = itemsRes.data.data?.data || itemsRes.data.data
  } catch {
    router.push({ name: 'Boxes' })
  } finally {
    loading.value = false
  }
})

</script>

<style scoped>
.page { max-width: 800px; margin: 0 auto; }

.page-header {
  margin-bottom: 1.5rem;
}
.header-row {
  display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 0.5rem;
}
.page-header h1 { font-size: 1.5rem; font-weight: 600; margin: 0; }
.btn-back { flex-shrink: 0; }
.header-actions { flex-shrink: 0; }

.detail-card { padding: 1.5rem; margin-bottom: 1rem; }
.detail-card h2 { font-size: 1rem; font-weight: 600; margin: 0 0 1rem; color: #111827; }

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}
.section-header h2 { margin: 0; }

.view-toggle {
  display: flex;
  gap: 0.25rem;
}

.toggle-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: 1px solid #e5e7eb;
  background: white;
  border-radius: 6px;
  color: #9ca3af;
  cursor: pointer;
  transition: all 0.15s;
}
.toggle-btn.active {
  background: #3b82f6;
  border-color: #3b82f6;
  color: white;
}
.toggle-btn:hover:not(.active) {
  background: #f3f4f6;
  color: #374151;
}

.detail-row {
  display: flex; justify-content: space-between; align-items: baseline;
  padding: 0.5rem 0; border-bottom: 1px solid #f3f4f6; gap: 0.5rem;
  &:last-child { border-bottom: none; }
  & > span:first-child { color: #6b7280; font-size: 0.875rem; flex-shrink: 0; }
  & > span:last-child { font-size: 0.875rem; text-align: right; }
}
.detail-row--block { flex-direction: column; gap: 0.25rem; }
.detail-row--block p { margin: 0; font-size: 0.875rem; color: #374151; white-space: pre-wrap; }

.id-badge { font-size: 0.8rem; font-weight: 600; color: #7c3aed; background: #f5f3ff; padding: 0.1rem 0.5rem; border-radius: 99px; }

/* List View */
.sub-list { display: flex; flex-direction: column; }
.sub-item {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.5rem 0; border-bottom: 1px solid #f3f4f6; text-decoration: none; color: inherit;
  &:last-child { border-bottom: none; }
  &:hover { background: #f9fafb; margin: 0 -1.5rem; padding: 0.5rem 1.5rem; }
}
.sub-thumb {
  width: 48px; height: 48px; flex-shrink: 0; border-radius: 8px;
  overflow: hidden; background: #dbeafe; display: flex; align-items: center; justify-content: center;
}
.sub-thumb-img { width: 100%; height: 100%; object-fit: cover; display: block; }
.sub-thumb-ph { font-size: 0.65rem; font-weight: 700; color: #3b82f6; }
.sub-id { font-size: 0.75rem; font-weight: 600; color: #3b82f6; flex-shrink: 0; min-width: 36px; }
.sub-name { flex: 1; font-size: 0.875rem; font-weight: 500; color: #111827; }
.sub-meta { font-size: 0.75rem; color: #9ca3af; flex-shrink: 0; }
.sub-chevron { color: #d1d5db; flex-shrink: 0; }

/* Gallery View */
.detail-gallery {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 0.75rem;
}

.gallery-card {
  background: #f9fafb;
  border-radius: 10px;
  overflow: hidden;
  text-decoration: none;
  color: inherit;
  transition: box-shadow 0.2s, transform 0.2s;
  display: flex;
  flex-direction: column;
  border: 1px solid #f3f4f6;
  &:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); transform: translateY(-1px); }
}

.gallery-img-wrap {
  width: 100%;
  aspect-ratio: 1 / 1;
  overflow: hidden;
  background: #f3f4f6;
}

.gallery-img { width: 100%; height: 100%; object-fit: cover; display: block; }

.gallery-placeholder {
  width: 100%; height: 100%;
  display: flex; align-items: center; justify-content: center;
  background: #dbeafe;
  span { font-size: 0.7rem; font-weight: 700; color: #3b82f6; }
}

.gallery-info { padding: 0.5rem; display: flex; flex-direction: column; gap: 0.15rem; }
.gallery-id { font-size: 0.65rem; font-weight: 600; color: #3b82f6; }
.gallery-name { font-size: 0.8rem; font-weight: 600; color: #1f2937; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.gallery-cat { font-size: 0.7rem; color: #6b7280; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* Table View */
.detail-table-wrap {
  overflow-x: auto;
  margin: 0 -1.5rem;
}

.detail-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.detail-table thead tr {
  border-bottom: 1px solid #e5e7eb;
}

.detail-table th {
  padding: 0.5rem 1.5rem;
  text-align: left;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #9ca3af;
  white-space: nowrap;
}

.detail-table td {
  padding: 0.625rem 1.5rem;
  border-bottom: 1px solid #f3f4f6;
  vertical-align: middle;
}

.detail-table-row:last-child td { border-bottom: none; }
.detail-table-row:hover { background: #f9fafb; }

.td-id { font-size: 0.75rem; font-weight: 600; color: #3b82f6; text-decoration: none; }
.td-name { font-weight: 500; color: #111827; text-decoration: none; &:hover { color: #3b82f6; } }
.td-meta { color: #6b7280; }

.loading { padding: 2rem; text-align: center; color: #6b7280; }

@media (max-width: 767px) {
  .detail-card { padding: 1rem; }
  .sub-item:hover { margin: 0 -1rem; padding: 0.625rem 1rem; }
  .detail-table-wrap { margin: 0 -1rem; }
  .detail-table th, .detail-table td { padding: 0.5rem 1rem; }
  .detail-gallery { grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); }
}
</style>
