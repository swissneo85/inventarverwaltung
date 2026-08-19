<template>
  <div class="boxes-page">
    <div class="page-header">
      <h1>Boxen</h1>
      <div class="header-actions">
        <router-link to="/inbox" class="btn btn-secondary">Inbox</router-link>
        <router-link v-if="canEdit" to="/boxes/new" class="btn btn-primary">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Neue Box
        </router-link>
      </div>
    </div>

    <!-- Search -->
    <div class="filters-card card">
      <div class="filters-row">
        <div class="search-field">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input v-model="search" type="text" placeholder="Boxen suchen..." @input="handleSearch">
        </div>
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Wird geladen...</p>
    </div>

    <template v-else-if="boxes.length > 0">
      <!-- View Toggle -->
      <div class="view-toggle">
        <button :class="['toggle-btn', { active: viewMode === 'list' }]" @click="viewMode = 'list'" title="Liste">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line>
            <line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line>
          </svg>
          Liste
        </button>
        <button :class="['toggle-btn', { active: viewMode === 'gallery' }]" @click="viewMode = 'gallery'" title="Galerie">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect>
            <rect x="3" y="14" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect>
          </svg>
          Galerie
        </button>
        <button :class="['toggle-btn', { active: viewMode === 'table' }]" @click="viewMode = 'table'" title="Tabelle">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="3" y1="9" x2="21" y2="9"></line><line x1="3" y1="15" x2="21" y2="15"></line><line x1="9" y1="3" x2="9" y2="21"></line>
          </svg>
          Tabelle
        </button>
      </div>

      <!-- List View -->
      <div v-if="viewMode === 'list'" class="list-view">
        <div v-for="box in boxes" :key="box.id" class="list-card" :class="{ 'is-empty': isEmpty(box) }" @click="$router.push(`/boxes/${box.id}`)" style="cursor:pointer">
          <div class="list-thumb">
            <img v-if="box.image_url" :src="box.image_url" :alt="box.name" class="thumb-img">
            <span v-else class="thumb-id">B{{ box.id }}</span>
          </div>
          <span class="list-id">B{{ box.id }}</span>
          <div class="list-info">
            <div class="list-name">{{ box.name }}</div>
            <div class="list-sub">{{ box.room ? box.room.name : 'Inbox' }}</div>
          </div>
          <div class="list-footer" @click.stop>
            <div class="list-stats">
              <span v-if="isEmpty(box)" class="stat-chip stat-chip--empty">leer</span>
              <span v-else class="stat-chip">{{ box.items_count || 0 }} Items</span>
            </div>
            <div class="list-actions">
              <router-link :to="`/boxes/${box.id}`" class="row-btn" title="Ansehen">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>
                </svg>
              </router-link>
              <router-link v-if="canEdit" :to="`/boxes/${box.id}/edit`" class="row-btn" title="Bearbeiten">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
              </router-link>
              <button v-if="canDelete" class="row-btn row-btn--danger" title="Löschen" @click="confirmDelete(box)">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                  <path d="M10 11v6"></path><path d="M14 11v6"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Gallery View -->
      <div v-else-if="viewMode === 'gallery'" class="gallery-view">
        <router-link v-for="box in boxes" :key="box.id" :to="`/boxes/${box.id}`" class="gallery-card" :class="{ 'is-empty': isEmpty(box) }">
          <div class="gallery-img-wrap" :style="box.cover_image ? { background: colorMap[box.cover_image.url] || '#f3f4f6' } : {}">
            <img v-if="box.cover_image" :src="box.cover_image.url" :alt="box.name" class="gallery-img">
            <div v-else class="gallery-placeholder box-placeholder">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
              </svg>
            </div>
          </div>
          <div class="gallery-info">
            <div class="gallery-id">B{{ box.id }}</div>
            <div class="gallery-name">{{ box.name }}</div>
            <div class="gallery-loc">
              <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle>
              </svg>
              {{ box.room ? box.room.name : 'Inbox' }}
            </div>
            <div class="gallery-stats">{{ isEmpty(box) ? 'leer' : `${box.items_count || 0} Items` }}</div>
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
                <th>Box</th>
                <th>Raum</th>
                <th>Items</th>
                <th style="width:80px"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="box in boxes" :key="box.id" class="table-row" :class="{ 'is-empty': isEmpty(box) }">
                <td class="td-thumb">
                  <div class="row-thumb">
                    <img v-if="box.cover_image" :src="box.cover_image.url" :alt="box.name" class="row-thumb-img">
                    <span v-else class="row-thumb-id">B{{ box.id }}</span>
                  </div>
                </td>
                <td class="td-main">
                  <router-link :to="`/boxes/${box.id}`" class="row-name">{{ box.name }}</router-link>
                  <span class="row-id">B{{ box.id }}</span>
                </td>
                <td class="muted-text">{{ box.room ? box.room.name : 'Inbox' }}</td>
                <td><span class="chip" :class="{ 'chip--empty': isEmpty(box) }">{{ isEmpty(box) ? 'leer' : (box.items_count || 0) }}</span></td>
                <td class="td-actions">
                  <div class="actions-wrap">
                    <router-link :to="`/boxes/${box.id}`" class="row-btn" title="Ansehen">
                      <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>
                      </svg>
                    </router-link>
                    <router-link v-if="canEdit" :to="`/boxes/${box.id}/edit`" class="row-btn" title="Bearbeiten">
                      <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                      </svg>
                    </router-link>
                    <button v-if="canDelete" class="row-btn row-btn--danger" title="Löschen" @click="confirmDelete(box)">
                      <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                        <path d="M10 11v6"></path><path d="M14 11v6"></path>
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

    <div v-else-if="search" class="empty-state">
      <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      </svg>
      <h3>Keine Treffer für diese Filter</h3>
      <p>Passen Sie die Suche an oder setzen Sie sie zurück</p>
      <button class="btn-primary" @click="resetSearch">Filter zurücksetzen</button>
    </div>

    <div v-else class="empty-state">
      <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
      </svg>
      <h3>Keine Boxen gefunden</h3>
      <p>Erstellen Sie Ihre erste Box</p>
      <router-link to="/boxes/new" class="btn-primary">Neue Box</router-link>
    </div>

    <!-- Delete confirmation -->
    <div v-if="deleteTarget" class="confirm-backdrop" @click.self="deleteTarget = null">
      <div class="confirm-dialog">
        <p>Box <strong>{{ deleteTarget.name }}</strong> wirklich löschen?</p>
        <div class="confirm-actions">
          <button class="btn btn-secondary" @click="deleteTarget = null">Abbrechen</button>
          <button class="btn-danger" :disabled="deleting" @click="doDelete">
            {{ deleting ? '…' : 'Löschen' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import api from '@/services/api'
import { useToast } from 'vue-toastification'
import { debounce } from 'lodash'
import { useAuthStore } from '@/stores/auth'
import { extractImageColor } from '@/composables/useImageColor'

const toast = useToast()
const authStore = useAuthStore()
const canEdit = computed(() => authStore.isEditor)
const canDelete = computed(() => authStore.isAdmin)
const boxes = ref([])
const loading = ref(true)
const colorMap = ref({})

watch(boxes, (newBoxes) => {
  for (const box of newBoxes) {
    const url = box.cover_image?.url
    if (url && !colorMap.value[url]) {
      extractImageColor(url).then(color => { colorMap.value[url] = color })
    }
  }
}, { immediate: true })
const search = ref('')
const deleteTarget = ref(null)
const deleting = ref(false)

const viewMode = ref(localStorage.getItem('boxes-view-mode') || 'list')
watch(viewMode, val => localStorage.setItem('boxes-view-mode', val))

onMounted(fetchBoxes)

const handleSearch = debounce(fetchBoxes, 300)

async function fetchBoxes() {
  loading.value = true
  try {
    const params = { per_page: 500, ...(search.value ? { search: search.value } : {}) }
    const res = await api.get('/boxes', { params })
    boxes.value = res.data.data?.data || res.data.data
  } catch {
    toast.error('Fehler beim Laden')
  } finally {
    loading.value = false
  }
}

function isEmpty(box) {
  return !box.items_count
}

function resetSearch() {
  search.value = ''
  fetchBoxes()
}

function confirmDelete(box) {
  deleteTarget.value = box
}

async function doDelete() {
  if (deleting.value || !deleteTarget.value) return
  deleting.value = true
  try {
    await api.delete(`/boxes/${deleteTarget.value.id}`)
    toast.success('Box gelöscht')
    boxes.value = boxes.value.filter(b => b.id !== deleteTarget.value.id)
    deleteTarget.value = null
  } catch {
    // interceptor shows error
  } finally {
    deleting.value = false
  }
}
</script>

<style lang="scss" scoped>
.boxes-page { max-width: 1200px; margin: 0 auto; }

.page-header {
  display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;
  h1 { font-size: 1.5rem; font-weight: 600; margin: 0; }
}
.header-actions { display: flex; gap: 0.75rem; align-items: center; flex-shrink: 0; }

.filters-card { padding: 1rem; margin-bottom: 1.5rem; }
.filters-row { display: flex; gap: 1rem; }
.search-field {
  flex: 1; min-width: 200px; display: flex; align-items: center; gap: 0.5rem;
  padding: 0.5rem 0.75rem; background: #f3f4f6; border-radius: 8px;
  svg { color: #9ca3af; flex-shrink: 0; }
  input { flex: 1; border: none; background: transparent; outline: none; font-size: 0.875rem; }
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
  background: #ede9fe; display: flex; align-items: center; justify-content: center; overflow: hidden;
}
.thumb-img { width: 100%; height: 100%; object-fit: cover; display: block; }
.thumb-id { font-size: 0.7rem; font-weight: 700; color: #7c3aed; }
.list-id { font-size: 0.75rem; font-weight: 600; color: #7c3aed; flex-shrink: 0; min-width: 32px; }
.list-info { flex: 1; min-width: 0; }
.list-name { font-weight: 600; font-size: 0.9rem; color: #111827; }
.list-sub { font-size: 0.8rem; color: #9ca3af; }
.list-footer { display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }
.list-stats { display: flex; gap: 0.5rem; }
.stat-chip { font-size: 0.75rem; padding: 0.2rem 0.6rem; background: #f3f4f6; color: #6b7280; border-radius: 99px; }
.list-actions { display: flex; gap: 0.25rem; }

/* Empty state (0 Items) */
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
.box-placeholder { background: #f5f3ff; color: #7c3aed; }
.gallery-info { padding: 0.75rem; }
.gallery-id { font-size: 0.7rem; font-weight: 600; color: #7c3aed; }
.gallery-name { font-size: 0.875rem; font-weight: 600; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin: 0.1rem 0; }
.gallery-loc { display: flex; align-items: center; gap: 0.25rem; font-size: 0.72rem; color: #9ca3af; }
.gallery-stats { font-size: 0.72rem; color: #9ca3af; margin-top: 0.2rem; }

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
  background: #ede9fe; display: flex; align-items: center; justify-content: center;
}
.row-thumb-img { width: 100%; height: 100%; object-fit: cover; display: block; }
.row-thumb-id { font-size: 0.65rem; font-weight: 700; color: #7c3aed; }
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
  &.row-btn--danger:hover { background: var(--bg-danger); color: var(--text-danger); }
}

.btn-danger {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.5rem 0.875rem; background: #fee2e2; color: #991b1b;
  border: 1px solid #fca5a5; border-radius: 8px; font-size: 0.875rem;
  font-weight: 500; cursor: pointer; transition: background 0.15s;
  &:hover:not(:disabled) { background: #fecaca; }
  &:disabled { opacity: 0.5; cursor: not-allowed; }
}

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
</style>
