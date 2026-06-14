<template>
  <div class="item-row" @click="router.push(`/items/${item.id}`)" style="cursor:pointer">
    <div class="item-thumb">
      <img v-if="item.image_url" :src="item.image_url" :alt="item.name" class="thumb-img">
      <span v-else class="thumb-ph">{{ item.display_id || 'I' + item.id }}</span>
    </div>
    <span class="item-id">{{ item.display_id || 'I' + item.id }}</span>
    <span class="item-name">{{ item.name }}</span>
    <span v-if="item.category" class="item-cat">{{ item.category.name }}</span>
    <span v-if="locationText" class="item-loc">{{ locationText }}</span>
    <div class="item-actions" @click.stop>
      <router-link :to="`/items/${item.id}`" class="row-btn" title="Details">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>
        </svg>
      </router-link>
      <router-link v-if="canEdit" :to="`/items/${item.id}/edit`" class="row-btn" title="Bearbeiten">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
          <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
        </svg>
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const { canEdit } = useAuth()

const props = defineProps({
  item: {
    type: Object,
    required: true
  }
})

const locationText = computed(() => {
  if (props.item.is_in_inbox) return 'Inbox'
  if (props.item.box) return props.item.box.name || `B${props.item.box.id}`
  if (props.item.room) return props.item.room.name || `R${props.item.room.id}`
  return null
})
</script>

<style lang="scss" scoped>
.item-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 1rem;
  border-bottom: 1px solid #f3f4f6;
  color: inherit;
  min-height: 60px;

  &:last-child { border-bottom: none; }
  &:hover { background: #f9fafb; }
}

.item-thumb {
  width: 48px;
  height: 48px;
  flex-shrink: 0;
  border-radius: 8px;
  overflow: hidden;
  background: #dbeafe;
  display: flex;
  align-items: center;
  justify-content: center;
}

.thumb-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.thumb-ph {
  font-size: 0.65rem;
  font-weight: 700;
  color: #3b82f6;
}

.item-id {
  font-size: 0.75rem;
  font-weight: 600;
  color: #3b82f6;
  flex-shrink: 0;
  min-width: 36px;
}

.item-name {
  flex: 1;
  font-size: 0.875rem;
  font-weight: 600;
  color: #111827;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  min-width: 0;
}

.item-cat {
  font-size: 0.75rem;
  color: #9ca3af;
  flex-shrink: 0;
  white-space: nowrap;
}

.item-loc {
  font-size: 0.75rem;
  color: #9ca3af;
  flex-shrink: 0;
  white-space: nowrap;
}

.item-actions {
  display: flex;
  gap: 0.25rem;
  flex-shrink: 0;
}

.row-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border-radius: 6px;
  color: #9ca3af;
  text-decoration: none;
  transition: all 0.15s;
  &:hover { background: #eff6ff; color: #3b82f6; }
}

@media (max-width: 640px) {
  .item-cat,
  .item-loc { display: none; }
}
</style>
