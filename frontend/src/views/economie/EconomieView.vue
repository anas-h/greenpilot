<template>
  <div>
    <h1 class="text-h5 font-weight-bold mb-6">
      Suivi economique
    </h1>
    <v-row class="mb-6">
      <v-col
        cols="12"
        md="4"
      >
        <v-card class="pa-4">
          <p class="text-body-2 text-grey">
            Cout total collecte
          </p>
          <p class="text-h4 font-weight-bold text-error">
            {{ formatCurrency(kpis.cout_total) }}
          </p>
          <p class="text-caption text-grey">
            Periode selectionnee
          </p>
        </v-card>
      </v-col>
      <v-col
        cols="12"
        md="4"
      >
        <v-card class="pa-4">
          <p class="text-body-2 text-grey">
            Revenu revente
          </p>
          <p class="text-h4 font-weight-bold text-success">
            {{ formatCurrency(kpis.revenu_total) }}
          </p>
          <p class="text-caption text-grey">
            Periode selectionnee
          </p>
        </v-card>
      </v-col>
      <v-col
        cols="12"
        md="4"
      >
        <v-card class="pa-4">
          <p class="text-body-2 text-grey">
            Cout net dechets
          </p>
          <p
            class="text-h4 font-weight-bold"
            :class="kpis.cout_net > 0 ? 'text-error' : 'text-success'"
          >
            {{ formatCurrency(kpis.cout_net) }}
          </p>
          <p class="text-caption text-grey">
            Collecte - Revente
          </p>
        </v-card>
      </v-col>
    </v-row>
    <v-card>
      <v-tabs
        v-model="tab"
        color="primary"
      >
        <v-tab value="evolution">
          Evolution mensuelle
        </v-tab>
        <v-tab value="repartition">
          Repartition par type
        </v-tab>
        <v-tab value="comparatif">
          Comparatif collecteurs
        </v-tab>
      </v-tabs>
      <v-card-text>
        <v-tabs-window v-model="tab">
          <v-tabs-window-item value="evolution">
            <GraphiqueEvolution :data="evolutionData" />
          </v-tabs-window-item>
          <v-tabs-window-item value="repartition">
            <RepartitionType :data="repartitionData" />
          </v-tabs-window-item>
          <v-tabs-window-item value="comparatif">
            <ComparatifCollecteurs :data="comparatifData" />
          </v-tabs-window-item>
        </v-tabs-window>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'
import { useUiStore } from '../../stores/ui'

const uiStore = useUiStore()
import GraphiqueEvolution from './components/GraphiqueEvolution.vue'
import RepartitionType from './components/RepartitionType.vue'
import ComparatifCollecteurs from './components/ComparatifCollecteurs.vue'

const tab = ref('evolution')
const kpis = ref({ cout_total: 0, revenu_total: 0, cout_net: 0 })
const evolutionData = ref({})
const repartitionData = ref({})
const comparatifData = ref({})

function formatCurrency(val) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val || 0)
}

onMounted(async () => {
  try {
    const { data } = await api.get('/dashboard/economie')
    kpis.value = data.kpis || kpis.value
    evolutionData.value = data.evolution || {}
    repartitionData.value = data.repartition || {}
    comparatifData.value = data.comparatif || {}
  } catch {
    uiStore.showError('Erreur lors du chargement des donnees economiques.')
  }
})
</script>
