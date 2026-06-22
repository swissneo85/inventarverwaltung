<template>
  <div class="page">
    <div class="page-header">
      <button class="btn btn-secondary btn-back" @click="cancel">← Zurück</button>
      <h1>{{ id ? 'Gegenstand bearbeiten' : 'Neuer Gegenstand' }}</h1>
    </div>

    <div v-if="loading" class="loading">Wird geladen...</div>

    <template v-else>
      <div class="card form-card">
        <form @submit.prevent="save">

          <!-- Fotos -->
          <div class="form-group">
            <label>Fotos</label>
            <template v-if="id">
              <ImageGallery type="items" :model-id="id" />
            </template>
            <template v-else>
              <div class="pending-upload">
                <div class="upload-btns">
                  <label class="btn btn-secondary upload-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                      <circle cx="12" cy="13" r="4"/>
                    </svg>
                    Kamera
                    <input type="file" accept="image/*" capture="environment" @change="addPending" style="display:none">
                  </label>
                  <label class="btn btn-secondary upload-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/>
                      <polyline points="21 15 16 10 5 21"/>
                    </svg>
                    Galerie
                    <input type="file" accept="image/*" multiple @change="addPending" style="display:none">
                  </label>
                </div>
                <div v-if="pendingFiles.length" class="pending-grid">
                  <div v-for="(pf, i) in pendingFiles" :key="i" class="pending-thumb">
                    <img :src="pf.preview" :alt="pf.file.name">
                    <button type="button" class="pending-remove" @click="pendingFiles.splice(i, 1)">×</button>
                  </div>
                </div>
                <p v-else class="pending-hint">Fotos auswählen – werden beim Speichern hochgeladen</p>
              </div>
            </template>
          </div>

          <!-- Name -->
          <div class="form-group">
            <label>Name *</label>
            <input v-model="form.name" type="text" required placeholder="z.B. Laptop, Stuhl…">
          </div>

          <!-- Kategorie / Zustand -->
          <div class="form-row">
            <div class="form-group">
              <label>Kategorie</label>
              <SearchableSelect
                v-model="form.category_id"
                :options="categoryOptions"
                placeholder="Kategorie wählen…"
                create-route="CategoryCreate"
                create-label="Neue Kategorie anlegen"
                @before-navigate="saveFormDraft"
              />
            </div>
            <div class="form-group">
              <label>Zustand</label>
              <select v-model="form.condition">
                <option value="">–</option>
                <option>Neu</option>
                <option>Gut</option>
                <option>Gebraucht</option>
                <option>Defekt</option>
              </select>
            </div>
          </div>

          <!-- Status -->
          <div class="form-group">
            <label>Status</label>
            <select v-model="form.status">
              <option value="aktiv">Aktiv</option>
              <option value="entsorgt">Entsorgt</option>
              <option value="verkauft">Verkauft</option>
              <option value="verschenkt">Verschenkt</option>
              <option value="verloren">Verloren</option>
              <option value="defekt_entsorgt">Defekt / Entsorgt</option>
            </select>
          </div>

          <!-- Konditionale Felder bei Status != aktiv -->
          <div v-if="form.status && form.status !== 'aktiv'" class="form-row status-extra">
            <div class="form-group">
              <label>Status-Datum</label>
              <input v-model="form.status_datum" type="date">
            </div>
            <div class="form-group">
              <label>Notiz zum Status</label>
              <input v-model="form.status_notiz" type="text" placeholder="z.B. an Nachbarn verkauft für 20.–">
            </div>
          </div>

          <!-- Besitzer / Ausgeliehen an -->
          <div class="form-row">
            <div class="form-group">
              <label>Besitzer</label>
              <SearchableSelect
                v-model="form.person_id"
                :options="personOwnerOptions"
                placeholder="Besitzer wählen…"
                create-route="PersonCreate"
                create-label="Neue Person anlegen"
                @before-navigate="saveFormDraft"
              />
            </div>
            <div class="form-group">
              <label>Ausgeliehen an</label>
              <SearchableSelect
                v-model="form.loaned_to_person_id"
                :options="personLoanOptions"
                placeholder="Person wählen…"
                create-route="PersonCreate"
                create-label="Neue Person anlegen"
                return-field="loaned_to_person_id"
                @before-navigate="saveFormDraft"
              />
            </div>
          </div>

          <!-- Marke / Modell -->
          <div class="form-row">
            <div class="form-group">
              <label>Marke</label>
              <AutocompleteInput v-model="form.brand" field="brand" placeholder="z.B. Apple" />
            </div>
            <div class="form-group">
              <label>Modell</label>
              <AutocompleteInput v-model="form.model" field="model" placeholder="z.B. MacBook Pro" />
            </div>
          </div>

          <!-- Menge / Einheit -->
          <div class="form-row">
            <div class="form-group">
              <label>Menge</label>
              <input v-model="form.quantity" type="number" min="0" step="0.01">
            </div>
            <div class="form-group">
              <label>Einheit</label>
              <input v-model="form.unit" type="text" placeholder="z.B. Stück">
            </div>
          </div>

          <!-- Seriennummer -->
          <div class="form-group">
            <label>Seriennummer</label>
            <input v-model="form.serial_number" type="text">
          </div>

          <!-- Raum / Box -->
          <div class="form-row">
            <div class="form-group">
              <label>Raum</label>
              <SearchableSelect
                :model-value="form.room_id"
                @update:model-value="val => { form.room_id = val; if (val) form.box_id = '' }"
                :options="roomOptions"
                placeholder="Raum wählen..."
                create-route="RoomCreate"
                create-label="Neuen Raum anlegen"
                @before-navigate="saveFormDraft"
              />
            </div>
            <div class="form-group">
              <label>Box</label>
              <SearchableSelect
                :model-value="form.box_id"
                @update:model-value="val => { form.box_id = val; if (val) form.room_id = '' }"
                :options="boxOptions"
                placeholder="Box wählen..."
                create-route="BoxCreate"
                create-label="Neue Box anlegen"
                @before-navigate="saveFormDraft"
              />
            </div>
          </div>

          <!-- Kaufpreis / Kaufdatum -->
          <div class="form-row">
            <div class="form-group">
              <label>Kaufpreis (CHF)</label>
              <input v-model="form.purchase_price" type="number" min="0" step="0.01">
            </div>
            <div class="form-group">
              <label>Kaufdatum</label>
              <input v-model="form.purchased_at" type="date">
            </div>
          </div>

          <!-- Kaufort -->
          <div class="form-group">
            <label>Kaufort</label>
            <AutocompleteInput v-model="form.purchase_location" field="purchase_location" placeholder="z.B. MediaMarkt, Amazon…" />
          </div>

          <!-- Garantie -->
          <div class="form-group">
            <label>Garantie bis</label>
            <input v-model="form.warranty_until" type="date">
          </div>

          <!-- Beschreibung -->
          <div class="form-group">
            <label>Beschreibung</label>
            <textarea v-model="form.description" rows="2" placeholder="Optional…"></textarea>
          </div>

          <!-- Notizen -->
          <div class="form-group">
            <label>Notizen</label>
            <textarea v-model="form.notes" rows="2" placeholder="Interne Notizen…"></textarea>
          </div>

          <div class="form-actions">
            <button type="button" class="btn btn-secondary" @click="cancel">Abbrechen</button>
            <button type="submit" class="btn btn-primary" :disabled="saving || !form.name">
              {{ saving ? 'Wird gespeichert...' : (id ? 'Speichern' : 'Erstellen') }}
            </button>
          </div>
        </form>
      </div>

      <!-- Dokumente (nur im Edit-Modus) -->
      <div v-if="id" class="card form-card">
        <h3 class="section-title">Dokumente</h3>
        <DocumentGallery
          :item-id="id"
          :documents="documents"
          :readonly="false"
          @update="loadDocuments"
        />
      </div>

      <!-- Delete (edit mode only) -->
      <div v-if="id && canDelete" class="danger-zone">
        <button type="button" class="btn-delete" @click="confirmDelete = true">🗑 Gegenstand löschen</button>
      </div>
    </template>

    <!-- Delete confirmation -->
    <div v-if="confirmDelete" class="confirm-backdrop" @click.self="confirmDelete = false">
      <div class="confirm-dialog">
        <p>Gegenstand <strong>{{ form.name }}</strong> wirklich löschen?</p>
        <div class="confirm-actions">
          <button class="btn btn-secondary" @click="confirmDelete = false">Abbrechen</button>
          <button class="btn-danger" :disabled="deleting" @click="deleteItem">
            {{ deleting ? '…' : 'Löschen' }}
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
import ImageGallery from '@/components/ImageGallery.vue'
import SearchableSelect from '@/components/SearchableSelect.vue'
import DocumentGallery from '@/components/DocumentGallery.vue'
import AutocompleteInput from '@/components/AutocompleteInput.vue'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const canEdit = computed(() => authStore.isEditor)
const canDelete = computed(() => authStore.isAdmin)

