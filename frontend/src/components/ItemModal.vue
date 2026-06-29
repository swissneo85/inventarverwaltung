<template>
  <Teleport to="body">
    <div class="modal-backdrop" @click.self="close">
      <div class="modal-sheet">

        <!-- Header -->
        <div class="modal-header">
          <span class="modal-title">{{ itemId ? 'Gegenstand bearbeiten' : 'Neuer Gegenstand' }}</span>
          <button class="modal-close" @click="close">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>

        <div v-if="loading" class="modal-loading">Wird geladen…</div>

        <div v-else class="modal-body">
          <!-- Fotos -->
          <div class="photo-row">
            <div class="photo-previews">
              <!-- Edit mode: existing images via ImageGallery -->
              <template v-if="itemId">
                <ImageGallery type="items" :model-id="itemId" compact />
              </template>
              <!-- Create mode: pending queue -->
              <template v-else>
                <div v-for="(pf, i) in pendingFiles" :key="i" class="photo-thumb">
                  <img :src="pf.preview" alt="">
                  <button type="button" class="photo-remove" @click="pendingFiles.splice(i,1)">×</button>
                </div>
                <label class="photo-add-btn">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2v11z"/>
                    <circle cx="12" cy="13" r="4"/>
                  </svg>
                  <span>Kamera</span>
                  <input type="file" accept="image/*" capture="environment" @change="addPending" style="display:none">
                </label>
                <label class="photo-add-btn">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/>
                    <polyline points="21 15 16 10 5 21"/>
                  </svg>
                  <span>Galerie</span>
                  <input type="file" accept="image/*" multiple @change="addPending" style="display:none">
                </label>
              </template>
            </div>
          </div>

          <!-- Name -->
          <div class="form-group">
            <label>Name *</label>
            <input v-model="form.name" type="text" placeholder="z.B. Laptop, Stuhl…">
          </div>

          <!-- Kategorie / Zustand -->
          <div class="form-row">
            <div class="form-group">
              <label>Kategorie</label>
              <select v-model="form.category_id">
                <option value="">Keine</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
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

          <!-- Marke / Modell -->
          <div class="form-row">
            <div class="form-group">
              <label>Marke</label>
              <input v-model="form.brand" type="text" placeholder="z.B. Apple">
            </div>
            <div class="form-group">
              <label>Modell</label>
              <input v-model="form.model" type="text" placeholder="z.B. MacBook Pro">
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
              <select v-model="form.room_id" @change="form.box_id = ''">
                <option value="">–</option>
                <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.name }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Box</label>
              <select v-model="form.box_id" @change="form.room_id = ''">
                <option value="">–</option>
                <option v-for="b in boxes" :key="b.id" :value="b.id">{{ b.name }}</option>
              </select>
            </div>
          </div>

          <!-- Kaufpreis / Kaufdatum -->
          <div class="form-row">
            <div v-if="authStore.canViewKaufpreis" class="form-group">
              <label>Kaufpreis (CHF)</label>
              <input v-model="form.purchase_price" type="number" min="0" step="0.01">
            </div>
            <div class="form-group">
              <label>Kaufdatum</label>
              <input v-model="form.purchased_at" type="date">
            </div>
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
        </div>

        <!-- Footer -->
        <div class="modal-footer">
          <button v-if="itemId" class="btn btn-danger" :disabled="saving" @click="confirmDelete = true">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="3 6 5 6 21 6"></polyline>
              <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
              <path d="M10 11v6"></path><path d="M14 11v6"></path>
              <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
            </svg>
          </button>
          <button class="btn btn-secondary" @click="close" :disabled="saving">Abbrechen</button>
          <button class="btn btn-primary" :disabled="saving || !form.name" @click="save">
            {{ saving ? 'Wird gespeichert…' : 'Speichern' }}
          </button>
        </div>

        <!-- Delete confirmation overlay -->
        <div v-if="confirmDelete" class="confirm-overlay" @click.self="confirmDelete = false">
          <div class="confirm-dialog">
            <p>Gegenstand wirklich löschen?</p>
            <div class="confirm-actions">
              <button class="btn btn-secondary" @click="confirmDelete = false">Abbrechen</button>
              <button class="btn btn-danger" :disabled="saving" @click="deleteItem">
                {{ saving ? '…' : 'Löschen' }}
              </button>
            </div>
          </div>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { useToast } from 'vue-toastification'
import ImageGallery from '@/components/ImageGallery.vue'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  itemId: { type: [Number, String], default: null }
})
const emit = defineEmits(['close', 'saved'])

const toast = useToast()
const authStore = useAuthStore()

const loading = ref(false)
const saving = ref(false)
const confirmDelete = ref(false)
const categories = ref([])
const rooms = ref([])
const boxes = ref([])
const pendingFiles = ref([])

const emptyForm = () => ({
  name: '', description: '', category_id: '',
  brand: '', model: '', serial_number: '',
  quantity: 1, unit: '', condition: '',
  room_id: '', box_id: '',
  purchase_price: '', purchased_at: '', warranty_until: '',
  notes: '',
})
const form = ref(emptyForm())

