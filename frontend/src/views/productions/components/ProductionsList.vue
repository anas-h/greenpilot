<template>
  <div>
    <v-card class="mb-4">
      <v-card-text>
        <v-row dense>
          <v-col
            cols="12"
            md="3"
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
            md="3"
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
            md="3"
          >
            <v-select
              v-model="filters.valide"
              :items="validationOptions"
              label="Validation"
              clearable
              hide-details
            />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <v-progress-linear
      v-if="loading"
      indeterminate
      color="primary"
      class="mb-2"
    />

    <v-card>
      <v-card-title
        v-if="selected.length > 0"
        class="d-flex align-center bg-primary-lighten-5 pa-3"
      >
        <span class="text-body-2">{{ selected.length }} production(s) selectionnee(s)</span>
        <v-spacer />
        <v-btn
          color="success"
          variant="flat"
          size="small"
          prepend-icon="mdi-check-all"
          :loading="validating"
          @click="validateSelected"
        >
          Valider la selection
        </v-btn>
      </v-card-title>

      <v-data-table
        v-model="selected"
        :headers="headers"
        :items="productions"
        :loading="loading"
        items-per-page="20"
        show-select
        hover
        item-value="id"
      >
        <template #item.type_dechet="{ item }">
          <div class="d-flex align-center ga-2">
            <v-icon
              v-if="item.type_dechet?.dangereux"
              size="small"
              color="error"
            >
              mdi-alert
            </v-icon>
            <span>{{ item.type_dechet?.denomination || '-' }}</span>
          </div>
        </template>

        <template #item.quantite="{ item }">
          <span class="font-weight-medium">{{ item.quantite }} {{ item.unite }}</span>
        </template>

        <template #item.date_production="{ item }">
          {{ formatDate(item.date_production) }}
        </template>

        <template #item.conteneur="{ item }">
          {{ item.conteneur?.nom || '-' }}
        </template>

        <template #item.valide="{ item }">
          <v-chip
            :color="item.valide ? 'success' : 'warning'"
            size="small"
            variant="flat"
          >
            {{ item.valide ? 'Validee' : 'A valider' }}
          </v-chip>
        </template>

        <template #item.source_type="{ item }">
          <v-chip
            size="x-small"
            variant="outlined"
            color="grey"
          >
            {{ sourceLabels[item.source_type] || item.source_type || '-' }}
          </v-chip>
        </template>

        <template #item.actions="{ item }">
          <v-btn
            icon="mdi-pencil"
            variant="text"
            size="small"
            title="Modifier"
            @click="$emit('edit', item)"
          />
          <v-btn
            icon="mdi-delete"
            variant="text"
            size="small"
            color="error"
            title="Supprimer"
            @click="deleteProduction(item)"
          />
        </template>

        <template #no-data>
          <div class="text-center pa-6">
            <v-icon
              size="48"
              color="grey-lighten-1"
              class="mb-3"
            >
              mdi-plus-circle-outline
            </v-icon>
            <p class="text-body-1 text-grey">
              Aucune production enregistree
            </p>
          </div>
        </template>
      </v-data-table>
    </v-card>

    <ConfirmDialog
      v-model="confirmDialogOpen"
      title="Confirmer la suppression"
      message="Supprimer cette production ?"
      confirm-text="Supprimer"
      @confirm="doDelete"
    />
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import api from '../../../services/api'
import { useUiStore } from '../../../stores/ui'
import ConfirmDialog from '../../../components/ConfirmDialog.vue'

const emit = defineEmits(['edit'])

const uiStore = useUiStore()
const productions = ref([])
const typesDechets = ref([])
const selected = ref([])
const loading = ref(false)
const validating = ref(false)
const confirmDialogOpen = ref(false)
const pendingDelete = ref(null)

const filters = ref({
  date_debut: '',
  date_fin: '',
  type_dechet_id: null,
  valide: null,
})

const validationOptions = [
  { title: 'Validee', value: true },
  { title: 'A valider', value: false },
]

const sourceLabels = {
  manuelle: 'Manuelle',
  scan_qr: 'Scan QR',
  import_or: 'Import OR',
  correction: 'Correction',
}

const headers = [
  { title: 'Type de dechet', key: 'type_dechet', sortable: true },
  { title: 'Quantite', key: 'quantite', sortable: true },
  { title: 'Date', key: 'date_production', sortable: true },
  { title: 'Conteneur', key: 'conteneur', sortable: false },
  { title: 'Source', key: 'source_type', sortable: true },
  { title: 'Statut', key: 'valide', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' },
]

async function fetchProductions() {
  loading.value = true
  try {
    const params = {}
    if (filters.value.date_debut) params.date_debut = filters.value.date_debut
    if (filters.value.date_fin) params.date_fin = filters.value.date_fin
    if (filters.value.type_dechet_id) params.type_dechet_id = filters.value.type_dechet_id
    if (filters.value.valide !== null && filters.value.valide !== undefined) params.valide = filters.value.valide

    const { data } = await api.get('/productions', { params })
    productions.value = data.data || []
  } catch (e) {
    uiStore.showError('Erreur lors du chargement des productions.')
  } finally {
    loading.value = false
  }
}

async function fetchTypesDechets() {
  try {
    const { data } = await api.get('/types-dechets')
    typesDechets.value = data.data || data || []
  } catch {
    uiStore.showError('Erreur lors du chargement des types de dechets.')
  }
}

async function validateSelected() {
  if (selected.value.length === 0) return
  validating.value = true
  try {
    await api.post('/productions/validate', { ids: selected.value })
    uiStore.showSuccess(`${selected.value.length} production(s) validee(s).`)
    selected.value = []
    fetchProductions()
  } catch (e) {
    uiStore.showError('Erreur lors de la validation.')
  } finally {
    validating.value = false
  }
}

function deleteProduction(production) {
  pendingDelete.value = production
  confirmDialogOpen.value = true
}

async function doDelete() {
  confirmDialogOpen.value = false
  try {
    await api.delete(`/productions/${pendingDelete.value.id}`)
    uiStore.showSuccess('Production supprimee.')
    fetchProductions()
  } catch (e) {
    uiStore.showError('Erreur lors de la suppression.')
  } finally {
    pendingDelete.value = null
  }
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR')
}

watch(filters, fetchProductions, { deep: true })

onMounted(() => {
  fetchProductions()
  fetchTypesDechets()
})

defineExpose({ fetchProductions })
</script>
