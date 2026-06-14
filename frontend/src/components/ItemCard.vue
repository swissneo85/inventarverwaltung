<template>
  <div class="item-card" @click="router.push(`/items/${item.id}`)" style="cursor:pointer">
    <div class="item-main">
      <div class="item-thumb">
        <img v-if="item.image_url" :src="item.image_url" :alt="item.name" class="thumb-img">
        <span v-else class="thumb-id">{{ item.display_id || 'I' + item.id }}</span>
      </div>
      <div class="item-info">
        <h3 class="item-name">{{ item.name }}</h3>
        <p class="item-location" v-if="locationText">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
            <circle cx="12" cy="10" r="3"></circle>
          </svg>
          {{ locationText }}
        </p>
        <div class="item-tags" v-if="item.category">
          <span class="tag">{{ item.category.name }}</span>
        </div>
      </div>
    </div>
    
    <div class="item-actions" @click.stop>
      <router-link :to="`/items/${item.id}`" class="btn-icon" title="Details">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
          <circle cx="12" cy="12" r="3"></circle>
        </svg>
      </router-link>
      <router-link v-if="canEdit" :to="`/items/${item.id}/edit`" class="btn-icon" title="Bearbeiten">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
  if (props.item.is_in_inbox) {
    return 'Inbox'
  }
  if (props.item.box) {
    return `Box: ${props.item.box.name} (B${props.item.box.id})`
  }
  if (props.item.room) {
    return `Raum: ${props.item.room.name} (R${props.item.room.id})`
  }
  return null
})
</script>

<style lang="scss" scoped>
.item-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: all 0.2s;
  
  &:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }
}

.item-main {
  display: flex;
  align-items: center;
  gap: 1rem;
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

.thumb-id {
  font-size: 0.7rem;
  font-weight: 600;
  color: #3b82f6;
}

.item-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.item-name {
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.item-location {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0;
  
  svg {
    flex-shrink: 0;
  }
}

.item-tags {
  display: flex;
  gap: 0.5rem;
}

.tag {
  padding: 0.25rem 0.5rem;
  background: #f3f4f6;
  color: #6b7280;
  border-radius: 4px;
  font-size: 0.75rem;
}

.item-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: none;
  background: transparent;
  color: #6b7280;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
  
  &:hover {
    background: #f3f4f6;
    color: #3b82f6;
  }
}

@media (max-width: 640px) {
  .item-card {
    flex-direction: column;
    align-items: stretch;
  }
  
  .item-main {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .item-actions {
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px solid #e5e7eb;
    justify-content: flex-end;
  }
}
</style>