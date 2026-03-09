<template>
  <div>
    <v-card class="mb-4">
      <v-card-text>
        <v-row dense>
          <v-col
            cols="6"
            sm="6"
            md="3"
          >
            <v-select
              v-model="filters.statut"
              :items="statutOptions"
              label="Statut"
              clearable
              hide-details
            />
          </v-col>
          <v-col
            cols="6"
            sm="6"
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
          <v-col
            cols="6"
            sm="6"
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
            cols="6"
            sm="6"
            md="3"
          >
            <v-text-field
              v-model="filters.date_fin"
              label="Date fin"
              type="date"
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

    <v-card style="overflow-x: auto">
      <v-data-table
        :headers="headers"
        :items="enlevements"
        :loading="loading"
        items-per-page="15"
        hover
      >
        <template #item.numero="{ item }">
          <span class="font-weight-medium text-primary">{{ item.numero }}</span>
        </template>

        <template #item.collecteur="{ item }">
          {{ item.collecteur?.raison_sociale || '-' }}
        </template>

        <template #item.date_prevue="{ item }">
          {{ formatDate(item.date_prevue) }}
        </template>

        <template #item.statut="{ item }">
          <v-chip
            :color="getStatutColor(item.statut)"
            size="small"
            variant="flat"
          >
            {{ getStatutLabel(item.statut) }}
          </v-chip>
        </template>

        <template #item.montants="{ item }">
          <div v-if="item.statut === 'complete'">
            <div
              v-if="item.cout_total > 0"
              class="text-error text-caption"
            >
              -{{ formatMontant(item.cout_total) }}
            </div>
            <div
              v-if="item.revenu_total > 0"
              class="text-success text-caption"
            >
              +{{ formatMontant(item.revenu_total) }}
            </div>
          </div>
          <span
            v-else
            class="text-grey"
          >-</span>
        </template>

        <template #item.lignes="{ item }">
          <v-chip
            size="x-small"
            variant="outlined"
            color="grey"
          >
            {{ (item.lignes || []).length }} ligne(s)
          </v-chip>
        </template>

        <template #item.actions="{ item }">
          <v-menu>
            <template #activator="{ props }">
              <v-btn
                icon="mdi-dots-vertical"
                variant="text"
                size="small"
                title="Actions"
                v-bind="props"
              />
            </template>
            <v-list density="compact">
              <v-list-item
                v-if="item.statut === 'brouillon' || item.statut === 'planifie'"
                prepend-icon="mdi-check-circle"
                title="Completer"
                @click="$emit('complete', item)"
              />
              <v-list-item
                v-if="item.statut === 'brouillon'"
                prepend-icon="mdi-pencil"
                title="Modifier"
                @click="$emit('edit', item)"
              />
              <v-list-item
                v-if="item.statut === 'complete'"
                prepend-icon="mdi-file-pdf-box"
                title="Bon PDF"
                @click="$emit('download-pdf', item)"
              />
              <v-list-item
                v-if="item.statut === 'brouillon'"
                prepend-icon="mdi-delete"
                title="Supprimer"
                class="text-error"
                @click="deleteEnlevement(item)"
              />
            </v-list>
          </v-menu>
        </template>

        <template #no-data>
          <div class="text-center pa-6">
            <v-icon
              size="48"
              color="grey-lighten-1"
              class="mb-3"
            >
              mdi-truck-delivery-outline
            </v-icon>
            <p class="text-body-1 text-grey">
              Aucun enlevement enregistre
            </p>
          </div>
        </template>
      </v-data-table>
    </v-card>

    <ConfirmDialog
      v-model="confirmDialogOpen"
      title="Confirmer la suppression"
      :message="pendingDelete ? `Supprimer l'enlevement ${pendingDelete.numero} ?` : ''"
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

const emit = defineEmits(['edit', 'complete', 'download-pdf'])

const uiStore = useUiStore()
const enlevements = ref([])
const collecteurs = ref([])
const loading = ref(false)
const confirmDialogOpen = ref(false)
const pendingDelete = ref(null)

const filters = ref({
  statut: null,
  collecteur_id: null,
  date_debut: '',
  date_fin: '',
})

const statutOptions = [
  { title: 'Brouillon', value: 'brouillon' },
  { title: 'Planifie', value: 'planifie' },
  { title: 'Complete', value: 'complete' },
  { title: 'Annule', value: 'annule' },
]

const headers = [
  { title: 'Numero', key: 'numero', sortable: true },
  { title: 'Collecteur', key: 'collecteur', sortable: false },
  { title: 'Date prevue', key: 'date_prevue', sortable: true },
  { title: 'Statut', key: 'statut', sortable: true },
  { title: 'Lignes', key: 'lignes', sortable: false },
  { title: 'Montants', key: 'montants', sortable: false },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' },
]

function getStatutColor(statut) {
  const colors = {
    brouillon: 'grey',
    planifie: 'info',
    complete: 'success',
    annule: 'error',
  }
  return colors[statut] || 'grey'
}

function getStatutLabel(statut) {
  const labels = {
    brouillon: 'Brouillon',
    planifie: 'Planifie',
    complete: 'Complete',
    annule: 'Annule',
  }
  return labels[statut] || statut
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR')
}

function formatMontant(montant) {
  if (!montant) return '0,00 EUR'
  return Number(montant).toFixed(2).replace('.', ',') + ' EUR'
}

async function fetchEnlevements() {
  loading.value = true
  try {
    const params = {}
    if (filters.value.statut) params.statut = filters.value.statut
    if (filters.value.collecteur_id) params.collecteur_id = filters.value.collecteur_id
    if (filters.value.date_debut) params.date_debut = filters.value.date_debut
    if (filters.value.date_fin) params.date_fin = filters.value.date_fin

    const { data } = await api.get('/enlevements', { params })
    enlevements.value = data.data || []
  } catch (e) {
    uiStore.showError('Erreur lors du chargement des enlevements.')
  } finally {
    loading.value = false
  }
}

async function fetchCollecteurs() {
  try {
    const { data } = await api.get('/collecteurs')
    collecteurs.value = data.data || []
  } catch {
    uiStore.showError('Erreur lors du chargement des collecteurs.')
  }
}

function deleteEnlevement(enlevement) {
  pendingDelete.value = enlevement
  confirmDialogOpen.value = true
}

async function doDelete() {
  confirmDialogOpen.value = false
  try {
    await api.delete(`/enlevements/${pendingDelete.value.id}`)
    uiStore.showSuccess('Enlevement supprime.')
    fetchEnlevements()
  } catch (e) {
    const msg = e.response?.data?.message || 'Erreur lors de la suppression.'
    uiStore.showError(msg)
  } finally {
    pendingDelete.value = null
  }
}

watch(filters, fetchEnlevements, { deep: true })

onMounted(() => {
  fetchEnlevements()
  fetchCollecteurs()
})

defineExpose({ fetchEnlevements })
</script>
