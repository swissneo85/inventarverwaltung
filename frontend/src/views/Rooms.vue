<template>
  <div class="rooms-page">
    <div class="page-header">
      <h1>Räume</h1>
      <button v-if="canEdit" class="btn btn-primary" @click="$router.push({ name: 'RoomCreate' })">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Neuer Raum
      </button>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Wird geladen...</p>
    </div>

    <template v-else-if="rooms.length > 0">
      <!-- View Toggle -->
      <div class="view-toggle">
        <button :class="['toggle-btn', { active: viewMode === 'list' }]" @click="viewMode = 'list'">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line>
            <line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line>
          </svg>
          Liste
        </button>
        <button :class="['toggle-btn', { active: viewMode === 'gallery' }]" @click="viewMode = 'gallery'">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect>
            <rect x="3" y="14" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect>
          </svg>
          Galerie
        </button>
        <button :class="['toggle-btn', { active: viewMode === 'table' }]" @click="viewMode = 'table'">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="3" y1="9" x2="21" y2="9"></line><line x1="3" y1="15" x2="21" y2="15"></line><line x1="9" y1="3" x2="9" y2="21"></line>
          </svg>
          Tabelle
        </button>
      </div>

      <!-- List View -->
      <div v-if="viewMode === 'list'" class="list-view">
        <div v-for="room in rooms" :key="room.id" class="list-card" :class="{ 'is-empty': isEmpty(room) }" @click="$router.push(`/rooms/${room.id}`)" style="cursor:pointer">
          <router-link :to="`/rooms/${room.id}`" class="list-thumb" @click.stop>
            <img v-if="room.image_url" :src="room.image_url" :alt="room.name" class="thumb-img">
            <span v-else class="thumb-id">R{{ room.id }}</span>
          </router-link>
          <span class="list-id">R{{ room.id }}</span>
          <div class="list-info">
            <router-link :to="`/rooms/${room.id}`" class="list-name" @click.stop>{{ room.name }}</router-link>
            <div class="list-sub">{{ room.description || '' }}</div>
          </div>
          <div class="list-footer" @click.stop>
            <div class="list-stats">
              <span v-if="isEmpty(room)" class="stat-chip stat-chip--empty">leer</span>
              <template v-else>
                <span class="stat-chip">{{ room.items_count || 0 }} Items</span>
                <span class="stat-chip">{{ room.boxes_count || 0 }} Boxen</span>
              </template>
            </div>
            <div class="list-actions">
              <router-link :to="`/rooms/${room.id}`" class="row-btn" title="Ansehen">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>
                </svg>
              </router-link>
              <router-link v-if="canEdit" :to="`/rooms/${room.id}/edit`" class="row-btn" title="Bearbeiten">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
              </router-link>
              <button v-if="canDelete" class="row-btn row-btn--danger" title="Löschen" @click="confirmDelete(room)">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"></polyline>
                  <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Gallery View -->
      <div v-else-if="viewMode === 'gallery'" class="gallery-view">
        <router-link v-for="room in rooms" :key="room.id" :to="`/rooms/${room.id}`" class="gallery-card" :class="{ 'is-empty': isEmpty(room) }">
          <div class="gallery-img-wrap" :style="room.cover_image ? { background: colorMap[room.cover_image.url] || '#f3f4f6' } : {}">
            <img v-if="room.cover_image" :src="room.cover_image.url" :alt="room.name" class="gallery-img">
            <div v-else class="gallery-placeholder room-placeholder">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
              </svg>
            </div>
          </div>
          <div class="gallery-info">
            <div class="gallery-id">R{{ room.id }}</div>
            <div class="gallery-name">{{ room.name }}</div>
            <div class="gallery-stats">
              <span v-if="isEmpty(room)">leer</span>
              <template v-else>
                <span>{{ room.items_count || 0 }} Items</span>
                <span>{{ room.boxes_count || 0 }} Boxen</span>
              </template>
            </div>
          </div>
        </router-link>
      </div>

      <!-- Table View -->
      <div v-else class="table-card">
        <div class="table-container">
          <table class="table">
            <thead>
              <tr>
                <th style="width:56px"></th>
                <th>Raum</th>
                <th>Beschreibung</th>
                <th>Items</th>
                <th>Boxen</th>
                <th style="width:110px"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="room in rooms" :key="room.id" class="table-row" :class="{ 'is-empty': isEmpty(room) }">
                <td class="td-thumb">
                  <div class="row-thumb">
                    <img v-if="room.cover_image" :src="room.cover_image.url" :alt="room.name" class="row-thumb-img">
                    <span v-else class="row-thumb-id">R{{ room.id }}</span>
                  </div>
                </td>
                <td class="td-main">
                  <router-link :to="`/rooms/${room.id}`" class="row-name">{{ room.name }}</router-link>
                  <span class="row-id">R{{ room.id }}</span>
                </td>
                <td class="muted-text">{{ room.description || '—' }}</td>
                <template v-if="isEmpty(room)">
                  <td colspan="2"><span class="chip chip--empty">leer</span></td>
                </template>
                <template v-else>
                  <td><span class="chip">{{ room.items_count || 0 }}</span></td>
                  <td><span class="chip">{{ room.boxes_count || 0 }}</span></td>
                </template>
                <td class="td-actions">
                  <div class="actions-wrap">
                    <router-link :to="`/rooms/${room.id}`" class="row-btn" title="Ansehen">
                      <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>
                      </svg>
                    </router-link>
                    <router-link v-if="canEdit" :to="`/rooms/${room.id}/edit`" class="row-btn" title="Bearbeiten">
                      <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                      </svg>
                    </router-link>
                    <button v-if="canDelete" class="row-btn row-btn--danger" title="Löschen" @click="confirmDelete(room)">
                      <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <div v-else class="empty-state">
      <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
      </svg>
      <h3>Keine Räume angelegt</h3>
      <p>Erstellen Sie Ihren ersten Raum</p>
      <button v-if="canEdit" class="btn btn-primary" @click="$router.push({ name: 'RoomCreate' })">Raum erstellen</button>
    </div>

    <!-- Delete confirmation -->
    <div v-if="deleteTarget" class="confirm-backdrop" @click.self="deleteTarget = null">
      <div class="confirm-dialog">
        <p>Raum <strong>{{ deleteTarget.name }}</strong> wirklich löschen?</p>
        <div class="confirm-actions">
          <button class="btn btn-secondary" @click="deleteTarget = null">Abbrechen</button>
          <button class="btn-danger" :disabled="deleting" @click="deleteRoom">
            {{ deleting ? '…' : 'Löschen' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import { extractImageColor } from '@/composables/useImageColor'

const toast = useToast()
const router = useRouter()
const authStore = useAuthStore()
const canEdit = computed(() => authStore.isEditor)
const canDelete = computed(() => authStore.isAdmin)
const rooms = ref([])
const loading = ref(true)
const colorMap = ref({})

watch(rooms, (newRooms) => {
  for (const room of newRooms) {
    const url = room.cover_image?.url
    if (url && !colorMap.value[url]) {
      extractImageColor(url).then(color => { colorMap.value[url] = color })
    }
  }
}, { immediate: true })
const deleteTarget = ref(null)
const deleting = ref(false)

const viewMode = ref(localStorage.getItem('rooms-view-mode') || 'list')
watch(viewMode, val => localStorage.setItem('rooms-view-mode', val))

onMounted(fetchRooms)

async function fetchRooms() {
  loading.value = true
  try {
    const res = await api.get('/rooms')
    rooms.value = res.data.data
  } catch {
    toast.error('Fehler beim Laden der Räume')
  } finally {
    loading.value = false
  }
}

function isEmpty(room) {
  return !room.items_count && !room.boxes_count
}

function confirmDelete(room) {
  deleteTarget.value = room
}

async function deleteRoom() {
  if (!deleteTarget.value || deleting.value) return
  deleting.value = true
  try {
    await api.delete(`/rooms/${deleteTarget.value.id}`)
    toast.success('Raum gelöscht')
    rooms.value = rooms.value.filter(r => r.id !== deleteTarget.value.id)
    deleteTarget.value = null
  } catch {
    // API interceptor shows error toast
  } finally {
    deleting.value = false
  }
}
</script>

<style lang="scss" scoped>
.rooms-page { max-width: 1200px; margin: 0 auto; }

.page-header {
  display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;
  h1 { font-size: 1.5rem; font-weight: 600; margin: 0; }
}

.view-toggle { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
.toggle-btn {
  display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem;
  border: 1px solid #e5e7eb; background: white; border-radius: 8px;
  font-size: 0.875rem; color: #6b7280; cursor: pointer; transition: all 0.2s;
  &.active { background: #3b82f6; border-color: #3b82f6; color: white; }
  &:hover:not(.active) { background: #f3f4f6; }
}

/* List */
.list-view { display: flex; flex-direction: column; gap: 0.75rem; }
.list-card {
  display: flex; align-items: center; gap: 1rem; padding: 0.75rem 1rem;
  background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);
  transition: box-shadow 0.2s;
  &:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
}
.list-thumb {
  width: 48px; height: 48px; flex-shrink: 0; border-radius: 8px;
  background: #fef9ec; display: flex; align-items: center; justify-content: center;
  overflow: hidden; text-decoration: none;
}
.thumb-img { width: 100%; height: 100%; object-fit: cover; display: block; }
.thumb-id { font-size: 0.7rem; font-weight: 700; color: #d97706; }
.list-id { font-size: 0.75rem; font-weight: 600; color: #d97706; flex-shrink: 0; min-width: 32px; }
.list-info { flex: 1; min-width: 0; }
.list-name {
  font-weight: 600; font-size: 0.9rem; color: #111827; text-decoration: none; display: block;
  &:hover { color: #3b82f6; }
}
.list-sub { font-size: 0.8rem; color: #9ca3af; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.list-footer { display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }
.list-stats { display: flex; gap: 0.5rem; }
.stat-chip { font-size: 0.75rem; padding: 0.2rem 0.6rem; background: #f3f4f6; color: #6b7280; border-radius: 99px; white-space: nowrap; }
.list-actions { display: flex; gap: 0.25rem; }

/* Empty state (0 Items, 0 Boxen) */
.is-empty {
  .list-thumb, .gallery-placeholder, .row-thumb {
    background: transparent;
    border: 1px dashed #d1d5db;
  }
  .thumb-id, .row-thumb-id, .gallery-placeholder svg {
    color: var(--text-muted);
  }
}
.stat-chip--empty, .chip--empty {
  color: var(--text-muted);
  font-style: italic;
}

/* Gallery */
.gallery-view { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1rem; }
.gallery-card {
  background: white; border-radius: 12px; overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08); text-decoration: none; color: inherit;
  transition: box-shadow 0.2s, transform 0.2s; display: flex; flex-direction: column;
  &:hover { box-shadow: 0 6px 16px rgba(0,0,0,0.12); transform: translateY(-2px); }
}
.gallery-img-wrap { width: 100%; aspect-ratio: 4 / 3; overflow: hidden; background: #f3f4f6; }
.gallery-img { width: 100%; height: 100%; object-fit: contain; object-position: center; display: block; }
.gallery-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; }
.room-placeholder { background: #fef9ec; color: #d97706; }
.gallery-info { padding: 0.75rem; }
.gallery-id { font-size: 0.7rem; font-weight: 600; color: #d97706; }
.gallery-name { font-size: 0.875rem; font-weight: 600; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin: 0.1rem 0; }
.gallery-stats { display: flex; gap: 0.5rem; font-size: 0.72rem; color: #9ca3af; margin-top: 0.25rem; }

/* Table */
.table-card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden; }
.table-container { overflow-x: auto; }
.table {
  width: 100%; border-collapse: collapse; font-size: 0.875rem;
  thead tr { background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
  th { padding: 0.75rem 1rem; text-align: left; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #9ca3af; }
  td { padding: 0.75rem 1rem; vertical-align: middle; border-bottom: 1px solid #f3f4f6; }
  .table-row { transition: background 0.12s; &:last-child td { border-bottom: none; } &:hover { background: #f9fafb; } }
}
.td-thumb { padding: 0.5rem 0.5rem 0.5rem 1rem; }
.row-thumb {
  width: 40px; height: 40px; border-radius: 8px; overflow: hidden;
  background: #fef9ec; display: flex; align-items: center; justify-content: center;
}
.row-thumb-img { width: 100%; height: 100%; object-fit: cover; display: block; }
.row-thumb-id { font-size: 0.65rem; font-weight: 700; color: #d97706; }
.td-main { display: flex; flex-direction: column; gap: 0.1rem; min-width: 140px; }
.row-name { font-weight: 600; color: #111827; text-decoration: none; &:hover { color: #3b82f6; } }
.row-id { font-size: 0.7rem; color: #9ca3af; }
.chip { display: inline-block; padding: 0.2rem 0.6rem; background: #f3f4f6; color: #374151; border-radius: 99px; font-size: 0.75rem; font-weight: 500; }
.muted-text { color: #9ca3af; font-size: 0.85rem; }
.td-actions { white-space: nowrap; vertical-align: middle; }
.actions-wrap { display: flex; gap: 0.25rem; align-items: center; }
.row-btn {
  display: flex; align-items: center; justify-content: center; width: 30px; height: 30px;
  border-radius: 6px; color: #9ca3af; text-decoration: none; background: none; border: none;
  cursor: pointer; transition: all 0.15s;
  &:hover { background: #eff6ff; color: #3b82f6; }
  &.row-btn--danger:hover { background: #fee2e2; color: #dc2626; }
}

/* States */
.loading-state, .empty-state {
  display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem; text-align: center;
  svg { color: #d1d5db; margin-bottom: 1rem; }
  h3 { font-size: 1.125rem; font-weight: 600; margin: 0 0 0.5rem; }
  p { color: #6b7280; margin: 0 0 1rem; }
}
.spinner {
  width: 32px; height: 32px; border: 3px solid #e5e7eb; border-top-color: #3b82f6;
  border-radius: 50%; animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Confirm */
.confirm-backdrop {
  position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 500;
  display: flex; align-items: center; justify-content: center;
}
.confirm-dialog {
  background: white; border-radius: 12px; padding: 1.5rem; margin: 1rem;
  min-width: 280px; text-align: center; box-shadow: 0 8px 32px rgba(0,0,0,0.2);
  p { margin: 0 0 1.25rem; font-size: 1rem; color: #111827; }
}
.confirm-actions { display: flex; gap: 0.75rem; justify-content: center; }
.btn-danger {
  padding: 0.5rem 1rem; background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;
  border-radius: 8px; font-size: 0.875rem; font-weight: 500; cursor: pointer;
  &:hover:not(:disabled) { background: #fecaca; }
  &:disabled { opacity: 0.5; cursor: not-allowed; }
}

@media (max-width: 640px) {
  .list-card {
    display: grid;
    grid-template-columns: 52px 1fr;
    grid-template-rows: auto auto;
    column-gap: 0.75rem;
    row-gap: 0.375rem;
    align-items: start;
  }
  .list-thumb { grid-column: 1; grid-row: 1 / span 2; align-self: center; }
  .list-id { display: none; }
  .list-info { grid-column: 2; grid-row: 1; }
  .list-sub { white-space: normal; word-break: break-word; }
  .list-footer { grid-column: 2; grid-row: 2; justify-content: flex-end; }
}
@media (max-width: 767px) {
  .gallery-view { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); }
}
</style>