const id = route.params.id
const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)
const confirmDelete = ref(false)
const documents = ref([])
const categories = ref([])

const FORM_DRAFT_KEY = 'item_form_draft'

function saveFormDraft() {
  if (!id) {
    sessionStorage.setItem(FORM_DRAFT_KEY, JSON.stringify(form.value))
  }
}

function cancel() {
  sessionStorage.removeItem(FORM_DRAFT_KEY)
  if (id) {
    router.push({ name: 'ItemDetail', params: { id } })
  } else {
    router.push({ name: 'Items' })
  }
}
const rooms = ref([])
const boxes = ref([])
const persons = ref([])
const pendingFiles = ref([])

const roomOptions = computed(() => [
  { value: '', label: '— Inbox (kein Raum) —' },
  ...rooms.value.map(r => ({ value: r.id, label: r.name })),
])

const boxOptions = computed(() => [
  { value: '', label: '— keine Box —' },
  ...boxes.value.map(b => ({ value: b.id, label: b.name })),
])

const categoryOptions = computed(() => [
  { value: '', label: '— keine Kategorie —' },
  ...categories.value.map(c => ({ value: c.id, label: c.name })),
])

const personOwnerOptions = computed(() => [
  { value: '', label: '— kein Besitzer —' },
  ...persons.value.map(p => ({ value: p.id, label: p.name })),
])

