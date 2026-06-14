<template>
  <div class="inbox-page">
    <div class="page-header">
      <h1>Inbox</h1>
      <p class="page-subtitle">Noch nicht zugeordnete Elemente</p>
    </div>

    <div class="inbox-tabs">
      <button
        :class="['tab', { active: activeTab === 'items' }]"
        @click="activeTab = 'items'"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
        </svg>
        Gegenstände ({{ items.length }})
      </button>
      <button
        :class="['tab', { active: activeTab === 'boxes' }]"
        @click="activeTab = 'boxes'"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
        </svg>
        Boxen ({{ inboxBoxes.length }})
      </button>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Wird geladen...</p>
    </div>

    <!-- Items Tab -->
    <div v-else-if="activeTab === 'items'">
      <div v-if="items.length === 0" class="empty-card">
        <p>Keine Gegenstände in der Inbox</p>
      </div>
      <div v-else class="items-list">
        <div v-for="item in items" :key="item.id" class="inbox-item card">
          <div class="item-info" @click="$router.push({ name: 'ItemDetail', params: { id: item.id } })" style="cursor:pointer">
            <div class="item-thumb">
              <img v-if="item.image_url" :src="item.image_url" :alt="item.name" class="thumb-img">
              <span v-else class="thumb-ph">{{ item.display_id || 'I' + item.id }}</span>
            </div>
            <div class="item-details">
              <h3>{{ item.name }}</h3>
              <p v-if="item.category">{{ item.category.name }}</p>
            </div>
          </div>
          <div v-if="canEdit" class="item-actions">
            <div class="assign-selects">
              <SearchableSelect
                :model-value="item._targetRoom"
                @update:model-value="val => { item._targetRoom = val; item._targetBox = '' }"
                :options="roomOptions"
                placeholder="Raum wählen…"
                create-route="RoomCreate"
                create-label="Neuen Raum anlegen"
              />
              <SearchableSelect
                :model-value="item._targetBox"
                @update:model-value="val => { item._targetBox = val; if (val) item._targetRoom = '' }"
                :options="filteredBoxOptions(item._targetRoom)"
                placeholder="Box wählen…"
                create-route="BoxCreate"
                create-label="Neue Box anlegen"
              />
            </div>
            <button
              class="btn btn-primary btn-sm"
              :disabled="!item._targetRoom && !item._targetBox"
              @click="assignItem(item)"
            >
              Zuweisen
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Boxes Tab -->
    <div v-else-if="activeTab === 'boxes'">
      <div v-if="inboxBoxes.length === 0" class="empty-card">
        <p>Keine Boxen in der Inbox</p>
      </div>
      <div v-else class="items-list">
        <div v-for="box in inboxBoxes" :key="box.id" class="inbox-item card">
          <div class="item-info">
            <div class="item-thumb box-thumb">
              <img v-if="box.image_url" :src="box.image_url" :alt="box.name" class="thumb-img">
              <span v-else class="thumb-ph">B{{ box.id }}</span>
            </div>
            <div class="item-details">
              <h3>{{ box.name }}</h3>
              <p>{{ box.items_count || 0 }} Items</p>
            </div>
          </div>
          <div v-if="canEdit" class="item-actions">
            <div class="assign-selects">
              <SearchableSelect
                :model-value="box._targetRoom"
                @update:model-value="val => box._targetRoom = val"
                :options="roomOptions"
                placeholder="Raum wählen…"
                create-route="RoomCreate"
                create-label="Neuen Raum anlegen"
              />
            </div>
            <button
              class="btn btn-primary btn-sm"
              :disabled="!box._targetRoom"
              @click="assignBox(box)"
            >
              Zuweisen
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { useToast } from 'vue-toastification'
import SearchableSelect from '@/components/SearchableSelect.vue'
import { useAuthStore } from '@/stores/auth'

const toast = useToast()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const canEdit = computed(() => authStore.isEditor)

const activeTab = ref('items')
const items = ref([])
const inboxBoxes = ref([])
const rooms = ref([])
const allBoxes = ref([])
const loading = ref(true)

