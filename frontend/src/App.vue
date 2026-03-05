<template>
  <v-app>
    <router-view v-if="!loading" />
    <v-overlay
      v-else
      :model-value="true"
      class="align-center justify-center"
    >
      <div class="text-center">
        <svg
          class="mb-4"
          width="48"
          height="48"
          viewBox="0 0 32 32"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            d="M5 26c0 0 2.5-9.5 9.5-14c2.3-1.5 6-2.5 8.5-1.2c-1 5-4 8.5-8.5 11c-2.5 1.4-5 1.8-6 1.8"
            stroke="#2E7D32"
            stroke-width="2.2"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
          <path
            d="M14.5 12c0 0-1.2 5.5-1.2 9.5c0 1.8.6 3 1.2 4.2"
            stroke="#2E7D32"
            stroke-width="1.6"
            stroke-linecap="round"
          />
          <path
            d="M10 18.5c2.5 0 4.5.6 5.8 2.2"
            stroke="#2E7D32"
            stroke-width="1.6"
            stroke-linecap="round"
          />
        </svg>
        <v-progress-circular
          indeterminate
          size="64"
          color="primary"
          :width="3"
        />
        <p class="text-body-2 mt-4 text-medium-emphasis">
          Chargement...
        </p>
      </div>
    </v-overlay>
    <v-snackbar
      v-model="uiStore.snackbar.show"
      :color="uiStore.snackbar.color"
      :timeout="4000"
      location="bottom right"
    >
      <div class="d-flex align-center">
        <v-icon class="mr-2">
          {{ snackbarIcon }}
        </v-icon>
        {{ uiStore.snackbar.message }}
      </div>
      <template #actions>
        <v-btn
          variant="text"
          @click="uiStore.snackbar.show = false"
        >
          Fermer
        </v-btn>
      </template>
    </v-snackbar>
  </v-app>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from './stores/auth'
import { useUiStore } from './stores/ui'

const authStore = useAuthStore()
const uiStore = useUiStore()
const loading = ref(true)

const snackbarIcon = computed(() => {
  const icons = {
    success: 'mdi-check-circle',
    error: 'mdi-alert-circle',
    warning: 'mdi-alert',
    info: 'mdi-information',
  }
  return icons[uiStore.snackbar.color] || 'mdi-information'
})

onMounted(async () => {
  await authStore.checkAuth()
  loading.value = false
})
</script>