const personLoanOptions = computed(() => [
  { value: '', label: '— niemand —' },
  ...persons.value.map(p => ({ value: p.id, label: p.name })),
])

const form = ref({
  name: '',
  description: '',
  notes: '',
  category_id: '',
  person_id: '',
  loaned_to_person_id: '',
  condition: '',
  status: 'aktiv',
  status_datum: '',
  status_notiz: '',
  brand: '',
  model: '',
  serial_number: '',
  quantity: 1,
  unit: '',
  room_id: '',
  box_id: '',
  purchase_price: '',
  purchased_at: '',
  warranty_until: '',
  purchase_location: '',
})

async function loadDocuments() {
  if (!id) return
  try {
    const res = await api.get(`/items/${id}/documents`)
    documents.value = res.data.data ?? []
  } catch {
    // silent — documents are non-critical
  }
}

onMounted(async () => {
  loading.value = true
  try {
    const requests = [
      api.get('/categories'),
      api.get('/rooms'),
      api.get('/boxes', { params: { per_page: 200 } }),
      api.get('/persons'),
    ]
    if (id) requests.push(api.get(`/items/${id}`))

    const results = await Promise.all(requests)
    categories.value = results[0].data.data
    rooms.value = results[1].data.data
    boxes.value = results[2].data.data?.data ?? results[2].data.data
    persons.value = results[3].data.data

    if (id) {
      const item = results[4].data.data
      form.value = {
        name: item.name ?? '',
        description: item.description ?? '',
        notes: item.notes ?? '',
        category_id: item.category_id ?? '',
        person_id: item.person_id ?? '',
        loaned_to_person_id: item.loaned_to_person_id ?? '',
        condition: item.condition ?? '',
        status: item.status ?? 'aktiv',
        status_datum: item.status_datum ? item.status_datum.substring(0, 10) : '',
        status_notiz: item.status_notiz ?? '',
        brand: item.brand ?? '',
        model: item.model ?? '',
        serial_number: item.serial_number ?? '',
        quantity: item.quantity ?? 1,
        unit: item.unit ?? '',
        room_id: item.room_id ?? '',
        box_id: item.box_id ?? '',
        purchase_price: item.purchase_price ?? '',
        purchased_at: item.purchased_at ? item.purchased_at.substring(0, 10) : '',
        warranty_until: item.warranty_until ? item.warranty_until.substring(0, 10) : '',
        purchase_location: item.purchase_location ?? '',
      }
      // Ensure assigned persons (possibly inactive) appear in the dropdown
      const knownIds = new Set(persons.value.map(p => p.id))
      for (const rel of [item.person, item.loaned_to_person]) {
        if (rel && !knownIds.has(rel.id)) {
          persons.value.push(rel)
          knownIds.add(rel.id)
        }
      }
      await loadDocuments()
    } else {
      // Neu-Erstellen: Draft aus sessionStorage wiederherstellen
      const raw = sessionStorage.getItem(FORM_DRAFT_KEY)
      if (raw) {
        try { form.value = { ...form.value, ...JSON.parse(raw) } } catch {}
        sessionStorage.removeItem(FORM_DRAFT_KEY)
      }
    }

    // Neu angelegte Entität aus Return-Flow vorauswählen
    if (route.query.newRoomId) {
      form.value.room_id = Number(route.query.newRoomId)
      form.value.box_id = ''
      router.replace({ query: { ...route.query, newRoomId: undefined } })
    } else if (route.query.newBoxId) {
      form.value.box_id = Number(route.query.newBoxId)
      form.value.room_id = ''
      router.replace({ query: { ...route.query, newBoxId: undefined } })
    } else if (route.query.newCategoryId) {
      form.value.category_id = Number(route.query.newCategoryId)
      router.replace({ query: { ...route.query, newCategoryId: undefined } })
    } else if (route.query.newPersonId) {
      if (route.query.returnField === 'loaned_to_person_id') {
        form.value.loaned_to_person_id = Number(route.query.newPersonId)
        router.replace({ query: { ...route.query, newPersonId: undefined, returnField: undefined } })
      } else {
        form.value.person_id = Number(route.query.newPersonId)
        router.replace({ query: { ...route.query, newPersonId: undefined } })
      }
    }
  } catch {
    toast.error('Fehler beim Laden')
    router.push({ name: 'Items' })
  } finally {
    loading.value = false
  }
})

