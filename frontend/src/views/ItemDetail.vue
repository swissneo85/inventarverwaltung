<template>
  <div class="page">
    <div class="page-header">
      <div class="header-row">
        <button class="btn btn-secondary btn-back" @click="$router.push({ name: 'Items' })">← Zurück</button>
        <div class="header-actions" v-if="item && canEdit">
          <button class="btn btn-primary" @click="$router.push({ name: 'ItemEdit', params: { id: item.id } })">
            Bearbeiten
          </button>
        </div>
      </div>
      <h1 v-if="item">{{ item.display_id }} – {{ item.name }}</h1>
      <h1 v-else-if="!loading">Gegenstand</h1>
    </div>

    <div v-if="loading" class="loading">Wird geladen...</div>

    <div v-else-if="item">
      <!-- Archivierungs-Banner -->
      <div v-if="item.status && item.status !== 'aktiv'" :class="['status-banner', statusBannerClass(item.status)]">
        <span class="status-banner-label">{{ statusLabel(item.status) }}</span>
        <span v-if="item.status_datum"> · {{ formatDate(item.status_datum) }}</span>
        <span v-if="item.status_notiz" class="status-banner-notiz"> · {{ item.status_notiz }}</span>
      </div>

      <!-- Images first -->
      <div class="card detail-card" v-if="imageCount !== 0">
        <ImageGallery type="items" :model-id="id" :readonly="true" @loaded="n => imageCount = n" />
      </div>

      <div v-if="item.documents && item.documents.length > 0" class="card detail-card">
        <h2>Dokumente</h2>
        <DocumentGallery :item-id="item.id" :documents="item.documents" :readonly="true" />
      </div>

      <div class="card detail-card">
        <h2>Allgemein</h2>
        <div class="detail-row"><span>Kategorie</span><span>{{ item.category?.name || '–' }}</span></div>
        <div class="detail-row"><span>Besitzer</span><span>{{ item.person?.name || '–' }}</span></div>
        <div v-if="item.loaned_to_person" class="detail-row">
          <span>Ausgeliehen an</span>
          <span>{{ item.loaned_to_person.name }}</span>
        </div>
        <div class="detail-row"><span>Zustand</span>
          <span v-if="item.condition" :class="['condition-badge', conditionClass(item.condition)]">{{ item.condition }}</span>
          <span v-else>–</span>
        </div>
        <div class="detail-row"><span>Status</span>
          <span :class="['status-badge', statusBannerClass(item.status)]">{{ statusLabel(item.status) }}</span>
        </div>
        <div class="detail-row"><span>Marke</span><span>{{ item.brand || '–' }}</span></div>
        <div class="detail-row"><span>Modell</span><span>{{ item.model || '–' }}</span></div>
        <div class="detail-row"><span>Seriennummer</span><span>{{ item.serial_number || '–' }}</span></div>
        <div class="detail-row"><span>Menge</span><span>{{ item.quantity }} {{ item.unit || '' }}</span></div>
      </div>

      <div class="card detail-card">
        <h2>Standort</h2>
        <div class="detail-row"><span>Ort</span><span>{{ locationText }}</span></div>
      </div>

      <div class="card detail-card" v-if="item.purchase_price || item.purchased_at || item.warranty_until || item.purchase_location">
        <h2>Kauf &amp; Garantie</h2>
        <div v-if="item.purchase_price" class="detail-row">
          <span>Kaufpreis</span><span>CHF {{ Number(item.purchase_price).toFixed(2) }}</span>
        </div>
        <div v-if="item.purchased_at" class="detail-row">
          <span>Kaufdatum</span><span>{{ formatDate(item.purchased_at) }}</span>
        </div>
        <div v-if="item.purchase_location" class="detail-row">
          <span>Kaufort</span><span>{{ item.purchase_location }}</span>
        </div>
        <div v-if="item.warranty_until" class="detail-row">
          <span>Garantie bis</span><span>{{ formatDate(item.warranty_until) }}</span>
        </div>
      </div>

      <div class="card detail-card" v-if="item.description || item.notes">
        <h2>Beschreibung &amp; Notizen</h2>
        <div v-if="item.description" class="detail-row detail-row--block">
          <span>Beschreibung</span><p>{{ item.description }}</p>
        </div>
        <div v-if="item.notes" class="detail-row detail-row--block">
          <span>Notizen</span><p>{{ item.notes }}</p>
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
import ImageGallery from '@/components/ImageGallery.vue'
import DocumentGallery from '@/components/DocumentGallery.vue'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const canEdit = computed(() => authStore.isEditor)

