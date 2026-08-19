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
        Gegenstände ({{ itemsTotal }})
      </button>
      <button
        :class="['tab', { active: activeTab === 'boxes' }]"
        @click="activeTab = 'boxes'"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
        </svg>
        Boxen ({{ boxesTotal }})
      </button>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Wird geladen...</p>
    </div>

    <!-- Bulk action bar -->
    <div v-else-if="canEdit && bulkSelectionCount > 0" class="bulk-bar">
      <span>{{ bulkSelectionCount }} {{ bulkSelectionLabel }} ausgewählt</span>
      <div class="bulk-bar-actions">
        <button class="btn btn-secondary btn-sm" @click="clearSelection">Auswahl aufheben</button>
        <button class="btn btn-primary btn-sm" @click="openBulkDialog">Markierte zuweisen</button>
      </div>
    </div>

    <!-- Items Tab -->
    <div v-if="!loading && activeTab === 'items'">
      <div v-if="items.length === 0" class="empty-card">
        <p>Keine Gegenstände in der Inbox</p>
      </div>
      <template v-else>
        <div v-if="canEdit" class="list-toolbar">
          <label class="select-all">
            <input type="checkbox" :checked="allVisibleItemsSelected" @change="toggleSelectAllItems">
            Alle geladenen Gegenstände auswählen
          </label>
        </div>
        <div class="items-list">
          <div
            v-for="item in items"
            :key="item.id"
            class="inbox-item card"
            :class="{ 'is-selected': selectedItemIds.has(item.id) }"
          >
            <label v-if="canEdit" class="row-checkbox" @click.stop>
              <input type="checkbox" :checked="selectedItemIds.has(item.id)" @change="toggleItemSelected(item.id)">
            </label>
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
                  @before-navigate="saveRowDraft('item', item.id)"
                />
                <SearchableSelect
                  :model-value="item._targetBox"
                  @update:model-value="val => { item._targetBox = val; if (val) item._targetRoom = '' }"
                  :options="filteredBoxOptions(item._targetRoom)"
                  placeholder="Box wählen…"
                  create-route="BoxCreate"
                  create-label="Neue Box anlegen"
                  @before-navigate="saveRowDraft('item', item.id)"
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
        <div v-if="itemsPage < itemsLastPage" class="load-more-wrap">
          <button class="btn btn-secondary" :disabled="itemsLoadingMore" @click="loadMoreItems">
            {{ itemsLoadingMore ? 'Wird geladen…' : 'Mehr laden' }}
          </button>
        </div>
      </template>
    </div>

    <!-- Boxes Tab -->
    <div v-if="!loading && activeTab === 'boxes'">
      <div v-if="inboxBoxes.length === 0" class="empty-card">
        <p>Keine Boxen in der Inbox</p>
      </div>
      <template v-else>
        <div v-if="canEdit" class="list-toolbar">
          <label class="select-all">
            <input type="checkbox" :checked="allVisibleBoxesSelected" @change="toggleSelectAllBoxes">
            Alle Boxen auswählen
          </label>
        </div>
        <div class="items-list">
          <div
            v-for="box in inboxBoxes"
            :key="box.id"
            class="inbox-item card"
            :class="{ 'is-selected': selectedBoxIds.has(box.id) }"
          >
            <label v-if="canEdit" class="row-checkbox" @click.stop>
              <input type="checkbox" :checked="selectedBoxIds.has(box.id)" @change="toggleBoxSelected(box.id)">
            </label>
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
                  @before-navigate="saveRowDraft('box', box.id)"
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
      </template>
    </div>

    <!-- Bulk assign dialog -->
    <div v-if="bulkDialog.open" class="confirm-backdrop" @click.self="closeBulkDialog">
      <div class="bulk-dialog">
        <h3>Markierte zuweisen</h3>
        <p class="bulk-dialog-summary">{{ bulkSelectionCount }} {{ bulkSelectionLabel }} ausgewählt</p>

        <div class="bulk-dialog-fields">
          <div class="form-group">
            <label>Raum</label>
            <SearchableSelect
              :model-value="bulkDialog.targetRoom"
              @update:model-value="val => { bulkDialog.targetRoom = val; bulkDialog.targetBox = '' }"
              :options="roomOptions"
              placeholder="Raum wählen…"
              create-route="RoomCreate"
              create-label="Neuen Raum anlegen"
              @before-navigate="saveBulkDraft"
            />
          </div>
          <div v-if="activeTab === 'items'" class="form-group">
            <label>Box</label>
            <SearchableSelect
              :model-value="bulkDialog.targetBox"
              @update:model-value="val => { bulkDialog.targetBox = val; if (val) bulkDialog.targetRoom = '' }"
              :options="filteredBoxOptions(bulkDialog.targetRoom)"
              placeholder="Box wählen…"
              create-route="BoxCreate"
              create-label="Neue Box anlegen"
              @before-navigate="saveBulkDraft"
            />
          </div>
        </div>

        <div class="bulk-dialog-actions">
          <button class="btn btn-secondary" @click="closeBulkDialog">Abbrechen</button>
          <button
            class="btn btn-primary"
            :disabled="!bulkDialog.targetRoom && !bulkDialog.targetBox"
            @click="confirmBulkAssign"
          >
            Zuweisen
          </button>
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

