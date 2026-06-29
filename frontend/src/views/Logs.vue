<template>
  <div class="page">
    <div class="page-header">
      <h1>Login-Protokoll</h1>
    </div>
    <div v-if="loading" class="loading">Wird geladen...</div>
    <div v-else class="card">
      <table>
        <thead>
          <tr>
            <th>Zeit</th>
            <th>Benutzer</th>
            <th>IP</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="log in logs" :key="log.id">
            <td>{{ formatTime(log.login_at) }}</td>
            <td>{{ log.username }}</td>
            <td>{{ log.ip_address }}</td>
            <td><span class="badge" :class="log.success ? 'badge-success' : 'badge-primary'">{{ log.success ? 'OK' : 'Fehl' }}</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { useToast } from 'vue-toastification'
import { formatDateTime } from '@/utils/dateFormat'

const toast = useToast()
const logs = ref([])
const loading = ref(true)

function formatTime(date) {
  return formatDateTime(date)
}

onMounted(async () => {
  try {
    const res = await api.get('/logs/login')
    logs.value = res.data.data?.data || res.data.data
  } catch (e) {
    toast.error('Fehler beim Laden')
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.page { max-width: 1000px; margin: 0 auto; }
.page-header { margin-bottom: 1.5rem; }
.page-header h1 { font-size: 1.5rem; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #f3f4f6; }
th { font-size: 0.75rem; color: #6b7280; }
.loading { padding: 2rem; text-align: center; color: #6b7280; }
</style>