const roomOptions = computed(() =>
  rooms.value.map(r => ({ value: r.id, label: r.name }))
)

function filteredBoxOptions(roomId) {
  const src = roomId
    ? allBoxes.value.filter(b => b.room_id === roomId)
    : allBoxes.value
  return src.map(b => ({ value: b.id, label: b.name }))
}

onMounted(async () => {
  await Promise.all([fetchInbox(), fetchRooms(), fetchBoxes()])
  loading.value = false

  // Clear return-flow query params without reloading
  if (route.query.newRoomId || route.query.newBoxId) {
    router.replace({ query: {} })
  }
})

async function fetchInbox() {
  try {
    const res = await api.get('/dashboard/inbox')
    items.value = res.data.data.items.map(i => ({ ...i, _targetRoom: '', _targetBox: '' }))
    inboxBoxes.value = res.data.data.boxes.map(b => ({ ...b, _targetRoom: '' }))
  } catch {
    toast.error('Fehler beim Laden der Inbox')
  }
}

async function fetchRooms() {
  try {
    const res = await api.get('/rooms')
    rooms.value = res.data.data
  } catch {
    // silent
  }
}

async function fetchBoxes() {
  try {
    const res = await api.get('/boxes', { params: { per_page: 500 } })
    allBoxes.value = res.data.data?.data ?? res.data.data
  } catch {
    // silent
  }
}

async function assignItem(item) {
  try {
    if (item._targetBox) {
      await api.post(`/items/${item.id}/assign-box`, { box_id: item._targetBox })
    } else {
      await api.post(`/items/${item.id}/assign-room`, { room_id: item._targetRoom })
    }
    toast.success('Gegenstand zugewiesen')
    items.value = items.value.filter(i => i.id !== item.id)
  } catch {
    toast.error('Fehler beim Zuweisen')
  }
}

async function assignBox(box) {
  try {
    await api.post(`/boxes/${box.id}/assign-room`, { room_id: box._targetRoom })
    toast.success('Box zugewiesen')
    inboxBoxes.value = inboxBoxes.value.filter(b => b.id !== box.id)
  } catch {
    toast.error('Fehler beim Zuweisen')
  }
}
</script>

<style lang="scss" scoped>
.inbox-page {
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 1.5rem;

  h1 { font-size: 1.5rem; font-weight: 600; margin: 0 0 0.25rem; }
  .page-subtitle { color: #6b7280; margin: 0; }
}

.inbox-tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
}

.tab {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border: 1px solid #e5e7eb;
  background: white;
  border-radius: 8px;
  font-size: 0.875rem;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.2s;

  &.active { background: #3b82f6; border-color: #3b82f6; color: white; }
  &:hover:not(.active) { background: #f3f4f6; }
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-card {
  padding: 2rem;
  text-align: center;
  color: #6b7280;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.items-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.inbox-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1.25rem;
  flex-wrap: wrap;
}

.item-info {
  display: flex;
  align-items: center;
  gap: 1rem;
  min-width: 0;
  flex: 1;
}

.item-thumb {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 48px;
  height: 48px;
  background: #dbeafe;
  border-radius: 8px;
  overflow: hidden;

  &.box-thumb { background: #dcfce7; }
}

.thumb-img { width: 100%; height: 100%; object-fit: cover; display: block; }
.thumb-ph { font-size: 0.7rem; font-weight: 700; color: #3b82f6; }
.box-thumb .thumb-ph { color: #166534; }

.item-details {
  min-width: 0;
  h3 { font-size: 1rem; font-weight: 600; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  p { font-size: 0.875rem; color: #6b7280; margin: 0; }
}

.item-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-shrink: 0;
}

.assign-selects {
  display: flex;
  gap: 0.5rem;

  > * { min-width: 160px; max-width: 200px; }
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  white-space: nowrap;
}

@media (max-width: 768px) {
  .inbox-item {
    flex-direction: column;
    align-items: stretch;
  }

  .item-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .assign-selects {
    flex-direction: column;
    > * { min-width: 0; max-width: none; width: 100%; }
  }

  .btn-sm { width: 100%; justify-content: center; }
}
</style>