const DRAFT_KEY = 'inbox_assign_draft'
const ITEMS_PER_PAGE = 50

const activeTab = ref('items')
const loading = ref(true)

const items = ref([])
const itemsPage = ref(1)
const itemsLastPage = ref(1)
const itemsTotal = ref(0)
const itemsLoadingMore = ref(false)

const inboxBoxes = ref([])
const boxesTotal = computed(() => inboxBoxes.value.length)

const rooms = ref([])
const assignableBoxes = ref([])

const selectedItemIds = ref(new Set())
const selectedBoxIds = ref(new Set())
const pendingRowDraft = ref(null)

const bulkDialog = ref({ open: false, targetRoom: '', targetBox: '' })

const roomOptions = computed(() =>
  rooms.value.map(r => ({ value: r.id, label: r.name }))
)

function filteredBoxOptions(roomId) {
  const src = roomId
    ? assignableBoxes.value.filter(b => b.room_id === roomId)
    : assignableBoxes.value
  return src.map(b => ({ value: b.id, label: b.name }))
}

const allVisibleItemsSelected = computed(() =>
  items.value.length > 0 && items.value.every(i => selectedItemIds.value.has(i.id))
)
const allVisibleBoxesSelected = computed(() =>
  inboxBoxes.value.length > 0 && inboxBoxes.value.every(b => selectedBoxIds.value.has(b.id))
)

const bulkSelectionCount = computed(() =>
  activeTab.value === 'items' ? selectedItemIds.value.size : selectedBoxIds.value.size
)
const bulkSelectionLabel = computed(() => {
  const n = bulkSelectionCount.value
  if (activeTab.value === 'items') return n === 1 ? 'Gegenstand' : 'Gegenstände'
  return n === 1 ? 'Box' : 'Boxen'
})

function toggleItemSelected(id) {
  const s = new Set(selectedItemIds.value)
  s.has(id) ? s.delete(id) : s.add(id)
  selectedItemIds.value = s
}
function toggleBoxSelected(id) {
  const s = new Set(selectedBoxIds.value)
  s.has(id) ? s.delete(id) : s.add(id)
  selectedBoxIds.value = s
}
function toggleSelectAllItems() {
  selectedItemIds.value = allVisibleItemsSelected.value
    ? new Set()
    : new Set(items.value.map(i => i.id))
}
function toggleSelectAllBoxes() {
  selectedBoxIds.value = allVisibleBoxesSelected.value
    ? new Set()
    : new Set(inboxBoxes.value.map(b => b.id))
}
function clearSelection() {
  if (activeTab.value === 'items') selectedItemIds.value = new Set()
  else selectedBoxIds.value = new Set()
}

onMounted(async () => {
  await Promise.all([fetchItemsPage(1), fetchInboxBoxes(), fetchRooms(), fetchAssignableBoxes()])
  loading.value = false
  handleReturnFlow()
})

async function fetchItemsPage(page) {
  try {
    const res = await api.get('/inbox/items', { params: { page, per_page: ITEMS_PER_PAGE } })
    const data = res.data.data
    const pageItems = (data.data || []).map(i => ({ ...i, _targetRoom: '', _targetBox: '' }))
    items.value = page === 1 ? pageItems : [...items.value, ...pageItems]
    itemsPage.value = data.current_page
    itemsLastPage.value = data.last_page
    itemsTotal.value = data.total
    applyPendingRowDraft()
  } catch {
    toast.error('Fehler beim Laden der Inbox')
  }
}

