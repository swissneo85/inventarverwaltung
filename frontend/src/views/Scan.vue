<template>
  <div class="scan-page">
    <div class="page-header">
      <h1>QR-Code scannen</h1>
      <p>Scannen Sie einen QR-Code oder geben Sie die ID manuell ein.</p>
    </div>

    <!-- Camera Scanner -->
    <div class="card scan-card">
      <h2>Kamera-Scanner</h2>

      <div v-if="!cameraSupported" class="https-warning">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <span>Kamerazugriff erfordert HTTPS. Bitte manuelle Eingabe verwenden.</span>
      </div>

      <div v-else class="camera-container">
        <div class="camera-wrapper">
          <video ref="videoEl" autoplay playsinline muted class="camera-video"></video>
          <canvas ref="canvasEl" class="camera-canvas"></canvas>
          <div class="scan-overlay">
            <div class="scan-frame"></div>
          </div>
        </div>
        <div class="camera-controls">
          <button v-if="!cameraActive" class="btn-primary" @click="startCamera" :disabled="cameraLoading">
            {{ cameraLoading ? 'Wird gestartet...' : 'Kamera starten' }}
          </button>
          <button v-else class="btn-secondary" @click="stopCamera">Kamera stoppen</button>
        </div>
        <div v-if="cameraError" class="camera-error">{{ cameraError }}</div>
      </div>
    </div>

    <!-- Manual Input -->
    <div class="card scan-card">
      <h2>Manuelle Eingabe</h2>
      <p>Geben Sie die ID ein (z.B. I123, B45, R12)</p>

      <form @submit.prevent="handleScan" class="scan-form">
        <div class="form-group">
          <input
            v-model="scanCode"
            type="text"
            placeholder="z.B. I123 oder QR-Token"
            class="scan-input"
            @keydown.enter.prevent="handleScan"
          />
        </div>
        <button type="submit" class="btn-primary" :disabled="loading || !scanCode">
          <span v-if="loading">Suchen...</span>
          <span v-else>Suchen</span>
        </button>
      </form>
    </div>

    <!-- Result (above recent scans) -->
    <div v-if="result" class="result-card card">
      <div class="result-header">
        <span class="result-type">{{ result.type }}</span>
        <h2>{{ result.data.name }}</h2>
      </div>

      <div class="result-details">
        <div class="detail-row">
          <span class="label">ID:</span>
          <span class="value">{{ result.display_id }}</span>
        </div>
        <div v-if="result.data.category" class="detail-row">
          <span class="label">Kategorie:</span>
          <span class="value">{{ result.data.category?.name }}</span>
        </div>
        <div v-if="result.data.location" class="detail-row">
          <span class="label">Standort:</span>
          <span class="value">{{ result.data.location }}</span>
        </div>
      </div>

      <div class="result-actions">
        <router-link :to="result.url" class="btn-primary">Details öffnen</router-link>
        <router-link :to="result.editUrl" class="btn-secondary">Bearbeiten</router-link>
      </div>
    </div>

    <!-- Recent Scans -->
    <div class="card recent-scans">
      <div class="recent-header">
        <h3>Letzte Scans</h3>
        <button v-if="recentScans.length > 0" class="btn-clear" @click="clearRecent">Löschen</button>
      </div>
      <div v-if="recentScans.length === 0" class="empty">Noch keine Scans durchgeführt</div>
      <div v-else class="scan-list">
        <div v-for="scan in recentScans" :key="scan.id" class="scan-item" @click="goToItem(scan)">
          <span class="scan-type">{{ scan.type }}</span>
          <span class="scan-name">{{ scan.name }}</span>
          <span class="scan-time">{{ formatTime(scan.time) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '@/services/api'
import { useToast } from 'vue-toastification'
import jsQR from 'jsqr'

const router = useRouter()
const route = useRoute()
const toast = useToast()

const scanCode = ref('')
const loading = ref(false)
const result = ref(null)
const recentScans = ref([])

const videoEl = ref(null)
const canvasEl = ref(null)
const cameraActive = ref(false)
const cameraLoading = ref(false)
const cameraError = ref('')
const cameraSupported = ref(true)

let stream = null
let rafId = null
let lastScanned = ''

onMounted(async () => {
  cameraSupported.value = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia)

  if (route.params.token) {
    scanCode.value = route.params.token
    await handleScan()
  }

  const saved = localStorage.getItem('recentScans')
  if (saved) {
    try { recentScans.value = JSON.parse(saved) } catch {}
  }
})

onUnmounted(() => {
  stopCamera()
})

async function startCamera() {
  cameraLoading.value = true
  cameraError.value = ''
  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } }
    })
    videoEl.value.srcObject = stream
    await videoEl.value.play()
    cameraActive.value = true
    scanLoop()
  } catch (err) {
    cameraError.value = 'Kamerazugriff verweigert: ' + (err.message || err)
  } finally {
    cameraLoading.value = false
  }
}

