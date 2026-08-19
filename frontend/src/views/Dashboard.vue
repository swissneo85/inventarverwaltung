<template>
  <div class="dashboard">
    <!-- Inbox Alert -->
    <div v-if="inboxCount > 0" class="inbox-alert">
      <div class="alert-content">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M22 12h-6l-2 3h-4l-2-3H2"></path>
          <path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path>
        </svg>
        <div>
          <strong>Inbox nicht leer!</strong>
          <p>{{ inboxCount }} Element{{ inboxCount > 1 ? 'e' : '' }} warten auf Zuordnung</p>
        </div>
      </div>
      <router-link to="/inbox" class="btn-secondary">
        Zur Inbox →
      </router-link>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <router-link to="/items" class="stat-card">
        <div class="stat-icon items">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-value">{{ stats.items?.total || 0 }}</span>
          <span class="stat-label">Gegenstände</span>
        </div>
      </router-link>

      <router-link to="/boxes" class="stat-card">
        <div class="stat-icon boxes">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="3" y1="9" x2="21" y2="9"></line>
            <line x1="9" y1="21" x2="9" y2="9"></line>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-value">{{ stats.boxes?.total || 0 }}</span>
          <span class="stat-label">Boxen</span>
        </div>
      </router-link>

      <router-link to="/rooms" class="stat-card">
        <div class="stat-icon rooms">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-value">{{ stats.rooms || 0 }}</span>
          <span class="stat-label">Räume</span>
        </div>
      </router-link>

      <router-link to="/categories" class="stat-card">
        <div class="stat-icon categories">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-value">{{ stats.categories || 0 }}</span>
          <span class="stat-label">Kategorien</span>
        </div>
      </router-link>
    </div>

    <!-- Warranty Alert -->
    <div v-if="warrantyExpiring > 0" class="warranty-alert">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
        <line x1="12" y1="9" x2="12" y2="13"></line>
        <line x1="12" y1="17" x2="12.01" y2="17"></line>
      </svg>
      <span>
        <strong>Garantie-Warnung:</strong> 
        {{ warrantyExpiring }} Gegenstände haben in den nächsten 30 Tagen ablaufende Garantien
      </span>
      <router-link to="/items?quick=warranty_expiring" class="alert-link">
        Ansehen →
      </router-link>
    </div>

    <!-- Quick Actions -->
    <div class="section">
      <h2 class="section-title">Schnellaktionen</h2>
      <div class="quick-actions">
        <router-link to="/items?new=1" class="action-card">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          <span>Neuer Gegenstand</span>
        </router-link>
        
        <router-link to="/scan" class="action-card">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2v11z"></path>
            <circle cx="12" cy="13" r="4"></circle>
          </svg>
          <span>QR-Code scannen</span>
        </router-link>
        
      </div>
    </div>

    <!-- Recent Items -->
    <div class="section">
      <div class="section-header">
        <h2 class="section-title">Letzte Gegenstände</h2>
        <router-link to="/items" class="link">Alle anzeigen →</router-link>
      </div>
      <div class="items-list">
        <div v-if="loading" class="loading">Wird geladen...</div>
        <div v-else-if="recentItems.length === 0" class="empty">
          Noch keine Gegenstände angelegt
        </div>
        <ItemCard
          v-else
          v-for="item in recentItems"
          :key="item.id"
          :item="item"
          @edit="openEdit"
        />
      </div>
    </div>
  </div>

  <ItemModal
    v-if="editModal.open"
    :item-id="editModal.itemId"
    @close="editModal.open = false"
    @saved="onSaved"
  />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import ItemCard from '@/components/ItemCard.vue'
import ItemModal from '@/components/ItemModal.vue'

const stats = ref({})
const recentItems = ref([])
const loading = ref(true)
const inboxCount = ref(0)
const warrantyExpiring = ref(0)
const editModal = ref({ open: false, itemId: null })

function openEdit(itemId) {
  editModal.value = { open: true, itemId }
}

async function onSaved() {
  const recentRes = await api.get('/dashboard/recent?limit=5')
  recentItems.value = recentRes.data.data
}

onMounted(async () => {
  try {
    const [statsRes, recentRes, inboxRes] = await Promise.all([
      api.get('/dashboard/stats'),
      api.get('/dashboard/recent?limit=5'),
      api.get('/dashboard/inbox'),
    ])

    stats.value = statsRes.data.data
    recentItems.value = recentRes.data.data
    inboxCount.value = inboxRes.data.data.items_count + inboxRes.data.data.boxes_count

    if (stats.value.alerts?.warranty_expiring) {
      warrantyExpiring.value = stats.value.alerts.warranty_expiring
    }
  } catch (error) {
    console.error('Fehler beim Laden:', error)
  } finally {
    loading.value = false
  }
})
</script>

<style lang="scss" scoped>
.dashboard {
  max-width: 1200px;
  margin: 0 auto;
}

.inbox-alert {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  background: #fef3c7;
  border: 1px solid #f59e0b;
  border-radius: 12px;
  margin-bottom: 1.5rem;
  
  .alert-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    
    svg {
      color: #f59e0b;
    }
    
    strong {
      display: block;
      color: #92400e;
    }
    
    p {
      margin: 0;
      color: #b45309;
      font-size: 0.875rem;
    }
  }
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  text-decoration: none;
  color: inherit;
  transition: all 0.2s;

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  }
  
  .stat-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    
    &.items { background: #dbeafe; color: #3b82f6; }
    &.boxes { background: #dcfce7; color: #22c55e; }
    &.rooms { background: #fef3c7; color: #f59e0b; }
    &.categories { background: #fce7f3; color: #ec4899; }
  }
  
  .stat-info {
    display: flex;
    flex-direction: column;
  }
  
  .stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
  }
  
  .stat-label {
    font-size: 0.875rem;
    color: #6b7280;
  }
}

.warranty-alert {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  background: #fef2f2;
  border: 1px solid #ef4444;
  border-radius: 12px;
  margin-bottom: 1.5rem;
  color: #991b1b;
  
  .alert-link {
    margin-left: auto;
    color: #dc2626;
    font-weight: 500;
    
    &:hover {
      text-decoration: underline;
    }
  }
}

.section {
  margin-bottom: 2rem;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.section-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.link {
  color: #3b82f6;
  text-decoration: none;
  font-size: 0.875rem;
  
  &:hover {
    text-decoration: underline;
  }
}

.quick-actions {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
}

.action-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 1.5rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  text-decoration: none;
  color: #374151;
  transition: all 0.2s;
  
  svg {
    color: #3b82f6;
  }
  
  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    color: #3b82f6;
  }
}

.items-list {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
  overflow: hidden;
}

.loading, .empty {
  padding: 2rem;
  text-align: center;
  color: #6b7280;
}

.btn-secondary {
  padding: 0.5rem 1rem;
  background: white;
  border: 1px solid #f59e0b;
  color: #f59e0b;
  border-radius: 8px;
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s;
  
  &:hover {
    background: #fffbeb;
  }
}

@media (max-width: 768px) {
  .inbox-alert {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;

    .btn-secondary {
      text-align: center;
    }
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .quick-actions {
    grid-template-columns: repeat(2, 1fr);
  }

  .action-card {
    padding: 1rem;
  }

  .warranty-alert {
    flex-wrap: wrap;

    .alert-link {
      margin-left: 0;
      width: 100%;
    }
  }
}
</style>