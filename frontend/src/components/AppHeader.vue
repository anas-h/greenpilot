<template>
  <v-app-bar
    flat
    color="surface"
    class="eco-header"
  >
    <v-app-bar-nav-icon @click="uiStore.toggleSidebar()" />
    <v-toolbar-title class="text-body-1 text-grey-darken-1 d-none d-sm-block">
      {{ currentRoute }}
    </v-toolbar-title>
    <v-spacer />
    <GarageSelector class="mr-4" />
    <v-btn
      icon
      size="small"
      :to="{ name: 'alertes' }"
      class="mr-2"
    >
      <v-badge
        :content="alertCount"
        :model-value="alertCount > 0"
        color="error"
        overlap
      >
        <v-icon>mdi-bell-outline</v-icon>
      </v-badge>
    </v-btn>
    <v-menu>
      <template #activator="{ props }">
        <v-btn
          v-bind="props"
          variant="text"
          class="text-none"
        >
          <v-avatar
            size="36"
            color="primary-lighten-1"
            class="mr-2"
          >
            <span class="text-white font-weight-bold text-caption">{{ initials }}</span>
          </v-avatar>
          <span class="d-none d-sm-inline">{{ authStore.user?.prenom }}</span>
        </v-btn>
      </template>
      <v-list density="compact">
        <v-list-item
          prepend-icon="mdi-account"
          title="Profil"
          :to="{ name: 'profil' }"
        />
        <v-list-item
          prepend-icon="mdi-logout"
          title="Deconnexion"
          @click="handleLogout"
        />
      </v-list>
    </v-menu>
  </v-app-bar>
</template>
<script setup>
import { computed, ref, onMounted } from 'vue'
import { useDisplay } from 'vuetify'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useUiStore } from '../stores/ui'
import { useGarageStore } from '../stores/garage'
import GarageSelector from './GarageSelector.vue'
import api from '../services/api'

const { mobile } = useDisplay()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const uiStore = useUiStore()
const garageStore = useGarageStore()
const alertCount = ref(0)

const currentRoute = computed(() => {
  const names = { dashboard: 'Tableau de bord', conteneurs: 'Conteneurs', collecteurs: 'Collecteurs', productions: 'Productions', enlevements: 'Enlevements', bordereaux: 'Bordereaux (BSD)', registre: 'Registre', alertes: 'Alertes', conformite: 'Conformite', economie: 'Economie', parametres: 'Parametres' }
  return names[route.name] || ''
})

const initials = computed(() => {
  if (!authStore.user) return ''
  return (authStore.user.prenom?.[0] || '') + (authStore.user.nom?.[0] || '')
})

async function fetchAlertCount() {
  try {
    const { data } = await api.get('/alertes/count')
    alertCount.value = data.count || 0
  } catch { /* ignore */ }
}

function handleLogout() {
  authStore.logout()
  router.push({ name: 'login' })
}

onMounted(() => {
  fetchAlertCount()
  setInterval(fetchAlertCount, 60000)
})
</script>
<style scoped>
.eco-header {
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04) !important;
}
</style>
