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
        <!-- Kamera nicht aktiv: grosser Start-Button -->
        <div v-if="!cameraActive" class="scanner-start">
          <button @click="startCamera" class="btn-scan-start" :disabled="cameraLoading">
            📷 {{ cameraLoading ? 'Wird gestartet…' : 'Kamera starten' }}
          </button>
        </div>

        <!-- Kamera aktiv: Video + Stop-Button -->
        <div v-else class="scanner-active">
          <video ref="videoEl" autoplay playsinline muted class="scanner-video"></video>
          <canvas ref="canvasEl" class="camera-canvas"></canvas>
          <div class="scanner-overlay">
            <div class="scanner-frame"></div>
          </div>
          <button @click="stopCamera" class="btn-scan-stop">✕ Stoppen</button>
        </div>

        <div v-if="cameraError" class="camera-error">{{ cameraError }}</div>
        <div v-if="scanError" class="camera-error">{{ scanError }}</div>
      </div>
    </div>

    <!-- Manual Input -->
    <div class="card scan-card">
      <h2>Manuelle Eingabe</h2>
      <p>Geben Sie die ID ein (z.B. I123, B45, R12)</p>

      <form @submit.prevent="handleScan(scanCode)" class="scan-form">
        <div class="form-group">
          <input
            v-model="scanCode"
            type="text"
            placeholder="z.B. I123 oder QR-Token"
            class="scan-input"
            @keydown.enter.prevent="handleScan"
          />
        </div>
        <button type="submit" class="btn-primary" :disabled="!scanCode">Suchen</button>
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
import jsQR from 'jsqr'

const router = useRouter()
const route = useRoute()

const scanCode = ref('')
const result = ref(null)
const scanError = ref('')
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
    cameraActive.value = true

    // iOS Safari braucht mehr Zeit als nextTick um das video-Element zu mounten
    await new Promise(resolve => setTimeout(resolve, 100))

    if (!videoEl.value) {
      cameraError.value = 'Kamera konnte nicht initialisiert werden'
      stopCamera()
      return
    }

    videoEl.value.srcObject = stream
    await videoEl.value.play()
    scanLoop()
  } catch (err) {
    cameraActive.value = false
    if (err.name === 'NotAllowedError') {
      cameraError.value = 'Kamera-Zugriff verweigert — bitte in den Safari-Einstellungen erlauben'
    } else {
      cameraError.value = 'Kamera-Fehler: ' + (err.message || err)
    }
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
  await handleScan(data)
  setTimeout(() => { lastScanned = '' }, 3000)
}

async function handleScan(data) {
  const trimmed = (data ?? scanCode.value ?? '').trim()
  if (!trimmed) return

  scanError.value = ''

  // Format: I1, I23, i1 etc.
  const itemMatch = trimmed.match(/^[Ii](\d+)$/)
  if (itemMatch) {
    router.push({ name: 'ItemDetail', params: { id: itemMatch[1] } })
    return
  }

  // Format: B1, B23 etc.
  const boxMatch = trimmed.match(/^[Bb](\d+)$/)
  if (boxMatch) {
    router.push({ name: 'BoxDetail', params: { id: boxMatch[1] } })
    return
  }

  // Format: R1, R23 etc.
  const roomMatch = trimmed.match(/^[Rr](\d+)$/)
  if (roomMatch) {
    router.push({ name: 'RoomDetail', params: { id: roomMatch[1] } })
    return
  }

  // URL Format: https://inventar.buettler.org/items/1
  try {
    const url = new URL(trimmed)
    router.push(url.pathname)
    return
  } catch {}

  scanError.value = 'Unbekannter QR-Code: ' + trimmed
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

.scanner-start {
  padding: 0.5rem 0;
}

.btn-scan-start {
  width: 100%;
  padding: 1.25rem;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 0.75rem;
  font-size: 1.1rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;

  &:disabled { opacity: 0.6; cursor: not-allowed; }
  &:hover:not(:disabled) { background: #2563eb; }
}

.scanner-active {
  position: relative;
  width: 100%;
  background: #000;
  border-radius: 8px;
  overflow: hidden;
  aspect-ratio: 4/3;
}

.scanner-video {
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

.scanner-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0,0,0,0.4);
}

.scanner-frame {
  width: 60%;
  aspect-ratio: 1;
  border: 3px solid #22c55e;
  border-radius: 12px;
  box-shadow: 0 0 0 9999px rgba(0,0,0,0.4);
}

.btn-scan-stop {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  padding: 0.4rem 1rem;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 0.5rem;
  cursor: pointer;
  font-size: 0.875rem;

  &:hover { background: #dc2626; }
}

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
