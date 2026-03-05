<template>
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between mb-6 ga-3">
      <div>
        <h1 class="text-h5 text-sm-h4 font-weight-bold text-primary">
          Registre des Dechets
        </h1>
        <p class="text-body-2 text-grey mt-1">
          Registre reglementaire conforme a l'arrete du 31 mai 2021
        </p>
      </div>
      <div class="d-flex flex-wrap ga-2">
        <v-menu>
          <template #activator="{ props }">
            <v-btn
              color="primary"
              variant="outlined"
              prepend-icon="mdi-download"
              v-bind="props"
            >
              Exporter
            </v-btn>
          </template>
          <v-list density="compact">
            <v-list-item
              prepend-icon="mdi-file-delimited"
              title="CSV"
              @click="exportRegistre('csv')"
            />
            <v-list-item
              prepend-icon="mdi-file-excel"
              title="Excel"
              @click="exportRegistre('excel')"
            />
            <v-list-item
              prepend-icon="mdi-file-pdf-box"
              title="PDF"
              @click="exportRegistre('pdf')"
            />
          </v-list>
        </v-menu>
      </div>
    </div>

    <v-card class="mb-4">
      <v-card-text>
        <v-row dense>
          <v-col
            cols="12"
            md="2"
          >
            <v-text-field
              v-model="filters.date_debut"
              label="Date debut"
              type="date"
              hide-details
            />
          </v-col>
          <v-col
            cols="12"
            md="2"
          >
            <v-text-field
              v-model="filters.date_fin"
              label="Date fin"
              type="date"
              hide-details
            />
          </v-col>
          <v-col
            cols="12"
            md="3"
          >
            <v-select
              v-model="filters.type_dechet_id"
              :items="typesDechets"
              item-title="denomination"
              item-value="id"
              label="Type de dechet"
              clearable
              hide-details
            />
          </v-col>
          <v-col
            cols="12"
            md="2"
          >
            <v-select
              v-model="filters.dangereux"
              :items="dangereuxOptions"
              label="DD / DND"
              clearable
              hide-details
            />
          </v-col>
          <v-col
            cols="12"
            md="3"
          >
            <v-select
              v-model="filters.collecteur_id"
              :items="collecteurs"
              item-title="raison_sociale"
              item-value="id"
              label="Collecteur"
              clearable
              hide-details
            />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <v-card>
      <v-data-table
        :headers="headers"
        :items="registre"
        :loading="loading"
        items-per-page="25"
        hover
        density="compact"
      >
        <template #item.date="{ item }">
          {{ formatDate(item.date) }}
        </template>

        <template #item.dangereux="{ item }">
          <v-chip
            :color="item.dangereux ? 'error' : 'success'"
            size="x-small"
            variant="flat"
          >
            {{ item.dangereux ? 'DD' : 'DND' }}
          </v-chip>
        </template>

        <template #item.quantite_tonnes="{ item }">
          <span class="font-weight-medium">{{ formatNumber(item.quantite_tonnes) }} t</span>
        </template>

        <template #item.numero_bsd="{ item }">
          <span
            v-if="item.numero_bsd"
            class="text-primary font-weight-medium"
          >{{ item.numero_bsd }}</span>
          <span
            v-else
            class="text-grey"
          >-</span>
        </template>

        <template #item.est_rachat="{ item }">
          <v-icon
            v-if="item.est_rachat"
            size="small"
            color="success"
          >
            mdi-cash-plus
          </v-icon>
          <v-icon
            v-else
            size="small"
            color="error"
          >
            mdi-cash-minus
          </v-icon>
        </template>

        <template #no-data>
          <div class="text-center pa-6">
            <v-icon
              size="48"
              color="grey-lighten-1"
              class="mb-3"
            >
              mdi-book-open-page-variant-outline
            </v-icon>
            <p class="text-body-1 text-grey">
              Aucune entree dans le registre pour cette periode
            </p>
            <p class="text-caption text-grey">
              Les entrees sont generees automatiquement a partir des enlevements completes.
            </p>
          </div>
        </template>

        <template #bottom>
          <v-divider />
          <div class="d-flex align-center justify-space-between pa-4">
            <span class="text-body-2 text-grey">
              {{ registre.length }} entree(s) dans le registre
            </span>
            <div class="d-flex ga-4">
              <span class="text-body-2">
                Total DD: <strong class="text-error">{{ formatNumber(totalDD) }} t</strong>
              </span>
              <span class="text-body-2">
                Total DND: <strong class="text-success">{{ formatNumber(totalDND) }} t</strong>
              </span>
              <span class="text-body-2">
                Total: <strong>{{ formatNumber(totalDD + totalDND) }} t</strong>
              </span>
            </div>
          </div>
        </template>
      </v-data-table>
    </v-card>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import api from '../../services/api'
