<template>
  <aside class="sidebar" :class="{ collapsed, 'mobile-open': mobileOpen }">
    <div class="sidebar-header">
      <div class="logo">
        <svg class="logo-icon" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24">
          <polygon points="12,3 20,7.5 12,12 4,7.5" fill="#2dd4bf"/>
          <polygon points="20,7.5 20,16.5 12,21 12,12" fill="#0d9488"/>
          <polygon points="4,7.5 12,12 12,21 4,16.5" fill="#0f766e"/>
        </svg>
        <span v-if="!collapsed" class="logo-text">Inventar</span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <router-link to="/" class="nav-item" title="Dashboard" @click="onNavClick">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect>
          <rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect>
        </svg>
        <span v-if="!collapsed">Dashboard</span>
      </router-link>

      <router-link to="/items" class="nav-item" title="Gegenstände" @click="onNavClick">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
          <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
          <line x1="12" y1="22.08" x2="12" y2="12"></line>
        </svg>
        <span v-if="!collapsed">Gegenstände</span>
      </router-link>

      <router-link to="/rooms" class="nav-item" title="Räume" @click="onNavClick">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
          <polyline points="9 22 9 12 15 12 15 22"></polyline>
        </svg>
        <span v-if="!collapsed">Räume</span>
      </router-link>

      <router-link to="/boxes" class="nav-item" title="Boxen" @click="onNavClick">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
          <line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line>
        </svg>
        <span v-if="!collapsed">Boxen</span>
      </router-link>

      <router-link to="/inbox" class="nav-item" title="Inbox" @click="onNavClick">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline>
          <path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path>
        </svg>
        <span v-if="!collapsed">Inbox</span>
      </router-link>

      <router-link to="/loans" class="nav-item" title="Ausgeliehen" @click="onNavClick">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M20 12V22H4V12"></path>
          <path d="M22 7H2v5h20V7z"></path>
          <path d="M12 22V7"></path>
          <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path>
          <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path>
        </svg>
        <span v-if="!collapsed">Ausgeliehen</span>
      </router-link>

      <router-link v-if="isEditor" to="/categories" class="nav-item" title="Kategorien" @click="onNavClick">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
        </svg>
        <span v-if="!collapsed">Kategorien</span>
      </router-link>

      <router-link v-if="isEditor" to="/persons" class="nav-item" title="Personen" @click="onNavClick">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
          <circle cx="12" cy="7" r="4"/>
        </svg>
        <span v-if="!collapsed">Personen</span>
      </router-link>

      <router-link to="/scan" class="nav-item" title="QR-Code" @click="onNavClick">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2v11z"></path>
          <circle cx="12" cy="13" r="4"></circle>
        </svg>
        <span v-if="!collapsed">QR-Code</span>
      </router-link>

      <div class="nav-divider"></div>

      <router-link v-if="isAdmin" to="/users" class="nav-item" title="Benutzer" @click="onNavClick">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
          <circle cx="9" cy="7" r="4"></circle>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
        </svg>
        <span v-if="!collapsed">Benutzer</span>
      </router-link>

      <router-link v-if="isAdmin" to="/logs" class="nav-item" title="Login-Protokoll" @click="onNavClick">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
          <polyline points="14 2 14 8 20 8"></polyline>
          <line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line>
        </svg>
        <span v-if="!collapsed">Login-Protokoll</span>
      </router-link>
    </nav>

    <div class="sidebar-footer">
      <router-link to="/settings" class="nav-item" title="Einstellungen" @click="onNavClick">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="3"></circle>
          <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
        </svg>
        <span v-if="!collapsed">Einstellungen</span>
      </router-link>
      <div v-if="!collapsed" class="app-version">{{ appVersion }}</div>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  collapsed: { type: Boolean, default: false },
  mobileOpen: { type: Boolean, default: false }
})

const emit = defineEmits(['toggle', 'close'])
const authStore = useAuthStore()
const isAdmin = computed(() => authStore.isAdmin)
const isEditor = computed(() => authStore.isEditor)

const appVersion = import.meta.env.VITE_APP_VERSION || 'dev'

function onNavClick() {
  emit('close')
}
</script>

<style lang="scss" scoped>
.sidebar {
  width: 260px;
  height: 100vh;
  position: fixed;
  left: 0;
  top: 0;
  background: #1f2937;
  display: flex;
  flex-direction: column;
  transition: width 0.3s ease;
  z-index: 300;
}

.sidebar.collapsed {
  width: 70px;
}

.sidebar-header {
  padding: 1.25rem;
  border-bottom: 1px solid #374151;
}

@keyframes cube-spin-in {
  from {
    transform: scale(0) rotate(-180deg);
    opacity: 0;
  }
  to {
    transform: scale(1) rotate(0deg);
    opacity: 1;
  }
}

.logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: white;
  font-size: 1.125rem;
  font-weight: 600;
  .logo-text { white-space: nowrap; }
}

.logo-icon {
  flex-shrink: 0;
  transform-origin: center;
  transform-box: fill-box;
  animation: cube-spin-in 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}

.sidebar-nav {
  flex: 1;
  padding: 1rem 0.75rem;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  min-height: 44px;
  color: #9ca3af;
  text-decoration: none;
  border-radius: 8px;
  margin-bottom: 0.25rem;
  transition: all 0.15s;
  svg { flex-shrink: 0; }
  span { white-space: nowrap; }
  &:hover { background: #374151; color: white; }
  &.router-link-active { background: #3b82f6; color: white; }
}

.nav-divider {
  height: 1px;
  background: #374151;
  margin: 0.75rem 0;
}

.sidebar-footer {
  padding: 0.75rem;
  border-top: 1px solid #374151;
}

.app-version {
  padding: 0.25rem 0.75rem 0.125rem;
  font-size: 0.65rem;
  color: #4b5563;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
}

/* ─── Mobile (< 1024px) ─── */
@media (max-width: 1023px) {
  .sidebar {
    width: 280px;
    transform: translateX(-100%);
    transition: transform 0.3s ease;
  }

  .sidebar.collapsed {
    width: 280px; /* no icon-only on mobile */
  }

  .sidebar.mobile-open {
    transform: translateX(0);
  }

  /* Always show labels on mobile drawer */
  .nav-item span,
  .logo-text {
    display: inline !important;
  }
}
</style>