async function loadMoreItems() {
  if (itemsLoadingMore.value || itemsPage.value >= itemsLastPage.value) return
  itemsLoadingMore.value = true
  try {
    await fetchItemsPage(itemsPage.value + 1)
  } finally {
    itemsLoadingMore.value = false
  }
}

async function fetchInboxBoxes() {
  try {
    const res = await api.get('/inbox/boxes')
    inboxBoxes.value = (res.data.data || []).map(b => ({ ...b, _targetRoom: '' }))
    applyPendingRowDraft()
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

async function fetchAssignableBoxes() {
  try {
    const res = await api.get('/boxes', { params: { per_page: 500 } })
    assignableBoxes.value = res.data.data?.data ?? res.data.data
  } catch {
    // silent
  }
}

function applyPendingRowDraft() {
  const draft = pendingRowDraft.value
  if (!draft) return
  if (draft.kind === 'item') {
    const item = items.value.find(i => i.id === draft.id)
    if (item) {
      item._targetRoom = draft.targetRoom || ''
      item._targetBox = draft.targetBox || ''
      pendingRowDraft.value = null
    }
  } else if (draft.kind === 'box') {
    const box = inboxBoxes.value.find(b => b.id === draft.id)
    if (box) {
      box._targetRoom = draft.targetRoom || ''
      pendingRowDraft.value = null
    }
  }
}

function saveRowDraft(kind, id) {
  sessionStorage.setItem(DRAFT_KEY, JSON.stringify({ mode: 'row', kind, id, tab: activeTab.value }))
}

function saveBulkDraft() {
  sessionStorage.setItem(DRAFT_KEY, JSON.stringify({
    mode: 'bulk',
    tab: activeTab.value,
    selectedItemIds: [...selectedItemIds.value],
    selectedBoxIds: [...selectedBoxIds.value],
    targetRoom: bulkDialog.value.targetRoom,
    targetBox: bulkDialog.value.targetBox,
  }))
}

function handleReturnFlow() {
  const newRoomId = route.query.newRoomId ? Number(route.query.newRoomId) : null
  const newBoxId = route.query.newBoxId ? Number(route.query.newBoxId) : null
  if (!newRoomId && !newBoxId) return

  const raw = sessionStorage.getItem(DRAFT_KEY)
  sessionStorage.removeItem(DRAFT_KEY)
  let draft = null
  if (raw) {
    try { draft = JSON.parse(raw) } catch { draft = null }
  }

  if (draft?.mode === 'bulk') {
    activeTab.value = draft.tab
    selectedItemIds.value = new Set(draft.selectedItemIds || [])
    selectedBoxIds.value = new Set(draft.selectedBoxIds || [])
    bulkDialog.value = {
      open: true,
      targetRoom: newRoomId || draft.targetRoom || '',
      targetBox: newBoxId || draft.targetBox || '',
    }
  } else if (draft?.mode === 'row') {
    activeTab.value = draft.tab
    pendingRowDraft.value = { kind: draft.kind, id: draft.id, targetRoom: newRoomId || '', targetBox: newBoxId || '' }
    applyPendingRowDraft()
  }

  router.replace({ query: {} })
}

function openBulkDialog() {
  bulkDialog.value = { open: true, targetRoom: '', targetBox: '' }
}
function closeBulkDialog() {
  bulkDialog.value = { open: false, targetRoom: '', targetBox: '' }
}

async function confirmBulkAssign() {
  if (activeTab.value === 'items') {
    await bulkAssignItems()
  } else {
    await bulkAssignBoxes()
  }
  closeBulkDialog()
}

async function bulkAssignItems() {
  const { targetRoom, targetBox } = bulkDialog.value
  if (!targetRoom && !targetBox) return

  const ids = [...selectedItemIds.value]
  const results = await Promise.allSettled(ids.map(id => (
    targetBox
      ? api.post(`/items/${id}/assign-box`, { box_id: targetBox })
      : api.post(`/items/${id}/assign-room`, { room_id: targetRoom })
  )))
  const okIds = new Set(ids.filter((id, i) => results[i].status === 'fulfilled'))

  items.value = items.value.filter(i => !okIds.has(i.id))
  itemsTotal.value = Math.max(0, itemsTotal.value - okIds.size)
  selectedItemIds.value = new Set([...selectedItemIds.value].filter(id => !okIds.has(id)))

  reportBulkResult(okIds.size, ids.length - okIds.size, 'Gegenstand', 'Gegenstände')
}

async function bulkAssignBoxes() {
  const { targetRoom } = bulkDialog.value
  if (!targetRoom) return

  const ids = [...selectedBoxIds.value]
  const results = await Promise.allSettled(ids.map(id => api.post(`/boxes/${id}/assign-room`, { room_id: targetRoom })))
  const okIds = new Set(ids.filter((id, i) => results[i].status === 'fulfilled'))

  inboxBoxes.value = inboxBoxes.value.filter(b => !okIds.has(b.id))
  selectedBoxIds.value = new Set([...selectedBoxIds.value].filter(id => !okIds.has(id)))

  reportBulkResult(okIds.size, ids.length - okIds.size, 'Box', 'Boxen')
}

function reportBulkResult(okCount, failCount, singular, plural) {
  if (okCount > 0) toast.success(`${okCount} ${okCount === 1 ? singular : plural} zugewiesen`)
  if (failCount > 0) toast.error(`${failCount} ${failCount === 1 ? singular : plural} konnte(n) nicht zugewiesen werden`)
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
    itemsTotal.value = Math.max(0, itemsTotal.value - 1)
    if (selectedItemIds.value.has(item.id)) {
      const s = new Set(selectedItemIds.value)
      s.delete(item.id)
      selectedItemIds.value = s
    }
  } catch {
    toast.error('Fehler beim Zuweisen')
  }
}

async function assignBox(box) {
  try {
    await api.post(`/boxes/${box.id}/assign-room`, { room_id: box._targetRoom })
    toast.success('Box zugewiesen')
    inboxBoxes.value = inboxBoxes.value.filter(b => b.id !== box.id)
    if (selectedBoxIds.value.has(box.id)) {
      const s = new Set(selectedBoxIds.value)
      s.delete(box.id)
      selectedBoxIds.value = s
    }
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

.bulk-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.75rem 1.25rem;
  margin-bottom: 1rem;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 10px;
  color: #1e40af;
  font-size: 0.875rem;
  font-weight: 500;
  flex-wrap: wrap;
}

.bulk-bar-actions {
  display: flex;
  gap: 0.5rem;
}

.list-toolbar {
  padding: 0 0.25rem;
  margin-bottom: 0.5rem;
}

.select-all {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8rem;
  color: #6b7280;
  cursor: pointer;
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
  transition: box-shadow 0.15s, border-color 0.15s;

  &.is-selected {
    border: 1px solid #93c5fd;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
  }
}

.row-checkbox {
  display: flex;
  align-items: center;
  flex-shrink: 0;

  input {
    width: 18px;
    height: 18px;
    cursor: pointer;
  }
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

.load-more-wrap {
  display: flex;
  justify-content: center;
  margin-top: 1.25rem;
}

.confirm-backdrop {
  position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 500;
  display: flex; align-items: center; justify-content: center;
  padding: 1rem;
}

.bulk-dialog {
  background: white; border-radius: 12px; padding: 1.5rem;
  width: 100%; max-width: 420px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.2);

  h3 { margin: 0 0 0.25rem; font-size: 1.1rem; font-weight: 600; }
}

.bulk-dialog-summary {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0 0 1.25rem;
}

.bulk-dialog-fields {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 1.5rem;

  .form-group label {
    display: block;
    font-size: 0.8rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.3rem;
  }
}

.bulk-dialog-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
}

@media (max-width: 768px) {
  .inbox-item {
    flex-direction: column;
    align-items: stretch;
  }

  .row-checkbox {
    align-self: flex-start;
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

  .bulk-bar {
    flex-direction: column;
    align-items: stretch;
  }

  .bulk-bar-actions {
    flex-direction: column;
  }

  .bulk-dialog-actions {
    flex-direction: column-reverse;
    .btn { width: 100%; justify-content: center; }
  }
}
</style>
