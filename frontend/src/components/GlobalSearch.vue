<template>
  <div class="global-search" ref="searchRef">
    <div class="search-input-wrapper">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      </svg>
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Suchen... (R/B/I + Nummer)"
        class="search-input"
        @input="handleSearch"
        @keydown.esc="closeResults"
        @focus="showResults = true"
      />
      <button v-if="searchQuery" class="clear-btn" @click="clearSearch">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>
    
    <div v-if="showResults && (searchQuery || results.items?.length || results.boxes?.length || results.rooms?.length)" class="search-results">
      <div v-if="loading" class="search-loading">
        Suche...
      </div>
      
      <template v-else>
        <!-- Display ID Match -->
        <div v-if="displayIdMatch" class="search-section">
          <router-link :to="displayIdMatch.url" class="search-item primary" @click="closeResults">
            <span class="item-type">{{ displayIdMatch.type }}</span>
            <span class="item-name">{{ displayIdMatch.name }}</span>
          </router-link>
        </div>
        
        <!-- Items -->
        <div v-if="results.items?.length" class="search-section">
          <div class="section-label">Gegenstände</div>
          <router-link
            v-for="item in results.items.slice(0, 5)"
            :key="item.id"
            :to="`/items/${item.id}`"
            class="search-item"
            @click="closeResults"
          >
            <span class="item-id">{{ item.display_id || 'I' + item.id }}</span>
            <div class="item-main">
              <span class="item-name">{{ item.name }}</span>
              <span v-if="item.location_path" class="item-location">{{ item.location_path }}</span>
            </div>
          </router-link>
        </div>
        
        <!-- Boxes -->
        <div v-if="results.boxes?.length" class="search-section">
          <div class="section-label">Boxen</div>
          <router-link
            v-for="box in results.boxes.slice(0, 3)"
            :key="box.id"
            :to="`/boxes/${box.id}`"
            class="search-item"
            @click="closeResults"
          >
            <span class="item-id">B{{ box.id }}</span>
            <span class="item-name">{{ box.name }}</span>
          </router-link>
        </div>
        
        <!-- Rooms -->
        <div v-if="results.rooms?.length" class="search-section">
          <div class="section-label">Räume</div>
          <router-link
            v-for="room in results.rooms.slice(0, 3)"
            :key="room.id"
            :to="`/rooms/${room.id}`"
            class="search-item"
            @click="closeResults"
          >
            <span class="item-id">R{{ room.id }}</span>
            <span class="item-name">{{ room.name }}</span>
          </router-link>
        </div>
        
        <div v-if="!hasResults" class="no-results">
          Keine Ergebnisse gefunden
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { debounce } from 'lodash'

const router = useRouter()
const searchRef = ref(null)
const searchQuery = ref('')
const showResults = ref(false)
const loading = ref(false)
const results = ref({
  items: [],
  boxes: [],
  rooms: [],
})

let searchTimeout = null

const displayIdMatch = computed(() => {
  const match = searchQuery.value.match(/^([RBI])(\d+)$/i)
  if (match) {
    const type = match[1].toUpperCase()
    const id = match[2]
    const typeNames = { R: 'Raum', B: 'Box', I: 'Gegenstand' }
    const routes = { R: 'RoomDetail', B: 'BoxDetail', I: 'ItemDetail' }
    return {
      type: typeNames[type],
      id,
      name: `${typeNames[type]} ${type}${id}`,
      url: type === 'R' ? `/rooms/${id}` : type === 'B' ? `/boxes/${id}` : `/items/${id}`,
    }
  }
  return null
})

const hasResults = computed(() => {
  return displayIdMatch.value ||
    results.value.items?.length ||
    results.value.boxes?.length ||
    results.value.rooms?.length
})

const handleSearch = debounce(async () => {
  if (!searchQuery.value || searchQuery.value.length < 2) {
    results.value = { items: [], boxes: [], rooms: [] }
    return
  }
  
  loading.value = true
  
  try {
    const response = await api.get('/search/quick', { params: { q: searchQuery.value } })
    results.value = response.data.data
  } catch (error) {
    console.error('Search error:', error)
  } finally {
    loading.value = false
  }
}, 300)

function clearSearch() {
  searchQuery.value = ''
  results.value = { items: [], boxes: [], rooms: [] }
}

function closeResults() {
  showResults.value = false
}

function handleClickOutside(event) {
  if (searchRef.value && !searchRef.value.contains(event.target)) {
    closeResults()
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style lang="scss" scoped>
.global-search {
  position: relative;
  width: 100%;
  max-width: 400px;
}

.search-input-wrapper {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: #f3f4f6;
  border-radius: 12px;
  border: 1px solid transparent;
  transition: all 0.2s;
  
  &:focus-within {
    background: white;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }
  
  svg {
    flex-shrink: 0;
    color: #9ca3af;
  }
}

.search-input {
  flex: 1;
  border: none;
  background: transparent;
  font-size: 0.875rem;
  outline: none;
  color: #1f2937;
  
  &::placeholder {
    color: #9ca3af;
  }
}

.clear-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  border: none;
  background: transparent;
  color: #9ca3af;
  cursor: pointer;
  padding: 0;
  
  &:hover {
    color: #6b7280;
  }
}

.search-results {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  margin-top: 0.5rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
  max-height: 400px;
  overflow-y: auto;
  z-index: 1000;
}

.search-loading {
  padding: 1rem;
  text-align: center;
  color: #6b7280;
}

.search-section {
  padding: 0.5rem;
  
  &:not(:last-child) {
    border-bottom: 1px solid #e5e7eb;
  }
}

.section-label {
  padding: 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #9ca3af;
  text-transform: uppercase;
}

.search-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  border-radius: 8px;
  text-decoration: none;
  color: #1f2937;
  transition: background 0.15s;
  
  &:hover {
    background: #f3f4f6;
  }
  
  &.primary {
    background: #dbeafe;
    
    &:hover {
      background: #bfdbfe;
    }
  }
}

.item-type {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  background: #3b82f6;
  color: white;
  border-radius: 4px;
}

.item-id {
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  font-family: monospace;
}

.item-main {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  min-width: 0;
}

.item-name {
  font-size: 0.875rem;
}

.item-location {
  font-size: 0.7rem;
  color: #9ca3af;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.no-results {
  padding: 1rem;
  text-align: center;
  color: #9ca3af;
}

@media (max-width: 640px) {
  .global-search {
    max-width: none;
  }
  
  .search-input-wrapper {
    padding: 0.5rem 0.75rem;
  }
  
  .search-input::placeholder {
    font-size: 0.75rem;
  }
}
</style>