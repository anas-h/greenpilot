<template>
  <v-select
    v-if="garageStore.garages.length > 0"
    :model-value="garageStore.currentGarageId"
    :items="garageStore.garages"
    item-title="nom"
    item-value="id"
    density="compact"
    variant="outlined"
    hide-details
    style="max-width: min(280px, 45vw); min-width: 120px"
    prepend-inner-icon="mdi-domain"
    @update:model-value="garageStore.setCurrentGarage($event)"
  />
</template>
<script setup>
import { useGarageStore } from '../stores/garage'
const garageStore = useGarageStore()
</script>
