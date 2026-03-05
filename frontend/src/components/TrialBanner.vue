<template>
  <v-banner
    v-if="subscriptionStore.onTrial"
    color="info"
    icon="mdi-clock-outline"
    density="compact"
    class="trial-banner"
  >
    <template #text>
      Il vous reste <strong>{{ subscriptionStore.trialDaysRemaining }} jour{{ subscriptionStore.trialDaysRemaining > 1 ? 's' : '' }}</strong> d'essai gratuit.
    </template>
    <template #actions>
      <v-btn
        variant="tonal"
        color="white"
        size="small"
        :to="{ name: 'abonnement' }"
      >
        Souscrire
      </v-btn>
    </template>
  </v-banner>
  <v-banner
    v-else-if="subscriptionStore.isReadOnly"
    color="error"
    icon="mdi-alert-circle"
    density="compact"
    class="trial-banner"
  >
    <template #text>
      Votre essai a expire. Souscrivez pour continuer a utiliser GreenPilot.
    </template>
    <template #actions>
      <v-btn
        variant="tonal"
        color="white"
        size="small"
        :to="{ name: 'abonnement' }"
      >
        Souscrire
      </v-btn>
    </template>
  </v-banner>
</template>
<script setup>
import { useSubscriptionStore } from '../stores/subscription'

const subscriptionStore = useSubscriptionStore()
</script>