import { useUiStore } from '../../stores/ui'

const uiStore = useUiStore()
const registre = ref([])
const typesDechets = ref([])
const collecteurs = ref([])
const loading = ref(false)
const exporting = ref(false)

const filters = ref({
  date_debut: '',
  date_fin: '',
  type_dechet_id: null,
  dangereux: null,
  collecteur_id: null,
})

const dangereuxOptions = [
  { title: 'Dechets dangereux (DD)', value: true },
  { title: 'Dechets non dangereux (DND)', value: false },
]

const headers = [
  { title: 'Date', key: 'date', sortable: true, width: 100 },
  { title: 'Nature du dechet', key: 'nature_dechet', sortable: true },
  { title: 'Code', key: 'code_dechet', sortable: true, width: 100 },
  { title: 'DD', key: 'dangereux', sortable: true, width: 60 },
  { title: 'Quantite (t)', key: 'quantite_tonnes', sortable: true, width: 100 },
  { title: 'Collecteur', key: 'collecteur', sortable: true },
  { title: 'SIRET collecteur', key: 'siret_collecteur', sortable: false, width: 130 },
  { title: 'N. autorisation', key: 'numero_autorisation', sortable: false, width: 120 },
  { title: 'Destination', key: 'destination', sortable: false },
  { title: 'Code traitement', key: 'code_traitement', sortable: false, width: 110 },
  { title: 'N. BSD', key: 'numero_bsd', sortable: false, width: 120 },
]

const totalDD = computed(() => {
  return registre.value
    .filter(r => r.dangereux)
    .reduce((sum, r) => sum + (r.quantite_tonnes || 0), 0)
})

const totalDND = computed(() => {
  return registre.value
    .filter(r => !r.dangereux)
    .reduce((sum, r) => sum + (r.quantite_tonnes || 0), 0)
})

async function fetchRegistre() {
  loading.value = true
  try {
    const params = {}
    if (filters.value.date_debut) params.date_debut = filters.value.date_debut
    if (filters.value.date_fin) params.date_fin = filters.value.date_fin
    if (filters.value.type_dechet_id) params.type_dechet_id = filters.value.type_dechet_id
    if (filters.value.dangereux !== null && filters.value.dangereux !== undefined) params.dangereux = filters.value.dangereux
    if (filters.value.collecteur_id) params.collecteur_id = filters.value.collecteur_id

    const { data } = await api.get('/registre', { params })
    registre.value = data.data || []
  } catch (e) {
    uiStore.showError('Erreur lors du chargement du registre.')
  } finally {
    loading.value = false
  }
}

async function exportRegistre(format) {
  exporting.value = true
  try {
    const params = { format }
    if (filters.value.date_debut) params.date_debut = filters.value.date_debut
    if (filters.value.date_fin) params.date_fin = filters.value.date_fin
    if (filters.value.type_dechet_id) params.type_dechet_id = filters.value.type_dechet_id
    if (filters.value.dangereux !== null && filters.value.dangereux !== undefined) params.dangereux = filters.value.dangereux
    if (filters.value.collecteur_id) params.collecteur_id = filters.value.collecteur_id

    const response = await api.get('/registre/export', {
      params,
      responseType: 'blob',
    })

    const extensions = { csv: 'csv', excel: 'xlsx', pdf: 'pdf' }
    const ext = extensions[format] || format
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `registre-dechets.${ext}`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)

    uiStore.showSuccess(`Registre exporte en ${format.toUpperCase()}.`)
  } catch (e) {
    uiStore.showError('Erreur lors de l\'export du registre.')
  } finally {
    exporting.value = false
  }
}

async function fetchReferenceData() {
  try {
    const [typesRes, collecteursRes] = await Promise.all([
      api.get('/types-dechets'),
      api.get('/collecteurs'),
    ])
    typesDechets.value = typesRes.data.data || typesRes.data || []
    collecteurs.value = collecteursRes.data.data || []
  } catch {
    // Silently fail
  }
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR')
}

function formatNumber(num) {
  if (num === null || num === undefined) return '0'
  return Number(num).toFixed(3).replace('.', ',')
}

watch(filters, fetchRegistre, { deep: true })

onMounted(() => {
  fetchRegistre()
  fetchReferenceData()
})
</script>