const id = route.params.id
const item = ref(null)
const loading = ref(true)
const imageCount = ref(null)

const locationText = computed(() => {
  if (!item.value) return '–'
  if (item.value.is_in_inbox) return 'Inbox'
  if (item.value.box) return `Box: ${item.value.box.name}`
  if (item.value.room) return `Raum: ${item.value.room.name}`
  return '–'
})

function conditionClass(condition) {
  const map = { 'Neu': 'cond-new', 'Gut': 'cond-good', 'Gebraucht': 'cond-used', 'Defekt': 'cond-broken' }
  return map[condition] || 'cond-default'
}

const STATUS_LABELS = {
  'aktiv': 'Aktiv',
  'entsorgt': 'Entsorgt',
  'verkauft': 'Verkauft',
  'verschenkt': 'Verschenkt',
  'verloren': 'Verloren',
  'defekt_entsorgt': 'Defekt / Entsorgt',
}

function statusLabel(status) {
  return STATUS_LABELS[status] || status || 'Aktiv'
}

function statusBannerClass(status) {
  const map = {
    'aktiv': 'status-aktiv',
    'entsorgt': 'status-entsorgt',
    'defekt_entsorgt': 'status-entsorgt',
    'verloren': 'status-verloren',
    'verkauft': 'status-verkauft',
    'verschenkt': 'status-verkauft',
  }
  return map[status] || 'status-default'
}

function formatDate(val) {
  if (!val) return '–'
  return new Date(val).toLocaleDateString('de-CH')
}

async function loadItem() {
  try {
    const res = await api.get(`/items/${id}`)
    item.value = res.data.data
  } catch {
    router.push({ name: 'Items' })
  } finally {
    loading.value = false
  }
}

onMounted(loadItem)
</script>

<style scoped>
.page { max-width: 800px; margin: 0 auto; }

.page-header {
  margin-bottom: 1.5rem;
}

.header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 0.5rem;
}

.page-header h1 {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0;
}

.btn-back { flex-shrink: 0; }
.header-actions { flex-shrink: 0; }

.detail-card { padding: 1.5rem; margin-bottom: 1rem; }
.detail-card h2 { font-size: 1rem; font-weight: 600; margin: 0 0 1rem; color: #111827; }

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  padding: 0.5rem 0;
  border-bottom: 1px solid #f3f4f6;
  gap: 0.5rem;
}
.detail-row:last-child { border-bottom: none; }
.detail-row > span:first-child { color: #6b7280; font-size: 0.875rem; flex-shrink: 0; }
.detail-row > span:last-child { text-align: right; font-size: 0.875rem; }

.detail-row--block { flex-direction: column; gap: 0.25rem; }
.detail-row--block > span:first-child { font-size: 0.8rem; }
.detail-row--block p { margin: 0; font-size: 0.875rem; color: #374151; white-space: pre-wrap; }

.condition-badge {
  display: inline-block;
  padding: 0.2rem 0.6rem;
  border-radius: 99px;
  font-size: 0.75rem;
  font-weight: 500;
}
.cond-new     { background: #d1fae5; color: #065f46; }
.cond-good    { background: #dbeafe; color: #1e40af; }
.cond-used    { background: #fef3c7; color: #92400e; }
.cond-broken  { background: #fee2e2; color: #991b1b; }
.cond-default { background: #f3f4f6; color: #6b7280; }

.status-banner {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.25rem;
  padding: 0.75rem 1rem;
  border-radius: 10px;
  font-size: 0.9rem;
  font-weight: 600;
  margin-bottom: 1rem;
}
.status-banner-label { font-weight: 700; }
.status-banner-notiz { font-weight: 400; font-style: italic; }

.status-badge {
  display: inline-block;
  padding: 0.2rem 0.6rem;
  border-radius: 99px;
  font-size: 0.75rem;
  font-weight: 500;
}

.status-aktiv    { background: #d1fae5; color: #065f46; }
.status-entsorgt { background: #f3f4f6; color: #374151; }
.status-verloren { background: #fee2e2; color: #991b1b; }
.status-verkauft { background: #e0f2fe; color: #075985; }
.status-default  { background: #f3f4f6; color: #6b7280; }

.loading { padding: 2rem; text-align: center; color: #6b7280; }

@media (max-width: 767px) {
  .detail-card { padding: 1rem; }
  .page-header h1 { font-size: 1.1rem; }
}
</style>