function stopCamera() {
  if (rafId) { cancelAnimationFrame(rafId); rafId = null }
  if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null }
  cameraActive.value = false
}

function scanLoop() {
  if (!cameraActive.value || !videoEl.value || !canvasEl.value) return

  const video = videoEl.value
  const canvas = canvasEl.value

  if (video.readyState === video.HAVE_ENOUGH_DATA) {
    canvas.width = video.videoWidth
    canvas.height = video.videoHeight
    const ctx = canvas.getContext('2d')
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height)
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height)
    const code = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: 'dontInvert' })

    if (code && code.data && code.data !== lastScanned) {
      lastScanned = code.data
      handleQrResult(code.data)
    }
  }

  rafId = requestAnimationFrame(scanLoop)
}

async function handleQrResult(data) {
  scanCode.value = data
  await handleScan()
  setTimeout(() => { lastScanned = '' }, 3000)
}

async function handleScan() {
  if (!scanCode.value) return

  loading.value = true
  result.value = null

  try {
    const displayMatch = scanCode.value.trim().match(/^([RBI])(\d+)$/i)

    if (displayMatch) {
      const type = displayMatch[1].toUpperCase()
      const id = displayMatch[2]
      await handleDisplayId(type, id)
    } else {
      const pathMatch = scanCode.value.match(/\/(rooms|boxes|items)\/(\d+)/)
      if (pathMatch) {
        const typeMap = { rooms: 'R', boxes: 'B', items: 'I' }
        await handleDisplayId(typeMap[pathMatch[1]], pathMatch[2])
      } else {
        const response = await api.post('/scan', { token: scanCode.value })
        result.value = formatResult(response.data.data)
        addToRecent(result.value)
      }
    }
  } catch {
    toast.error('Nicht gefunden')
  } finally {
    loading.value = false
  }
}

async function handleDisplayId(type, id) {
  let response

  if (type === 'I') {
    response = await api.get(`/items/${id}`)
    result.value = { type: 'Gegenstand', display_id: `I${id}`, data: response.data.data, url: `/items/${id}`, editUrl: `/items/${id}/edit` }
  } else if (type === 'B') {
    response = await api.get(`/boxes/${id}`)
    result.value = { type: 'Box', display_id: `B${id}`, data: response.data.data, url: `/boxes/${id}`, editUrl: `/boxes/${id}` }
  } else if (type === 'R') {
    response = await api.get(`/rooms/${id}`)
    result.value = { type: 'Raum', display_id: `R${id}`, data: response.data.data, url: `/rooms/${id}`, editUrl: `/rooms/${id}` }
  }

  if (result.value) {
    result.value.name = result.value.data.name
    addToRecent(result.value)
  }
}

function formatResult(data) {
  const typeMap = {
    item: { type: 'Gegenstand', prefix: 'I' },
    box: { type: 'Box', prefix: 'B' },
    room: { type: 'Raum', prefix: 'R' },
  }
  const { type, prefix } = typeMap[data.type] || { type: 'Unbekannt', prefix: '' }
  return {
    type,
    display_id: `${prefix}${data.data.id}`,
    name: data.data.name,
    data: data.data,
    url: `/${data.type === 'item' ? 'items' : data.type === 'box' ? 'boxes' : 'rooms'}/${data.data.id}`,
    editUrl: `/${data.type === 'item' ? 'items' : data.type === 'box' ? 'boxes' : 'rooms'}/${data.data.id}/edit`,
  }
}

