<template>
  <div class="item-search" ref="wrapRef">
    <div class="item-search-input-wrap" :class="{ focused: isOpen }">
      <input
        ref="inputRef"
        v-model="query"
        type="text"
        :placeholder="placeholder"
        autocomplete="off"
        @input="handleInput"
        @focus="onFocus"
        @keydown.esc.prevent="close"
        @keydown.down.prevent="moveDown"
        @keydown.up.prevent="moveUp"
        @keydown.enter.prevent="selectHighlighted"
      />
      <button
        v-if="modelValue"
        type="button"
        class="item-search-clear"
        @click="clear"
        title="Auswahl aufheben"
      >×</button>
    </div>

    <div v-if="isOpen && (loading || options.length || (query && !loading))" class="item-search-dropdown">
      <div v-if="loading" class="item-search-loading">Suche…</div>
      <div
        v-for="(opt, i) in options"
        :key="opt.id"
        :class="['item-search-option', { highlighted: i === highlightIndex }]"
        @mousedown.prevent="selectOption(opt)"
      >
        <span class="item-search-option-id">{{ opt.display_id }}</span>
        <span class="item-search-option-name">{{ opt.name }}</span>
      </div>
      <div v-if="!loading && !options.length && query" class="item-search-empty">
        Keine Items gefunden
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import api from '@/services/api'
import { debounce } from 'lodash'

const props = defineProps({
  modelValue: { type: [Number, String], default: null },
  placeholder: { type: String, default: 'Item suchen…' },
  excludeId: { type: [Number, String], default: null },
})

const emit = defineEmits(['update:modelValue'])

const wrapRef = ref(null)
const inputRef = ref(null)
const query = ref('')
const options = ref([])
const loading = ref(false)
const isOpen = ref(false)
const highlightIndex = ref(-1)

// When modelValue is set externally, resolve the display text
watch(() => props.modelValue, async (val) => {
  if (!val) {
    query.value = ''
    return
  }
  // Skip if query already represents this item
  const prefix = 'I' + val
  if (query.value === prefix || query.value.startsWith(prefix + ' ')) return
  try {
    const res = await api.get(`/items/${val}`)
    const item = res.data.data
    query.value = `${item.display_id} ${item.name}`
  } catch {
    query.value = 'I' + val
  }
}, { immediate: true })

const search = debounce(async (term) => {
  if (!term || term.length < 1) {
    options.value = []
    loading.value = false
    return
  }
  loading.value = true
  try {
    const res = await api.get('/items', {
      params: { search: term, status: 'aktiv', show_accessories: 1, per_page: 20 },
    })
    const items = res.data.data?.data ?? res.data.data ?? []
    options.value = items
      .filter(i => String(i.id) !== String(props.excludeId))
      .map(i => ({ id: i.id, display_id: i.display_id || 'I' + i.id, name: i.name }))
  } catch {
    options.value = []
  } finally {
    loading.value = false
  }
}, 300)

function handleInput() {
  // If user clears the input, clear selection
  if (!query.value) {
    emit('update:modelValue', null)
  }
  isOpen.value = true
  highlightIndex.value = -1
  search(query.value)
}

function onFocus() {
  isOpen.value = true
  if (query.value && options.value.length === 0) {
    search(query.value)
  }
}

function close() {
  isOpen.value = false
  highlightIndex.value = -1
}

function clear() {
  query.value = ''
  options.value = []
  emit('update:modelValue', null)
  isOpen.value = false
  inputRef.value?.focus()
}

function selectOption(opt) {
  query.value = `${opt.display_id} ${opt.name}`
  emit('update:modelValue', opt.id)
  close()
}

function selectHighlighted() {
  if (highlightIndex.value >= 0 && options.value[highlightIndex.value]) {
    selectOption(options.value[highlightIndex.value])
  }
}

function moveDown() {
  if (highlightIndex.value < options.value.length - 1) highlightIndex.value++
}

function moveUp() {
  if (highlightIndex.value > 0) highlightIndex.value--
}

function handleClickOutside(e) {
  if (wrapRef.value && !wrapRef.value.contains(e.target)) {
    close()
  }
}

onMounted(() => document.addEventListener('mousedown', handleClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', handleClickOutside))
</script>

<style scoped>
.item-search { position: relative; }

.item-search-input-wrap {
  display: flex;
  align-items: center;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background: white;
  overflow: hidden;
  transition: border-color 0.15s;
}
.item-search-input-wrap.focused { border-color: #3b82f6; }

.item-search-input-wrap input {
  flex: 1;
  border: none;
  outline: none;
  padding: 0.5rem 0.75rem;
  font-size: 16px;
  font-family: inherit;
  background: transparent;
}

.item-search-clear {
  border: none;
  background: transparent;
  color: #9ca3af;
  font-size: 1.1rem;
  cursor: pointer;
  padding: 0 0.5rem;
  line-height: 1;
  transition: color 0.15s;
}
.item-search-clear:hover { color: #374151; }

.item-search-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.12);
  z-index: 200;
  max-height: 240px;
  overflow-y: auto;
}

.item-search-loading,
.item-search-empty {
  padding: 0.75rem 1rem;
  color: #9ca3af;
  font-size: 0.875rem;
}

.item-search-option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.6rem 1rem;
  cursor: pointer;
  font-size: 0.875rem;
  transition: background 0.1s;
}
.item-search-option:hover,
.item-search-option.highlighted { background: #f0f9ff; }

.item-search-option-id {
  font-size: 0.7rem;
  font-weight: 600;
  color: #3b82f6;
  font-family: monospace;
  min-width: 40px;
}

.item-search-option-name { color: #1f2937; }
</style>
