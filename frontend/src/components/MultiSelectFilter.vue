<template>
  <div class="multi-select" ref="rootEl">
    <button type="button" class="multi-select-trigger" :class="{ active: isActive }" @click="open = !open">
      {{ triggerText }}
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="6 9 12 15 18 9"></polyline>
      </svg>
    </button>

    <div v-if="open" class="multi-select-panel">
      <label v-for="opt in options" :key="opt.id" class="multi-select-option">
        <input
          type="checkbox"
          :checked="modelValue.includes(opt.id)"
          @change="toggle(opt.id)"
        >
        {{ opt.name }}
      </label>
      <p v-if="options.length === 0" class="multi-select-empty">Keine Optionen</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  label: { type: String, required: true },
  options: { type: Array, default: () => [] },
  modelValue: { type: Array, default: () => [] },
  // Überschreibt den Trigger-Text (z. B. für ein "nur ein Wert ausgewählt" Sonderformat)
  triggerLabel: { type: String, default: null },
  // Überschreibt, ob der Trigger als "aktiv" (Akzentfarbe) dargestellt wird
  active: { type: Boolean, default: null },
})

const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const rootEl = ref(null)

const triggerText = computed(() => {
  if (props.triggerLabel !== null) return props.triggerLabel
  return props.modelValue.length > 0 ? `${props.label} (${props.modelValue.length})` : props.label
})

const isActive = computed(() => props.active !== null ? props.active : props.modelValue.length > 0)

function toggle(id) {
  const next = props.modelValue.includes(id)
    ? props.modelValue.filter(v => v !== id)
    : [...props.modelValue, id]
  emit('update:modelValue', next)
}

function handleClickOutside(event) {
  if (rootEl.value && !rootEl.value.contains(event.target)) {
    open.value = false
  }
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))
</script>

<style lang="scss" scoped>
.multi-select {
  position: relative;
}

.multi-select-trigger {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.875rem;
  background: white;
  color: #374151;
  cursor: pointer;
  white-space: nowrap;

  &.active {
    background: var(--bg-accent);
    color: var(--text-accent);
    border-color: var(--border-accent);
  }

  &:focus {
    outline: none;
    border-color: var(--border-accent);
  }
}

.multi-select-panel {
  position: absolute;
  top: calc(100% + 0.35rem);
  left: 0;
  z-index: 20;
  min-width: 220px;
  max-height: 280px;
  overflow-y: auto;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
  padding: 0.5rem;
}

.multi-select-option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.4rem 0.5rem;
  font-size: 0.875rem;
  color: #374151;
  cursor: pointer;
  border-radius: 6px;

  &:hover {
    background: #f3f4f6;
  }

  input {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
  }
}

.multi-select-empty {
  padding: 0.5rem;
  font-size: 0.8rem;
  color: var(--text-muted);
}

@media (max-width: 767px) {
  .multi-select-trigger {
    width: 100%;
    justify-content: space-between;
    min-height: 44px;
  }

  .multi-select-panel {
    left: 0;
    right: 0;
    width: 100%;
  }
}
</style>
