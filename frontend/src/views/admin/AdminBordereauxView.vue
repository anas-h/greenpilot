<template>
  <div>
    <div class="d-flex align-center mb-6">
      <v-btn
        icon="mdi-arrow-left"
        variant="text"
        color="primary"
        to="/admin/dashboard"
        title="Retour au tableau de bord"
        class="mr-2"
      />
      <h1 class="text-h5 font-weight-bold">
        Bordereaux - Synchronisation Trackdechets
      </h1>
    </div>

    <v-card class="mb-4">
      <v-card-text>
        <v-row>
          <v-col
            cols="12"
            sm="4"
          >
            <v-select
              v-model="filterStatutSync"
              label="Statut synchronisation"
              :items="statutSyncOptions"
              density="compact"
              @update:model-value="loadBordereaux"
            />
          </v-col>
          <v-col
            cols="12"
            sm="4"
          >
            <v-autocomplete
              v-model="filterEntrepriseId"
              label="Entreprise"
              :items="entrepriseOptions"
              density="compact"
              clearable
              @update:model-value="loadBordereaux"
            />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <v-card>
      <v-data-table-server
        :headers="headers"
        :items="adminStore.bordereaux"
        :items-length="adminStore.bordereauxPagination.total || 0"
        :loading="adminStore.loading"
        :page="page"
        :items-per-page="itemsPerPage"
        density="comfortable"
        @update:page="onPageChange"
        @update:items-per-page="onItemsPerPageChange"
        @click:row="(_, { item }) => openErrorDialog(item)"
      >
        <template #item.entreprise="{ item }">
          {{ item.garage?.entreprise?.raison_sociale || '-' }}
        </template>
        <template #item.garage="{ item }">
          {{ item.garage?.nom || '-' }}
        </template>
        <template #item.dechet="{ item }">
          {{ item.code_dechet }} - {{ item.denomination_dechet || '' }}
        </template>
        <template #item.statut="{ item }">
          <v-chip size="small">
            {{ item.statut }}
          </v-chip>
        </template>
        <template #item.statut_sync="{ item }">
          <v-chip
            size="small"
            :color="syncColor(item.statut_sync)"
          >
            {{ syncLabel(item.statut_sync) }}
          </v-chip>
        </template>
        <template #item.erreur_sync="{ item }">
          <span
            v-if="item.erreur_sync"
            class="text-error text-caption"
          >
            {{ truncate(item.erreur_sync, 60) }}
          </span>
          <span
            v-else
            class="text-grey"
          >-</span>
        </template>
        <template #item.derniere_sync="{ item }">
          {{ item.derniere_sync ? new Date(item.derniere_sync).toLocaleString('fr-FR') : '-' }}
        </template>
        <template #item.created_at="{ item }">
          {{ new Date(item.created_at).toLocaleDateString('fr-FR') }}
        </template>
        <template #item.actions="{ item }">
          <v-btn
            v-if="item.statut_sync === 'erreur' && !item.trackdechets_id"
            size="small"
            variant="text"
            color="primary"
            icon="mdi-refresh"
            :loading="retryingId === item.id"
            @click.stop="retryBsd(item)"
          />
        </template>
      </v-data-table-server>
    </v-card>

    <!-- Error detail dialog -->
    <v-dialog
      v-model="errorDialog"
      max-width="600"
    >
      <v-card v-if="selectedBordereau">
        <v-card-title>Erreur de synchronisation</v-card-title>
        <v-card-text>
          <div class="mb-2">
            <strong>Bordereau :</strong> #{{ selectedBordereau.id }} ({{ selectedBordereau.type_bsd }})
          </div>
          <div class="mb-2">
            <strong>Entreprise :</strong> {{ selectedBordereau.garage?.entreprise?.raison_sociale || '-' }}
          </div>
          <div class="mb-2">
            <strong>Garage :</strong> {{ selectedBordereau.garage?.nom || '-' }}
          </div>
          <div class="mb-2">
            <strong>Dechet :</strong> {{ selectedBordereau.code_dechet }} - {{ selectedBordereau.denomination_dechet || '' }}
          </div>
          <div class="mb-2">
            <strong>Derniere tentative :</strong> {{ selectedBordereau.derniere_sync ? new Date(selectedBordereau.derniere_sync).toLocaleString('fr-FR') : '-' }}
          </div>
          <v-divider class="my-3" />
          <div class="mb-1">
            <strong>Message d'erreur :</strong>
          </div>
          <v-alert
            type="error"
            variant="tonal"
            density="compact"
          >
            {{ selectedBordereau.erreur_sync || 'Aucune erreur enregistree.' }}
          </v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="text"
            @click="errorDialog = false"
          >
            Fermer
          </v-btn>
          <v-btn
            v-if="selectedBordereau.statut_sync === 'erreur' && !selectedBordereau.trackdechets_id"
            color="primary"
            prepend-icon="mdi-refresh"
            :loading="retryingId === selectedBordereau.id"
            @click="retryBsd(selectedBordereau)"
          >
            Relancer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAdminStore } from '../../stores/admin'