function addToRecent(item) {
  const scan = { ...item, id: Date.now(), time: new Date().toISOString() }
  recentScans.value.unshift(scan)
  recentScans.value = recentScans.value.slice(0, 10)
  localStorage.setItem('recentScans', JSON.stringify(recentScans.value))
}

function clearRecent() {
  recentScans.value = []
  localStorage.removeItem('recentScans')
}

function formatTime(isoString) {
  return new Date(isoString).toLocaleTimeString('de-CH', { hour: '2-digit', minute: '2-digit' })
}

function goToItem(scan) {
  router.push(scan.url)
}
</script>

<style lang="scss" scoped>
.scan-page {
  max-width: 600px;
  margin: 0 auto;
}

.page-header {
  text-align: center;
  margin-bottom: 2rem;

  h1 { font-size: 1.5rem; font-weight: 600; margin: 0 0 0.5rem; }
  p { color: #6b7280; margin: 0; }
}

.scan-card {
  padding: 1.5rem;
  margin-bottom: 1.5rem;

  h2 { font-size: 1.125rem; font-weight: 600; margin: 0 0 0.5rem; }
  p { color: #6b7280; margin: 0 0 1rem; }
}

/* Camera */
.camera-container { display: flex; flex-direction: column; gap: 1rem; }

.camera-wrapper {
  position: relative;
  width: 100%;
  background: #000;
  border-radius: 8px;
  overflow: hidden;
  aspect-ratio: 4/3;
}

.camera-video {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.camera-canvas {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  pointer-events: none;
}

.scan-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0,0,0,0.4);
}

.scan-frame {
  width: 60%;
  aspect-ratio: 1;
  border: 3px solid #22c55e;
  border-radius: 12px;
  box-shadow: 0 0 0 9999px rgba(0,0,0,0.4);
}

.camera-controls { display: flex; justify-content: center; }

.camera-error {
  color: #dc2626;
  font-size: 0.875rem;
  text-align: center;
}

.https-warning {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: #fef3c7;
  border: 1px solid #f59e0b;
  border-radius: 8px;
  color: #92400e;
  font-size: 0.875rem;
}

/* Manual input */
.scan-form { display: flex; gap: 0.75rem; }

.scan-input {
  flex: 1;
  padding: 0.75rem 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;

  &:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }
}

/* Result */
.result-card {
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  border-left: 4px solid #3b82f6;

  .result-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;

    .result-type {
      padding: 0.25rem 0.75rem;
      background: #dbeafe;
      color: #1e40af;
      border-radius: 4px;
      font-size: 0.75rem;
      font-weight: 500;
    }

    h2 { font-size: 1.25rem; font-weight: 600; margin: 0; }
  }

  .result-details { margin-bottom: 1.5rem; }

  .detail-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f3f4f6;

    &:last-child { border-bottom: none; }
    .label { color: #6b7280; }
    .value { font-weight: 500; }
  }

  .result-actions { display: flex; gap: 0.75rem; }
}

/* Recent scans */
.recent-scans {
  padding: 1.5rem;
  margin-bottom: 1.5rem;

  .recent-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }

  h3 { font-size: 1rem; font-weight: 600; margin: 0; }

  .btn-clear {
    background: none;
    border: none;
    color: #6b7280;
    font-size: 0.8rem;
    cursor: pointer;
    padding: 0.25rem 0.5rem;

    &:hover { color: #dc2626; }
  }

  .empty { color: #9ca3af; text-align: center; padding: 1rem; }

  .scan-list { display: flex; flex-direction: column; gap: 0.5rem; }

  .scan-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.15s;

    &:hover { background: #f3f4f6; }
  }

  .scan-type {
    padding: 0.25rem 0.5rem;
    background: #dbeafe;
    color: #1e40af;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
  }

  .scan-name { flex: 1; font-weight: 500; }
  .scan-time { color: #9ca3af; font-size: 0.875rem; }
}
</style>