function addPending(e) {
  Array.from(e.target.files).forEach(file => {
    if (!file.type.startsWith('image/')) return
    const reader = new FileReader()
    reader.onload = ev => pendingFiles.value.push({ file, preview: ev.target.result })
    reader.readAsDataURL(file)
  })
  e.target.value = ''
}

async function compressImage(file) {
  if (file.size <= 500 * 1024) return file
  return new Promise(resolve => {
    const fallback = () => resolve(file)
    try {
      const reader = new FileReader()
      reader.onerror = fallback
      reader.onload = e => {
        const img = new Image()
        img.onerror = fallback
        img.onload = () => {
          try {
            const MAX = 1920
            let { width, height } = img
            if (width > MAX || height > MAX) {
              if (width >= height) { height = Math.round(height * MAX / width); width = MAX }
              else { width = Math.round(width * MAX / height); height = MAX }
            }
            const canvas = document.createElement('canvas')
            canvas.width = width; canvas.height = height
            canvas.getContext('2d').drawImage(img, 0, 0, width, height)
            canvas.toBlob(blob => {
              if (!blob) { fallback(); return }
              resolve(new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), { type: 'image/jpeg', lastModified: Date.now() }))
            }, 'image/jpeg', 0.82)
          } catch { fallback() }
        }
        img.src = e.target.result
      }
      reader.readAsDataURL(file)
    } catch { fallback() }
  })
}

async function deleteItem() {
  if (deleting.value) return
  deleting.value = true
  try {
    await api.delete(`/items/${id}`)
    toast.success('Gegenstand gelöscht')
    router.push({ name: 'Items' })
  } catch {
    // interceptor shows error
  } finally {
    deleting.value = false
    confirmDelete.value = false
  }
}

