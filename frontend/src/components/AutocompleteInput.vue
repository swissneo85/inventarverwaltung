<template>
  <div class="autocomplete-input" ref="container">
    <input
      ref="inputEl"
      :value="modelValue ?? ''"
      :placeholder="placeholder"
      type="text"
      class="autocomplete-input__field"
      autocomplete="off"
      @input="onInput"
      @focus="onFocus"
      @blur="onBlur"
      @keydown.escape="close"
      @keydown.arrow-down.prevent="highlightNext"
      @keydown.arrow-up.prevent="highlightPrev"
      @keydown.enter.prevent="selectHighlighted"
    />
    <ul
      v-if="isOpen && filteredSuggestions.length > 0"
      class="autocomplete-input__list"
      role="listbox"
    >
      <li
        v-for="(s, i) in filteredSuggestions"
        :key="s"
        class="autocomplete-input__option"
        :class="{ 'is-highlighted': i === highlighted }"
        role="option"
        @mousedown.prevent="select(s)"
      >
        {{ s }}
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import api from '@/services/api'

const props = defineProps({
  modelValue: { type: String, default: '' },
  field: { type: String, required: true },
  placeholder: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const container = ref(null)
const isOpen = ref(false)
const suggestions = ref([])
const highlighted = ref(-1)

const filteredSuggestions = computed(() => {
  const q = (props.modelValue ?? '').toLowerCase().trim()
  if (!q) return suggestions.value
  return suggestions.value.filter(s => s.toLowerCase().includes(q))
})

async function fetchSuggestions() {
  try {
    const res = await api.get('/items/field-suggestions', { params: { field: props.field } })
    suggestions.value = res.data.data ?? []
  } catch {
    suggestions.value = []
  }
}

function onInput(e) {
  emit('update:modelValue', e.target.value)
  isOpen.value = true
  highlighted.value = -1
}

function onFocus() {
  if (suggestions.value.length === 0) fetchSuggestions()
  isOpen.value = true
}

function onBlur() {
  // slight delay so mousedown on option fires first
  setTimeout(close, 150)
}

function close() {
  isOpen.value = false
  highlighted.value = -1
}

function select(s) {
  emit('update:modelValue', s)
  close()
}

function highlightNext() {
  if (!isOpen.value) { isOpen.value = true; return }
  if (highlighted.value < filteredSuggestions.value.length - 1) highlighted.value++
}

function highlightPrev() {
  if (highlighted.value > 0) highlighted.value--
}

function selectHighlighted() {
  if (highlighted.value >= 0 && filteredSuggestions.value[highlighted.value] !== undefined) {
    select(filteredSuggestions.value[highlighted.value])
  } else {
    close()
  }
}
</script>

<style scoped>
.autocomplete-input {
  position: relative;
  width: 100%;
}

.autocomplete-input__field {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 16px;
  background: white;
  font-family: inherit;
  box-sizing: border-box;
}

.autocomplete-input__field:focus {
  outline: none;
  border-color: #3b82f6;
}

.autocomplete-input__list {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  z-index: 100;
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
  list-style: none;
  margin: 0;
  padding: 0.25rem 0;
  max-height: 200px;
  overflow-y: auto;
}

.autocomplete-input__option {
  padding: 0.5rem 0.75rem;
  cursor: pointer;
  font-size: 0.9rem;
  color: #374151;
  transition: background 0.1s;
}

.autocomplete-input__option:hover,
.autocomplete-input__option.is-highlighted {
  background: #f3f4f6;
}

@media (max-width: 767px) {
  .autocomplete-input__field {
    min-height: 44px;
  }

  .autocomplete-input__option {
    padding: 0.625rem 0.75rem;
    min-height: 44px;
    display: flex;
    align-items: center;
  }
}
</style>