onMounted(async () => {
  loading.value = true
  try {
    const requests = [
      api.get('/categories'),
      api.get('/rooms'),
      api.get('/boxes', { params: { per_page: 200 } }),
    ]
    if (props.itemId) requests.push(api.get(`/items/${props.itemId}`))

    const results = await Promise.all(requests)
    categories.value = results[0].data.data
    rooms.value = results[1].data.data
    boxes.value = results[2].data.data?.data ?? results[2].data.data

    if (props.itemId) {
      const item = results[3].data.data
      form.value = {
        name: item.name ?? '',
        description: item.description ?? '',
        category_id: item.category_id ?? '',
        brand: item.brand ?? '',
        model: item.model ?? '',
        serial_number: item.serial_number ?? '',
        quantity: item.quantity ?? 1,
        unit: item.unit ?? '',
        condition: item.condition ?? '',
        room_id: item.room_id ?? '',
        box_id: item.box_id ?? '',
        purchase_price: item.purchase_price ?? '',
        purchased_at: item.purchased_at ? item.purchased_at.substring(0, 10) : '',
        warranty_until: item.warranty_until ? item.warranty_until.substring(0, 10) : '',
        notes: item.notes ?? '',
      }
    }
  } catch {
    toast.error('Fehler beim Laden')
    emit('close')
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

async function save() {
  if (!form.value.name || saving.value) return
  saving.value = true
  try {
    let savedId = props.itemId
    if (props.itemId) {
      await api.put(`/items/${props.itemId}`, form.value)
      toast.success('Gespeichert')
    } else {
      const res = await api.post('/items', form.value)
      savedId = res.data.data?.id
      if (savedId && pendingFiles.value.length) {
        for (const pf of pendingFiles.value) {
          try {
            const compressed = await compressImage(pf.file)
            const fd = new FormData()
            fd.append('image', compressed)
            await api.post(`/items/${savedId}/images`, fd)
          } catch { /* einzelner Bild-Fehler überspringen */ }
        }
      }
      toast.success('Erstellt')
    }
    emit('saved', savedId)
  } catch {
    toast.error('Fehler beim Speichern')
  } finally {
    saving.value = false
    emit('close')
  }
}

function close() {
  if (saving.value) return
  emit('close')
}

async function deleteItem() {
  if (!props.itemId || saving.value) return
  saving.value = true
  try {
    await api.delete(`/items/${props.itemId}`)
    toast.success('Gegenstand gelöscht')
    emit('saved', null)
    emit('close')
  } catch {
    toast.error('Fehler beim Löschen')
  } finally {
    saving.value = false
    confirmDelete.value = false
  }
}
</script>

<style lang="scss" scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 500;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-sheet {
  position: relative;
  background: white;
  border-radius: 16px;
  width: 100%;
  max-width: 520px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.25rem 0;
  flex-shrink: 0;
}

.modal-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #111827;
}

.modal-close {
  width: 36px; height: 36px;
  border: none; background: #f3f4f6; border-radius: 8px;
  color: #6b7280; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  &:hover { background: #e5e7eb; }
}

.modal-loading {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6b7280;
  padding: 2rem;
}

.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 1rem 1.25rem;

  .form-group {
    margin-bottom: 0.875rem;

    label {
      display: block;
      font-size: 0.8rem;
      font-weight: 500;
      color: #374151;
      margin-bottom: 0.3rem;
    }

    input, select, textarea {
      width: 100%;
      padding: 0.5rem 0.75rem;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      font-size: 16px;
      background: white;
      font-family: inherit;

      &:focus {
        outline: none;
        border-color: #3b82f6;
      }
    }

    textarea { resize: vertical; min-height: 60px; }
  }

  .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
  }
}

.modal-footer {
  padding: 1rem 1.25rem;
  border-top: 1px solid #f3f4f6;
  display: flex;
  gap: 0.75rem;
  flex-shrink: 0;

  .btn { flex: 1; justify-content: center; }
}

/* Photo strip */
.photo-row { margin-bottom: 1rem; }

.photo-previews {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  align-items: flex-start;
}

.photo-thumb {
  position: relative;
  width: 72px; height: 72px;
  border-radius: 8px; overflow: hidden;
  flex-shrink: 0;

  img { width: 100%; height: 100%; object-fit: cover; display: block; }
}

.photo-remove {
  position: absolute; top: 2px; right: 2px;
  width: 20px; height: 20px; border-radius: 50%;
  background: rgba(239, 68, 68, 0.85); color: #fff;
  border: none; font-size: 0.8rem; cursor: pointer;
  display: flex; align-items: center; justify-content: center; padding: 0;
}

.photo-add-btn {
  width: 72px; height: 72px;
  border: 2px dashed #d1d5db;
  border-radius: 8px;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  gap: 4px; cursor: pointer; color: #6b7280; font-size: 0.7rem;
  flex-shrink: 0;
  transition: border-color 0.15s, color 0.15s;

  &:hover { border-color: #3b82f6; color: #3b82f6; }
}

/* Mobile: bottom sheet */
@media (max-width: 767px) {
  .modal-backdrop { align-items: flex-end; }

  .modal-sheet {
    border-radius: 20px 20px 0 0;
    max-height: 92vh;
    max-width: 100%;
  }

  .modal-header { padding: 1rem 1rem 0; }
  .modal-body { padding: 1rem; }

  .modal-footer {
    padding: 1rem;
    padding-bottom: calc(1rem + env(safe-area-inset-bottom, 0px));
  }

  .form-row { grid-template-columns: 1fr; }
}

.btn-danger {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  padding: 0.5rem 0.875rem;
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #fca5a5;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.15s;
  white-space: nowrap;

  &:hover:not(:disabled) { background: #fecaca; }
  &:disabled { opacity: 0.5; cursor: not-allowed; }
}

.confirm-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  border-radius: inherit;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: center;
}

.confirm-dialog {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin: 1rem;
  text-align: center;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);

  p {
    margin: 0 0 1.25rem;
    font-size: 1rem;
    font-weight: 500;
    color: #111827;
  }
}

.confirm-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: center;
}
</style>
