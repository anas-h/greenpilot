<template>
  <v-navigation-drawer
    v-model="uiStore.sidebarOpen"
    :width="mobile ? 260 : 280"
    theme="dark"
    :temporary="mobile"
    class="eco-sidebar"
  >
    <div class="pa-5 text-center">
      <svg
        class="mb-1"
        width="36"
        height="36"
        viewBox="0 0 32 32"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        <path
          d="M5 26c0 0 2.5-9.5 9.5-14c2.3-1.5 6-2.5 8.5-1.2c-1 5-4 8.5-8.5 11c-2.5 1.4-5 1.8-6 1.8"
          stroke="white"
          stroke-width="2.2"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
        <path
          d="M14.5 12c0 0-1.2 5.5-1.2 9.5c0 1.8.6 3 1.2 4.2"
          stroke="white"
          stroke-width="1.6"
          stroke-linecap="round"
        />
        <path
          d="M10 18.5c2.5 0 4.5.6 5.8 2.2"
          stroke="white"
          stroke-width="1.6"
          stroke-linecap="round"
        />
      </svg>
      <h2 class="text-h5 font-weight-bold">
        GreenPilot
      </h2>
      <p
        class="text-caption mt-1"
        style="opacity: 0.6"
      >
        Gestion des dechets
      </p>
    </div>
    <v-divider
      class="mb-2"
      style="opacity: 0.12"
    />
    <v-list
      density="compact"
      nav
    >
      <template
        v-for="item in filteredMenuItems"
        :key="item.to"
      >
        <v-list-subheader
          v-if="item.header"
          class="eco-sidebar-subheader"
        >
          {{ item.header }}
        </v-list-subheader>
        <v-list-item
          v-else
          :to="item.to"
          :prepend-icon="item.icon"
          :title="item.title"
          rounded="lg"
          class="mx-2 mb-1"
        />
      </template>
    </v-list>
  </v-navigation-drawer>
</template>
<script setup>
import { computed } from 'vue'
import { useDisplay } from 'vuetify'
import { useUiStore } from '../stores/ui'
import { useAuthStore } from '../stores/auth'

const uiStore = useUiStore()
const authStore = useAuthStore()
const { mobile } = useDisplay()

const menuItems = [
  { to: '/dashboard', icon: 'mdi-view-dashboard', title: 'Tableau de bord' },
  { header: 'Gestion' },
  { to: '/conteneurs', icon: 'mdi-package-variant-closed', title: 'Conteneurs', permission: 'conteneurs.view' },
  { to: '/collecteurs', icon: 'mdi-truck', title: 'Collecteurs', permission: 'collecteurs.view' },
  { header: 'Operations' },
  { to: '/productions', icon: 'mdi-plus-circle', title: 'Productions', permission: 'productions.view' },
  { to: '/enlevements', icon: 'mdi-truck-delivery', title: 'Enlevements', permission: 'enlevements.view' },
  { header: 'Conformite' },
  { to: '/bordereaux', icon: 'mdi-file-document', title: 'Bordereaux (BSD)', permission: 'bordereaux.view' },
  { to: '/registre', icon: 'mdi-book-open-page-variant', title: 'Registre', permission: 'registre.view' },
  { to: '/conformite', icon: 'mdi-shield-check', title: 'Conformite & FDS', permission: 'fds.view' },
  { to: '/alertes', icon: 'mdi-bell', title: 'Alertes', permission: 'alertes.view' },
  { header: 'Finance' },
  { to: '/economie', icon: 'mdi-chart-line', title: 'Economie', permission: 'economie.view' },
  { header: 'Configuration' },
  { to: '/parametres', icon: 'mdi-cog', title: 'Parametres', permission: 'garages.view' },
  { to: '/abonnement', icon: 'mdi-card-account-details-star-outline', title: 'Abonnement' },
  { header: 'Administration', superAdmin: true },
  { to: '/admin/dashboard', icon: 'mdi-shield-crown', title: 'Dashboard Admin', superAdmin: true },
  { to: '/admin/entreprises', icon: 'mdi-domain', title: 'Entreprises', superAdmin: true },
]

const filteredMenuItems = computed(() => {
  return menuItems.filter(item => {
    // Super admin items only visible to super_admin role
    if (item.superAdmin && authStore.user?.role !== 'super_admin') {
      return false
    }
    if (item.header) {
      const idx = menuItems.indexOf(item)
      const nextItems = []
      for (let i = idx + 1; i < menuItems.length; i++) {
        if (menuItems[i].header) break
        nextItems.push(menuItems[i])
      }
      return nextItems.some(ni => {
        if (ni.superAdmin && authStore.user?.role !== 'super_admin') return false
        return !ni.permission || authStore.hasPermission(ni.permission)
      })
    }
    return !item.permission || authStore.hasPermission(item.permission)
  })
})
</script>
<style scoped>
.eco-sidebar {
  background: linear-gradient(180deg, #1B5E20 0%, #2E7D32 50%, #1B5E20 100%) !important;
}

.eco-sidebar-subheader {
  font-size: 0.65rem !important;
  letter-spacing: 0.08em;
  opacity: 0.5;
  text-transform: uppercase;
}
</style>
