<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      <v-icon
        color="primary"
        class="mr-2"
      >
        mdi-table
      </v-icon>
      Comparatif des tarifs
    </v-card-title>

    <v-card-text>
      <v-progress-linear
        v-if="loading"
        indeterminate
        color="primary"
        class="mb-4"
      />

      <div
        v-if="!loading && matrix.length === 0"
        class="text-center pa-6"
      >
        <v-icon
          size="48"
          color="grey-lighten-1"
          class="mb-3"
        >
          mdi-table-off
        </v-icon>
        <p class="text-body-1 text-grey">
          Aucun tarif configure pour comparer.
        </p>
      </div>

      <div
        v-else-if="!loading"
        style="overflow-x: auto;"
      >
        <table class="comparatif-table">
          <thead>
            <tr>
              <th class="type-header">
                Type de dechet
              </th>
              <th
                v-for="col in collecteurs"
                :key="col.id"
                class="collecteur-header"
              >
                <div>{{ col.raison_sociale }}</div>
                <v-chip
                  v-if="!col.autorisation_valide"
                  size="x-small"
                  color="error"
                  variant="flat"
                >
                  Autorisation expiree
                </v-chip>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in matrix"
              :key="row.type_dechet_id"
            >
              <td class="type-cell">
                <div class="d-flex align-center ga-2">
                  <v-icon
                    v-if="row.dangereux"
                    size="small"
                    color="error"
                  >
                    mdi-alert
                  </v-icon>
                  <div>
                    <div class="font-weight-medium">
                      {{ row.denomination }}
                    </div>
                    <div class="text-caption text-grey">
                      {{ row.code_europeen }}
                    </div>
                  </div>
                </div>
              </td>
              <td
                v-for="(tarif, idx) in row.tarifs"
                :key="idx"
                :class="getCellClass(tarif, row, idx)"
              >
                <template v-if="tarif">
                  <div class="text-center">
                    <div
                      v-if="tarif.prix_unitaire !== null"
                      class="font-weight-medium"
                    >
                      {{ formatPrice(tarif.prix_unitaire) }} EUR/u
                    </div>
                    <div
                      v-if="tarif.prix_forfaitaire !== null"
                      class="text-caption"
                    >
                      Forfait: {{ formatPrice(tarif.prix_forfaitaire) }} EUR
                    </div>
                    <v-chip
                      v-if="tarif.est_rachat"
                      size="x-small"
                      color="success"
                      variant="flat"
                      class="mt-1"
                    >
                      Rachat
                    </v-chip>
                  </div>
                </template>
                <template v-else>
                  <div class="text-center text-grey">
                    -
                  </div>
                </template>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="!loading && matrix.length > 0"
        class="d-flex align-center ga-4 mt-4"
      >
        <div class="d-flex align-center ga-1">
          <div class="best-indicator" />
          <span class="text-caption">Meilleur tarif</span>
        </div>
        <div class="d-flex align-center ga-1">
          <v-chip
            size="x-small"
            color="success"
            variant="flat"
          >
            Rachat
          </v-chip>
          <span class="text-caption">Le collecteur rachete ce dechet</span>
        </div>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../../services/api'
import { useUiStore } from '../../../stores/ui'

const uiStore = useUiStore()
const loading = ref(false)
const collecteurs = ref([])
const matrix = ref([])

async function fetchComparatif() {
  loading.value = true
  try {
    const { data } = await api.get('/collecteurs-comparatif')
    collecteurs.value = data.collecteurs || []
    matrix.value = data.matrix || []
  } catch (e) {
    uiStore.showError('Erreur lors du chargement du comparatif.')
  } finally {
    loading.value = false
  }
}

function formatPrice(price) {
  if (price === null || price === undefined) return '-'
  return Number(price).toFixed(2).replace('.', ',')
}

function getCellClass(tarif, row, idx) {
  const classes = ['tarif-cell']
  if (tarif && row.best_collecteur_id === collecteurs.value[idx]?.id) {
    classes.push('best-price')
  }
  return classes.join(' ')
}

onMounted(fetchComparatif)
</script>

<style scoped>
.comparatif-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 600px;
}

.comparatif-table th,
.comparatif-table td {
  border: 1px solid #e0e0e0;
  padding: 8px 12px;
}

.type-header {
  background-color: #2E7D32;
  color: white;
  font-weight: bold;
  min-width: 200px;
  position: sticky;
  left: 0;
  z-index: 1;
}

.collecteur-header {
  background-color: #2E7D32;
  color: white;
  font-weight: bold;
  text-align: center;
  min-width: 150px;
}

.type-cell {
  background-color: #f5f5f5;
  font-weight: 500;
  position: sticky;
  left: 0;
  z-index: 1;
}

.tarif-cell {
  text-align: center;
  vertical-align: middle;
}

.best-price {
  background-color: #E8F5E9;
  border: 2px solid #4CAF50 !important;
}

.best-indicator {
  width: 16px;
  height: 16px;
  background-color: #E8F5E9;
  border: 2px solid #4CAF50;
  border-radius: 3px;
}
</style>
