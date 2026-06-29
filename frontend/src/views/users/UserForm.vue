<template>
  <div class="page">
    <div class="page-header">
      <button class="btn btn-secondary btn-back" @click="$router.back()">← Zurück</button>
      <h1>{{ isEdit ? 'Benutzer bearbeiten' : 'Neuer Benutzer' }}</h1>
    </div>

    <div v-if="loading" class="loading">Wird geladen…</div>

    <template v-else>
      <div class="card form-card">
        <form @submit.prevent="save">

          <!-- Name -->
          <div class="form-group">
            <label>Name *</label>
            <input v-model="form.name" type="text" required placeholder="Vollständiger Name" />
          </div>

          <!-- Benutzername (read-only in edit) -->
          <div class="form-group">
            <label>Benutzername *</label>
            <input
              v-model="form.username"
              type="text"
              :required="!isEdit"
              :disabled="isEdit"
              :class="{ 'input-disabled': isEdit }"
              placeholder="benutzername"
              autocomplete="off"
            />
            <p v-if="isEdit" class="field-hint">Benutzername kann nicht geändert werden.</p>
          </div>

          <!-- E-Mail -->
          <div class="form-group">
            <label>E-Mail</label>
            <input v-model="form.email" type="email" placeholder="optional" autocomplete="off" />
          </div>

          <!-- Rolle (nur Admin) -->
          <div v-if="authStore.isAdmin" class="form-group">
            <label>Rolle *</label>
            <select v-model="form.role" required>
              <option value="admin">Admin</option>
              <option value="editor">Bearbeiter</option>
              <option value="viewer">Leser</option>
            </select>
          </div>

          <!-- Kategorie-Berechtigungen (nur Admin, nur für Viewer) -->
          <div v-if="authStore.isAdmin && form.role === 'viewer'" class="form-group">
            <label>Sichtbare Kategorien</label>
            <p class="field-hint">Leer = alle Kategorien sichtbar</p>
            <div class="category-checkboxes">
              <label
                v-for="category in categories"
                :key="category.id"
                class="category-checkbox-row"
              >
                <input type="checkbox" :value="category.id" v-model="selectedCategoryIds" />
                <span>{{ category.name }}</span>
              </label>
              <p v-if="categories.length === 0" class="field-hint" style="padding: 0.625rem 0.875rem; margin: 0;">Keine Kategorien vorhanden.</p>
            </div>
          </div>

          <!-- Kaufpreis sichtbar (nur Admin) -->
          <div v-if="authStore.isAdmin" class="form-group form-group--checkbox">
            <label class="checkbox-label">
              <input v-model="form.kaufpreis_sichtbar" type="checkbox" />
              <span>Kaufpreis sichtbar</span>
            </label>
            <p class="field-hint">Ob dieser Benutzer den Kaufpreis von Gegenständen sehen darf.</p>
          </div>

          <!-- Aktiv (nur Admin) -->
          <div v-if="authStore.isAdmin && isEdit" class="form-group form-group--checkbox">
            <label class="checkbox-label">
              <input v-model="form.active" type="checkbox" />
              <span>Aktiv</span>
            </label>
          </div>

          <!-- Passwort -->
          <div class="form-group">
            <label>Passwort {{ isEdit ? '(leer lassen = nicht ändern)' : '*' }}</label>
            <input
              v-model="form.password"
              type="password"
              :required="!isEdit"
              placeholder="Mindestens 8 Zeichen"
              autocomplete="new-password"
            />
          </div>

          <!-- Passwort bestätigen (nur wenn Passwort ausgefüllt) -->
          <div v-if="form.password" class="form-group">
            <label>Passwort bestätigen *</label>
            <input
              v-model="form.passwordConfirm"
              type="password"
              required
              placeholder="Passwort wiederholen"
              autocomplete="new-password"
            />
            <p v-if="passwordMismatch" class="field-error">Passwörter stimmen nicht überein.</p>
          </div>

          <div class="form-actions">
            <button type="button" class="btn btn-secondary" @click="$router.back()">Abbrechen</button>
            <button type="submit" class="btn btn-primary" :disabled="saving || passwordMismatch">
              {{ saving ? 'Wird gespeichert…' : (isEdit ? 'Speichern' : 'Erstellen') }}
            </button>
          </div>
        </form>
      </div>

      <!-- Danger zone (edit, admin only, not self) -->
      <div v-if="isEdit && authStore.isAdmin && !isSelf" class="danger-zone">
        <button type="button" class="btn-delete" @click="showDeleteConfirm = true">Benutzer löschen</button>
      </div>
    </template>

    <!-- Delete confirmation -->
    <div v-if="showDeleteConfirm" class="confirm-backdrop" @click.self="showDeleteConfirm = false">
      <div class="confirm-dialog">
        <p>Benutzer <strong>{{ form.name }}</strong> wirklich löschen?</p>
        <p class="confirm-hint">Diese Aktion kann nicht rückgängig gemacht werden.</p>
        <div class="confirm-actions">
          <button class="btn btn-secondary" @click="showDeleteConfirm = false">Abbrechen</button>
          <button class="btn-danger" :disabled="deleting" @click="doDelete">
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
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

const id = route.params.id
const isEdit = computed(() => !!id)
const isSelf = computed(() => authStore.user?.id == id)

const saving = ref(false)
const deleting = ref(false)
const loading = ref(!!id)
const showDeleteConfirm = ref(false)

const categories = ref([])
const selectedCategoryIds = ref([])