import { useUiStore } from '../../stores/ui'

const adminStore = useAdminStore()
const uiStore = useUiStore()

const filterStatutSync = ref('erreur')
const filterEntrepriseId = ref(null)
const page = ref(1)
const itemsPerPage = ref(15)
const retryingId = ref(null)
const errorDialog = ref(false)
const selectedBordereau = ref(null)

const entrepriseOptions = computed(() => {
  return adminStore.entreprises.map(e => ({ title: e.raison_sociale, value: e.id }))
})

const statutSyncOptions = [
  { title: 'Erreur', value: 'erreur' },
  { title: 'En attente', value: 'en_attente' },
  { title: 'Synchronise', value: 'synchronise' },
  { title: 'Tous', value: 'tous' },
]

const headers = [
  { title: 'Entreprise', key: 'entreprise', sortable: false },
  { title: 'Garage', key: 'garage', sortable: false },
  { title: 'Type', key: 'type_bsd' },
  { title: 'Dechet', key: 'dechet', sortable: false },
  { title: 'Statut', key: 'statut' },
  { title: 'Sync', key: 'statut_sync' },
  { title: 'Erreur', key: 'erreur_sync', sortable: false },
  { title: 'Derniere sync', key: 'derniere_sync' },
  { title: 'Date', key: 'created_at' },
  { title: '', key: 'actions', sortable: false },
]

function syncColor(statut) {
  return { erreur: 'error', en_attente: 'orange', synchronise: 'success' }[statut] || 'grey'
}

function syncLabel(statut) {
  return { erreur: 'Erreur', en_attente: 'En attente', synchronise: 'Synchronise' }[statut] || statut
}

function truncate(str, len) {
  if (!str) return ''
  return str.length > len ? str.substring(0, len) + '...' : str
}

function openErrorDialog(item) {
  selectedBordereau.value = item
  errorDialog.value = true
}

function loadBordereaux() {
  page.value = 1
  fetchData()
}

function onPageChange(newPage) {
  page.value = newPage
  fetchData()
}

function onItemsPerPageChange(newPerPage) {
  itemsPerPage.value = newPerPage
  page.value = 1
  fetchData()
}

function fetchData() {
  const params = {
    statut_sync: filterStatutSync.value,
    page: page.value,
    per_page: itemsPerPage.value,
  }
  if (filterEntrepriseId.value) {
    params.entreprise_id = filterEntrepriseId.value
  }
  adminStore.fetchBordereaux(params)
}

async function retryBsd(bordereau) {
  retryingId.value = bordereau.id
  try {
    const result = await adminStore.retryBsd(bordereau.id)
    uiStore.showSuccess(result.message)
    errorDialog.value = false
    fetchData()
  } catch (e) {
    uiStore.showError(e.response?.data?.message || 'Erreur lors de la relance')
  } finally {
    retryingId.value = null
  }
}

onMounted(() => {
  adminStore.fetchEntreprises({ per_page: 100, archived: 'all' })
  fetchData()
})
</script>
