<template>
  <div class="page">
    <div class="page-header">
      <h1>Einstellungen</h1>
    </div>

    <!-- Profil -->
    <div class="card form-card">
      <h2>Profil</h2>
      <form @submit.prevent="saveProfile">
        <div class="form-group">
          <label>Name</label>
          <input v-model="profile.name" type="text" />
        </div>
        <div class="form-group">
          <label>E-Mail</label>
          <input v-model="profile.email" type="email" placeholder="optional" />
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary" :disabled="savingProfile">
            {{ savingProfile ? 'Wird gespeichert…' : 'Speichern' }}
          </button>
        </div>
      </form>
    </div>

    <!-- Passwort ändern -->
    <div class="card form-card" style="margin-top: 1.5rem;">
      <h2>Passwort ändern</h2>
      <form @submit.prevent="changePassword">
        <div class="form-group">
          <label>Aktuelles Passwort *</label>
          <input v-model="pw.current_password" type="password" required autocomplete="current-password" />
        </div>
        <div class="form-group">
          <label>Neues Passwort *</label>
          <input v-model="pw.password" type="password" required placeholder="Mindestens 8 Zeichen" autocomplete="new-password" />
        </div>
        <div class="form-group">
          <label>Neues Passwort bestätigen *</label>
          <input v-model="pw.password_confirmation" type="password" required autocomplete="new-password" />
          <p v-if="pwMismatch" class="field-error">Passwörter stimmen nicht überein.</p>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary" :disabled="savingPw || pwMismatch">
            {{ savingPw ? 'Wird gespeichert…' : 'Passwort ändern' }}
          </button>
        </div>
      </form>
    </div>

    <!-- Backup & Wiederherstellung — nur Admin -->
    <div v-if="authStore.isAdmin" class="card form-card" style="margin-top: 1.5rem;">
      <h2>Backup &amp; Wiederherstellung</h2>

      <!-- Backup erstellen -->
      <div class="backup-section">
        <h3 class="section-title">Backup erstellen</h3>
        <p class="section-desc">
          Erstellt eine ZIP-Datei mit der Datenbank und allen gespeicherten Dateien (Bilder, Dokumente).
        </p>
        <button class="btn btn-primary" :disabled="creatingBackup" @click="createBackup">
          <span v-if="creatingBackup" class="spinner" />
          {{ creatingBackup ? 'Backup wird erstellt…' : 'Backup erstellen &amp; herunterladen' }}
        </button>
      </div>

      <div class="section-divider" />

      <!-- Backup wiederherstellen -->
      <div class="restore-section">
        <h3 class="section-title">Backup wiederherstellen</h3>

        <div class="warning-box">
          <strong>Achtung:</strong> Die Wiederherstellung überschreibt alle aktuellen Daten
          unwiderruflich. Vor der Wiederherstellung wird automatisch ein Sicherungs-Backup
          des aktuellen Stands erstellt.
        </div>

        <div class="form-group" style="margin-top: 1rem;">
          <label>Backup-Datei (.zip)</label>
          <input
            ref="fileInput"
            type="file"
            accept=".zip"
            class="file-input"
            @change="onFileSelect"
          />
        </div>

        <div v-if="loadingPreview" class="preview-loading">
          <span class="spinner" /> Lese Backup-Informationen…
        </div>

        <div v-if="previewData" class="manifest-preview">
          <h4 class="preview-title">Backup-Informationen</h4>
          <table class="manifest-table">
            <tbody>
              <tr>
                <td>Erstellt am</td>
                <td>{{ formatDate(previewData.manifest.created_at) }}</td>
              </tr>
              <tr>
                <td>App-Version</td>
                <td>
                  {{ previewData.manifest.app_version }}
                  <span v-if="!previewData.version_match" class="badge-warn">
                    ⚠️ Aktuelle Version: {{ previewData.current_version }}
                  </span>
                </td>
              </tr>
              <tr>
                <td>Räume</td>
                <td>{{ previewData.manifest.counts?.rooms ?? '—' }}</td>
              </tr>
              <tr>
                <td>Boxen</td>
                <td>{{ previewData.manifest.counts?.boxes ?? '—' }}</td>
              </tr>
              <tr>
                <td>Gegenstände</td>
                <td>{{ previewData.manifest.counts?.items ?? '—' }}</td>
              </tr>
              <tr>
                <td>PHP / Laravel</td>
                <td>{{ previewData.manifest.php_version }} / {{ previewData.manifest.laravel_version }}</td>
              </tr>
            </tbody>
          </table>

          <div v-if="!previewData.version_match" class="warning-box" style="margin-top: 0.75rem;">
            Die Backup-Version weicht von der aktuellen App-Version ab. Die Wiederherstellung
            könnte zu Problemen führen.
          </div>

          <div class="form-group" style="margin-top: 1.25rem;">
            <label>Zum Bestätigen <strong>"WIEDERHERSTELLEN"</strong> eingeben</label>
            <input
              v-model="restoreConfirm"
              type="text"
              placeholder="WIEDERHERSTELLEN"
              class="confirm-input"
              autocomplete="off"
            />
          </div>

          <button
            class="btn btn-danger"
            :disabled="restoring || restoreConfirm !== 'WIEDERHERSTELLEN'"
            @click="doRestore"
          >
            <span v-if="restoring" class="spinner" />
            {{ restoring ? 'Wird wiederhergestellt…' : 'Jetzt wiederherstellen' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'

const toast = useToast()
const authStore = useAuthStore()

// ── Profile ──────────────────────────────────────────────────────────────────
const profile = ref({ name: '', email: '' })
const savingProfile = ref(false)

const pw = ref({ current_password: '', password: '', password_confirmation: '' })
const savingPw = ref(false)

const pwMismatch = computed(() =>
  !!pw.value.password && pw.value.password !== pw.value.password_confirmation
)

onMounted(() => {
  if (authStore.user) {
    profile.value.name = authStore.user.name
    profile.value.email = authStore.user.email || ''
  }
})

async function saveProfile() {
  if (savingProfile.value) return
  savingProfile.value = true
  try {
    const res = await api.put('/profile', { name: profile.value.name, email: profile.value.email || null })
    authStore.user = res.data.data
    toast.success('Profil gespeichert')
  } catch {
    // interceptor shows error
  } finally {
    savingProfile.value = false
  }
}

async function changePassword() {
  if (savingPw.value || pwMismatch.value) return
  savingPw.value = true
  try {
    await api.put('/profile', {
      current_password: pw.value.current_password,
      password: pw.value.password,
      password_confirmation: pw.value.password_confirmation,
    })
    toast.success('Passwort geändert')
    pw.value = { current_password: '', password: '', password_confirmation: '' }
  } catch {
    // interceptor shows error
  } finally {
    savingPw.value = false
  }
}

// ── Backup ───────────────────────────────────────────────────────────────────
const creatingBackup = ref(false)

async function createBackup() {
  if (creatingBackup.value) return
  creatingBackup.value = true
  try {
    const res = await api.post('/admin/backup/create', {}, { responseType: 'blob' })
    const url = URL.createObjectURL(new Blob([res.data], { type: 'application/zip' }))
    const a = document.createElement('a')
    a.href = url
    const ts = new Date().toISOString().replace(/[T:]/g, '-').replace(/\..+/, '').replace(/-$/, '')
    a.download = `backup_${ts}.zip`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
    toast.success('Backup erfolgreich erstellt und heruntergeladen')
  } catch {
    // interceptor shows error
  } finally {
    creatingBackup.value = false
  }
}

// ── Restore ──────────────────────────────────────────────────────────────────
const fileInput = ref(null)
const selectedFile = ref(null)
const loadingPreview = ref(false)
const previewData = ref(null)
const restoreConfirm = ref('')
const restoring = ref(false)

async function onFileSelect(event) {
  const file = event.target.files?.[0]
  if (!file) return

  selectedFile.value = file
  previewData.value = null
  restoreConfirm.value = ''
  loadingPreview.value = true

  try {
    const form = new FormData()
    form.append('file', file)
    const res = await api.post('/admin/backup/preview', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    previewData.value = res.data.data
  } catch {
    selectedFile.value = null
    if (fileInput.value) fileInput.value.value = ''
    // interceptor shows error
  } finally {
    loadingPreview.value = false
  }
}

async function doRestore() {
  if (restoring.value || restoreConfirm.value !== 'WIEDERHERSTELLEN' || !selectedFile.value) return
  restoring.value = true

  try {
    const form = new FormData()
    form.append('file', selectedFile.value)
    form.append('confirm', restoreConfirm.value)
    await api.post('/admin/backup/restore', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
      timeout: 300000,
    })
    toast.success('Wiederherstellung erfolgreich abgeschlossen')
    previewData.value = null
    selectedFile.value = null
    restoreConfirm.value = ''
    if (fileInput.value) fileInput.value.value = ''
  } catch {
    // interceptor shows error
  } finally {
    restoring.value = false
  }
}

function formatDate(iso) {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleString('de-CH', {
      day: '2-digit', month: '2-digit', year: 'numeric',
      hour: '2-digit', minute: '2-digit',
    })
  } catch {
    return iso
  }
}
</script>

<style scoped>
.page { max-width: 600px; margin: 0 auto; }
.page-header { margin-bottom: 1.5rem; }
.page-header h1 { font-size: 1.5rem; font-weight: 600; margin: 0; }

.form-card { padding: 1.5rem; }
.form-card h2 { font-size: 1rem; font-weight: 600; margin: 0 0 1rem; color: #374151; }

.form-group { margin-bottom: 1rem; }
.form-group label {
  display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;
}
.form-group input {
  width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 8px;
  font-size: 16px; background: white; font-family: inherit; box-sizing: border-box;
}
.form-group input:focus { outline: none; border-color: #3b82f6; }

.field-error { font-size: 0.75rem; color: #dc2626; margin: 0.2rem 0 0; }

.form-actions { display: flex; justify-content: flex-end; margin-top: 1.25rem; }

/* Backup section */
.section-title { font-size: 0.9rem; font-weight: 600; color: #374151; margin: 0 0 0.4rem; }
.section-desc { font-size: 0.82rem; color: #6b7280; margin: 0 0 0.9rem; }

.section-divider { border: none; border-top: 1px solid #e5e7eb; margin: 1.5rem 0; }

.warning-box {
  background: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px;
  padding: 0.75rem 1rem; font-size: 0.82rem; color: #78350f; line-height: 1.5;
}

.file-input {
  display: block; font-size: 0.85rem; cursor: pointer;
  padding: 0.4rem 0;
}

.preview-loading {
  display: flex; align-items: center; gap: 0.5rem;
  font-size: 0.85rem; color: #6b7280; margin: 0.75rem 0;
}

.manifest-preview {
  background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px;
  padding: 1rem; margin-top: 0.75rem;
}

.preview-title { font-size: 0.85rem; font-weight: 600; color: #374151; margin: 0 0 0.6rem; }

.manifest-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
.manifest-table td { padding: 0.3rem 0.5rem; vertical-align: top; }
.manifest-table td:first-child { color: #6b7280; font-weight: 500; width: 45%; }
.manifest-table td:last-child { color: #111827; }

.badge-warn {
  display: inline-block; background: #fef3c7; color: #92400e;
  border-radius: 4px; padding: 0.1rem 0.4rem; font-size: 0.75rem; margin-left: 0.4rem;
}

.confirm-input { font-family: monospace !important; }

.btn { display: inline-flex; align-items: center; gap: 0.4rem; }
.btn-danger {
  background: #dc2626; color: white; border: none; padding: 0.5rem 1rem;
  border-radius: 8px; font-size: 0.875rem; font-weight: 500; cursor: pointer;
}
.btn-danger:hover:not(:disabled) { background: #b91c1c; }
.btn-danger:disabled { opacity: 0.5; cursor: not-allowed; }

.spinner {
  display: inline-block; width: 14px; height: 14px;
  border: 2px solid rgba(255,255,255,0.4); border-top-color: white;
  border-radius: 50%; animation: spin 0.7s linear infinite; flex-shrink: 0;
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>