function buildPayload() {
  const f = form.value
  const nullify = v => (v === '' || v === undefined) ? null : v
  const payload = {
    ...f,
    category_id: nullify(f.category_id),
    person_id: nullify(f.person_id),
    loaned_to_person_id: nullify(f.loaned_to_person_id),
    room_id: nullify(f.room_id),
    box_id: nullify(f.box_id),
    status_datum: nullify(f.status_datum),
    status_notiz: nullify(f.status_notiz),
  }
  payload.is_in_inbox = !payload.room_id && !payload.box_id
  return payload
}

async function save() {
  if (!form.value.name || saving.value) return
  saving.value = true
  try {
    sessionStorage.removeItem(FORM_DRAFT_KEY)
    const payload = buildPayload()
    if (id) {
      await api.put(`/items/${id}`, payload)
      toast.success('Gespeichert')
      router.push({ name: 'ItemDetail', params: { id } })
    } else {
      const res = await api.post('/items', payload)
      const newId = res.data.data?.id
      if (newId && pendingFiles.value.length) {
        for (const pf of pendingFiles.value) {
          try {
            const compressed = await compressImage(pf.file)
            const fd = new FormData()
            fd.append('image', compressed)
            await api.post(`/items/${newId}/images`, fd)
          } catch { /* einzelner Bild-Fehler überspringen */ }
        }
      }
      toast.success('Erstellt')
      router.push({ name: 'ItemDetail', params: { id: newId } })
    }
  } catch {
    toast.error('Fehler beim Speichern')
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.page { max-width: 800px; margin: 0 auto; }

.section-title { font-size: 1rem; font-weight: 600; margin: 0 0 1rem; color: #111827; }

.page-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.page-header h1 {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0;
}

.btn-back { flex-shrink: 0; }

.form-card { padding: 1.5rem; }

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  font-size: 0.8rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.3rem;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 16px;
  background: white;
  font-family: inherit;
  box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #3b82f6;
}

.form-group textarea { resize: vertical; min-height: 60px; }

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 1.5rem;
}

.pending-upload { display: flex; flex-direction: column; gap: 0.75rem; }
.upload-btns { display: flex; gap: 0.75rem; flex-wrap: wrap; }
.upload-btn {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.5rem 1rem; font-size: 0.875rem; cursor: pointer;
}
.pending-hint { font-size: 0.8rem; color: #9ca3af; margin: 0; }

.pending-grid { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.pending-thumb {
  position: relative; width: 80px; height: 80px;
  border-radius: 6px; overflow: hidden;
}
.pending-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.pending-remove {
  position: absolute; top: 2px; right: 2px;
  width: 20px; height: 20px; border-radius: 50%;
  background: rgba(239,68,68,0.85); color: #fff;
  border: none; font-size: 0.75rem; cursor: pointer;
  display: flex; align-items: center; justify-content: center; padding: 0;
}

.loading { padding: 2rem; text-align: center; color: #6b7280; }

.danger-zone {
  margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #fee2e2;
}
.btn-delete {
  width: 100%; padding: 0.75rem; background: none;
  border: 1px solid #ef4444; color: #ef4444;
  border-radius: 8px; font-size: 1rem; cursor: pointer; transition: background 0.15s;
}
.btn-delete:hover { background: #fef2f2; }

.btn-danger {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.5rem 0.875rem; background: #fee2e2; color: #991b1b;
  border: 1px solid #fca5a5; border-radius: 8px; font-size: 0.875rem;
  font-weight: 500; cursor: pointer; transition: background 0.15s;
}
.btn-danger:hover:not(:disabled) { background: #fecaca; }
.btn-danger:disabled { opacity: 0.5; cursor: not-allowed; }

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

.status-extra {
  background: #f9fafb;
  border-radius: 8px;
  padding: 0.75rem;
  border: 1px solid #e5e7eb;
  margin-top: -0.5rem;
}

@media (max-width: 767px) {
  .form-card { padding: 1rem; }
  .form-row { grid-template-columns: 1fr; }
  .upload-btns { flex-direction: row; }
  .upload-btn { flex: 1; justify-content: center; min-height: 44px; }
  .form-actions {
    flex-direction: column-reverse;
  }
  .form-actions .btn { width: 100%; justify-content: center; }
}
</style>
