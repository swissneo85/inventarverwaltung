<template>
  <div class="page">
    <div class="page-header">
      <div class="header-row">
        <button class="btn btn-secondary btn-back" @click="$router.push({ name: 'Rooms' })">← Zurück</button>
        <div class="header-actions" v-if="room && canEdit">
          <router-link :to="`/rooms/${room.id}/edit`" class="btn btn-primary">Bearbeiten</router-link>
        </div>
      </div>
      <h1 v-if="room">{{ room.name }}</h1>
    </div>

    <div v-if="loading" class="loading">Wird geladen...</div>

    <template v-else-if="room">
      <!-- Images first -->
      <div class="card detail-card" v-if="imageCount !== 0">
        <ImageGallery type="rooms" :model-id="room.id" :readonly="true" @loaded="n => imageCount = n" />
      </div>

      <!-- Details -->
      <div class="card detail-card">
        <h2>Details</h2>
        <div class="detail-row"><span>ID</span><span class="id-badge">R{{ room.id }}</span></div>
        <div v-if="room.description" class="detail-row detail-row--block">
          <span>Beschreibung</span><p>{{ room.description }}</p>
        </div>
        <div class="detail-row">
          <span>Boxen</span><span>{{ room.boxes_count ?? boxes.length }}</span>
        </div>
        <div class="detail-row">
          <span>Items (direkt)</span><span>{{ room.items_count ?? items.length }}</span>
        </div>
      </div>

      <!-- Boxes -->
      <div class="card detail-card" v-if="boxes.length > 0">
        <h2>Boxen ({{ boxes.length }})</h2>
        <div class="sub-list">
          <router-link v-for="box in boxes" :key="box.id" :to="`/boxes/${box.id}`" class="sub-item">
            <span class="sub-id">B{{ box.id }}</span>
            <span class="sub-name">{{ box.name }}</span>
            <span class="sub-meta">{{ box.items_count || 0 }} Items</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="sub-chevron">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </router-link>
        </div>
      </div>

      <!-- Items -->
      <div class="card detail-card" v-if="items.length > 0">
        <h2>Items ({{ items.length }})</h2>
        <div class="sub-list">
          <router-link v-for="item in items" :key="item.id" :to="`/items/${item.id}`" class="sub-item">
            <span class="sub-id">{{ item.display_id || 'I' + item.id }}</span>
            <span class="sub-name">{{ item.name }}</span>
            <span v-if="item.category" class="sub-meta">{{ item.category.name }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="sub-chevron">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </router-link>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
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

const room = ref(null)
const boxes = ref([])
const items = ref([])
const loading = ref(true)
const imageCount = ref(null)

onMounted(async () => {
  const id = route.params.id
  try {
    const [roomRes, boxesRes, itemsRes] = await Promise.all([
      api.get(`/rooms/${id}`),
      api.get(`/rooms/${id}/boxes`),
      api.get(`/rooms/${id}/items`)
    ])
    room.value = roomRes.data.data
    boxes.value = boxesRes.data.data?.data || boxesRes.data.data
    items.value = itemsRes.data.data?.data || itemsRes.data.data
  } catch {
    router.push({ name: 'Rooms' })
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

.detail-row {
  display: flex; justify-content: space-between; align-items: baseline;
  padding: 0.5rem 0; border-bottom: 1px solid #f3f4f6; gap: 0.5rem;
  &:last-child { border-bottom: none; }
  & > span:first-child { color: #6b7280; font-size: 0.875rem; flex-shrink: 0; }
  & > span:last-child { font-size: 0.875rem; text-align: right; }
}
.detail-row--block { flex-direction: column; gap: 0.25rem; }
.detail-row--block p { margin: 0; font-size: 0.875rem; color: #374151; white-space: pre-wrap; }

.id-badge { font-size: 0.8rem; font-weight: 600; color: #d97706; background: #fef3c7; padding: 0.1rem 0.5rem; border-radius: 99px; }

.sub-list { display: flex; flex-direction: column; }
.sub-item {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.625rem 0; border-bottom: 1px solid #f3f4f6; text-decoration: none; color: inherit;
  &:last-child { border-bottom: none; }
  &:hover { background: #f9fafb; margin: 0 -1.5rem; padding: 0.625rem 1.5rem; }
}
.sub-id { font-size: 0.75rem; font-weight: 600; color: #3b82f6; flex-shrink: 0; min-width: 36px; }
.sub-name { flex: 1; font-size: 0.875rem; font-weight: 500; color: #111827; }
.sub-meta { font-size: 0.75rem; color: #9ca3af; flex-shrink: 0; }
.sub-chevron { color: #d1d5db; flex-shrink: 0; }

.loading { padding: 2rem; text-align: center; color: #6b7280; }

@media (max-width: 767px) {
  .detail-card { padding: 1rem; }
  .sub-item:hover { margin: 0 -1rem; padding: 0.625rem 1rem; }
}
</style>
