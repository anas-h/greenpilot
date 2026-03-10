<template>
  <!-- Super admin: menu groupé par entreprise -->
  <v-menu
    v-if="authStore.isSuperAdmin && garageStore.garages.length > 0"
    :close-on-content-click="false"
    max-height="400"
    min-width="300"
  >
    <template #activator="{ props }">
      <v-btn
        v-bind="props"
        variant="outlined"
        class="text-none"
        style="max-width: min(320px, 45vw); min-width: 160px"
        :color="garageStore.currentGarage ? undefined : 'grey'"
      >
        <v-icon start>mdi-domain</v-icon>
        <span class="text-truncate">
          <template v-if="garageStore.currentGarage">
            <span class="text-grey-darken-1 text-caption mr-1">{{ currentEntrepriseName }} /</span>
            {{ garageStore.currentGarage.nom }}
          </template>
          <template v-else>Sélectionner un garage</template>
        </span>
        <v-icon end>mdi-chevron-down</v-icon>
      </v-btn>
    </template>
    <v-list density="compact">
      <template v-for="group in groupedGarages" :key="group.entreprise.id">
        <v-list-subheader class="text-primary font-weight-bold">
          <v-icon size="small" class="mr-1">mdi-office-building</v-icon>
          {{ group.entreprise.raison_sociale }}
        </v-list-subheader>
        <v-list-item
          v-for="garage in group.garages"
          :key="garage.id"
          :active="garage.id === garageStore.currentGarageId"
          color="primary"
          class="pl-8"
          @click="selectGarage(garage.id)"
        >
          <template #prepend>
            <v-icon size="small">mdi-garage</v-icon>
          </template>
          <v-list-item-title>{{ garage.nom }}</v-list-item-title>
          <v-list-item-subtitle>{{ garage.ville }}</v-list-item-subtitle>
        </v-list-item>
      </template>
    </v-list>
  </v-menu>

  <!-- Utilisateur normal: select simple -->
  <v-select
    v-else-if="garageStore.garages.length > 0"
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
import { computed } from 'vue'
import { useGarageStore } from '../stores/garage'
import { useAuthStore } from '../stores/auth'

const garageStore = useGarageStore()
const authStore = useAuthStore()

const groupedGarages = computed(() => {
  const groups = {}
  for (const garage of garageStore.garages) {
    const entreprise = garage.entreprise || { id: garage.entreprise_id, raison_sociale: 'Entreprise' }
    if (!groups[entreprise.id]) {
      groups[entreprise.id] = { entreprise, garages: [] }
    }
    groups[entreprise.id].garages.push(garage)
  }
  return Object.values(groups).sort((a, b) =>
    a.entreprise.raison_sociale.localeCompare(b.entreprise.raison_sociale)
  )
})

const currentEntrepriseName = computed(() => {
  const garage = garageStore.currentGarage
  if (!garage) return ''
  return garage.entreprise?.raison_sociale || ''
})

function selectGarage(id) {
  garageStore.setCurrentGarage(id)
}
</script>