const form = ref({
  name: '',
  username: '',
  email: '',
  role: 'viewer',
  active: true,
  kaufpreis_sichtbar: false,
  password: '',
  passwordConfirm: '',
})

const passwordMismatch = computed(() =>
  !!form.value.password && form.value.password !== form.value.passwordConfirm
)

onMounted(async () => {
  if (authStore.isAdmin) {
    try {
      const catRes = await api.get('/categories')
      categories.value = catRes.data.data || catRes.data
    } catch {
      // ignore
    }
  }

  if (!id) return
  try {
    const [userRes, permRes] = await Promise.all([
      api.get(`/users/${id}`),
      authStore.isAdmin ? api.get(`/users/${id}/category-permissions`) : Promise.resolve(null),
    ])
    const u = userRes.data.data
    form.value = {
      name: u.name,
      username: u.username,
      email: u.email || '',
      role: u.role,
      active: u.active,
      kaufpreis_sichtbar: u.kaufpreis_sichtbar ?? false,
      password: '',
      passwordConfirm: '',
    }
    if (permRes) {
      const perms = permRes.data.data || permRes.data
      selectedCategoryIds.value = perms.map(c => c.id)
    }
  } catch {
    toast.error('Benutzer nicht gefunden')
    router.push({ name: 'Users' })
  } finally {
    loading.value = false
  }
})

async function save() {
  if (saving.value || passwordMismatch.value) return

  saving.value = true
  try {
    const payload = {
      name: form.value.name,
      email: form.value.email || null,
    }

    if (authStore.isAdmin) {
      payload.role = form.value.role
      payload.kaufpreis_sichtbar = form.value.kaufpreis_sichtbar
      if (isEdit.value) payload.active = form.value.active
    }

    if (!isEdit.value) {
      payload.username = form.value.username
      payload.password = form.value.password
    } else if (form.value.password) {
      payload.password = form.value.password
    }

    let userId = id
    if (isEdit.value) {
      await api.put(`/users/${id}`, payload)
    } else {
      const res = await api.post('/users', payload)
      userId = res.data.data?.id
    }

    if (authStore.isAdmin && form.value.role === 'viewer' && userId) {
      await api.put(`/users/${userId}/category-permissions`, {
        category_ids: selectedCategoryIds.value,
      })
    }

    toast.success(isEdit.value ? 'Benutzer aktualisiert' : 'Benutzer erstellt')
    router.push({ name: 'Users' })
  } catch {
    // interceptor shows error
  } finally {
    saving.value = false
  }
}

async function doDelete() {
  if (deleting.value) return
  deleting.value = true
  try {
    await api.delete(`/users/${id}`)
    toast.success('Benutzer gelöscht')
    router.push({ name: 'Users' })
  } catch {
    // interceptor shows error
  } finally {
    deleting.value = false
    showDeleteConfirm.value = false
  }
}
</script>

<style scoped>
.page { max-width: 600px; margin: 0 auto; }

.page-header {
  display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;
  h1 { font-size: 1.5rem; font-weight: 600; margin: 0; }
}
.btn-back { flex-shrink: 0; }

.form-card { padding: 1.5rem; }

.form-group { margin-bottom: 1rem; }
.form-group label {
  display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;
}
.form-group input,
.form-group select {
  width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 8px;
  font-size: 16px; background: white; font-family: inherit; box-sizing: border-box;
}
.form-group input:focus,
.form-group select:focus { outline: none; border-color: #3b82f6; }
.input-disabled { background: #f9fafb !important; color: #6b7280; cursor: not-allowed; }

.field-hint { font-size: 0.75rem; color: #9ca3af; margin: 0.2rem 0 0; }
.field-error { font-size: 0.75rem; color: #dc2626; margin: 0.2rem 0 0; }

.form-group--checkbox { margin-bottom: 1rem; }
.checkbox-label {
  display: flex; align-items: center; gap: 0.5rem;
  font-size: 0.875rem; color: #374151; cursor: pointer;
  input[type="checkbox"] { width: 16px; height: 16px; accent-color: #3b82f6; cursor: pointer; }
}

.category-checkboxes {
  display: flex;
  flex-direction: column;
  gap: 0;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  overflow: hidden;
}

.category-checkbox-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0.875rem;
  border-bottom: 1px solid #f3f4f6;
  cursor: pointer;
  font-size: 0.875rem;
  color: #374151;
  input[type="checkbox"] { width: 16px; height: 16px; accent-color: #3b82f6; cursor: pointer; flex-shrink: 0; }
  &:last-child { border-bottom: none; }
  &:hover { background: #f9fafb; }
}

.form-actions {
  display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;
}

.loading { padding: 2rem; text-align: center; color: #6b7280; }

.danger-zone {
  margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #fee2e2;
}
.btn-delete {
  width: 100%; padding: 0.75rem; background: none;
  border: 1px solid #ef4444; color: #ef4444;
  border-radius: 8px; font-size: 1rem; cursor: pointer; transition: background 0.15s;
  &:hover { background: #fef2f2; }
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
  p { margin: 0 0 0.5rem; font-size: 1rem; color: #111827; }
  p:last-of-type { margin-bottom: 1.25rem; }
}
.confirm-hint { font-size: 0.82rem !important; color: #9ca3af !important; }
.confirm-actions { display: flex; gap: 0.75rem; justify-content: center; }

@media (max-width: 767px) {
  .form-card { padding: 1rem; }
  .form-actions { flex-direction: column-reverse; }
  .form-actions .btn { width: 100%; justify-content: center; }
}
</style>
