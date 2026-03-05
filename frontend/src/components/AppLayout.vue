<template>
  <div>
    <template v-if="ready">
      <AppSidebar />
      <AppHeader />
      <v-main>
        <TrialBanner />
        <v-container
          fluid
          class="pa-6 pa-md-8"
          style="max-width: 1600px"
        >
          <router-view v-slot="{ Component }">
            <transition
              name="page"
              mode="out-in"
            >
              <component
                :is="Component"
                :key="garageStore.currentGarageId"
              />
            </transition>
          </router-view>
        </v-container>
      </v-main>
    </template>
    <v-main v-else>
      <div
        class="d-flex justify-center align-center"
        style="min-height: 100vh"
      >
        <v-progress-circular
          indeterminate
          color="primary"
          size="48"
        />
      </div>
    </v-main>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { useGarageStore } from '../stores/garage'
import { useSubscriptionStore } from '../stores/subscription'
import { useUiStore } from '../stores/ui'
import AppHeader from './AppHeader.vue'
import AppSidebar from './AppSidebar.vue'
import TrialBanner from './TrialBanner.vue'

const garageStore = useGarageStore()
const subscriptionStore = useSubscriptionStore()
const uiStore = useUiStore()
const ready = ref(false)

onMounted(async () => {
  try {
    await Promise.all([
      garageStore.fetchGarages(),
      subscriptionStore.fetchStatus(),
    ])
  } catch (e) {
    uiStore.showError('Erreur lors du chargement initial de l\'application.')
  }
  ready.value = true
})
</script>
