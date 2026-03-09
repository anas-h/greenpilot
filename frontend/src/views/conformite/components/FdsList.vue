<template>
  <div class="fds-list">
    <!-- Search -->
    <v-text-field
      v-model="search"
      label="Rechercher une FDS..."
      prepend-inner-icon="mdi-magnify"
      clearable
      density="compact"
      variant="outlined"
      class="mb-4"
      style="max-width: min(400px, 100%)"
    />

    <!-- Data table -->
    <v-data-table
      :headers="headers"
      :items="fiches"
      :loading="loading"
      :search="search"
      hover
      density="comfortable"
    >
      <!-- Type dechet -->
      <template #item.type_dechet="{ item }">
        <div>
          <div class="text-body-2 font-weight-medium">
            {{ item.type_dechet?.denomination || '-' }}
          </div>
          <div class="text-caption text-medium-emphasis">
            {{ item.type_dechet?.code_europeen || '' }}
          </div>
        </div>
      </template>

      <!-- Nom produit -->
      <template #item.nom_produit="{ item }">
        <div>
          <div class="text-body-2">
            {{ item.nom_produit }}
          </div>
          <div
            v-if="item.fournisseur"
            class="text-caption text-medium-emphasis"
          >
            {{ item.fournisseur }}
          </div>
        </div>
      </template>

      <!-- Status indicator -->
      <template #item.status="{ item }">
        <v-chip
          :color="fdsStatusColor(item)"
          size="small"
          :variant="fdsStatusVariant(item)"
        >
          <v-icon
            start
            size="x-small"
          >
            {{ fdsStatusIcon(item) }}
          </v-icon>
          {{ fdsStatusLabel(item) }}
        </v-chip>
      </template>

      <!-- Dates -->
      <template #item.date_emission="{ item }">
        {{ formatDate(item.date_emission) }}
      </template>

      <template #item.date_validite="{ item }">
        <span :class="isExpired(item) ? 'text-red font-weight-bold' : ''">
          {{ formatDate(item.date_validite) }}
        </span>
      </template>

      <!-- Actions -->
      <template #item.actions="{ item }">
        <div class="d-flex ga-1">
          <v-btn
            icon="mdi-download"
            size="small"
            variant="text"
            color="primary"
            title="Telecharger"
            @click="downloadFds(item.id)"
          />
          <v-btn
            icon="mdi-pencil"
            size="small"
            variant="text"
            title="Modifier"
            @click="$emit('edit', item)"
          />
          <v-btn
            icon="mdi-delete"
            size="small"
            variant="text"
            color="red"
            title="Supprimer"
            @click="confirmDelete(item)"
          />
        </div>
      </template>

      <!-- No data -->
      <template #no-data>
        <div class="text-center py-8">
          <v-icon
            size="64"
            color="grey-lighten-1"
            class="mb-4"
          >
            mdi-file-document-alert-outline
          </v-icon>
          <p class="text-h6 text-medium-emphasis">
            Aucune fiche de securite
          </p>
          <p class="text-body-2 text-medium-emphasis">
            Ajoutez des FDS pour vos dechets dangereux.
          </p>
        </div>
      </template>
    </v-data-table>

    <!-- Delete confirmation -->
    <v-dialog
      v-model="showDeleteDialog"
      max-width="400"
    >
      <v-card>
        <v-card-title>Supprimer la FDS</v-card-title>
        <v-card-text>
          Etes-vous sur de vouloir supprimer la FDS "{{ deletingFds?.nom_produit }}" ?
          Le fichier PDF sera egalement supprime.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="text"
            @click="showDeleteDialog = false"
          >
            Annuler
          </v-btn>
          <v-btn
            color="red"
            :loading="deleteLoading"
            @click="doDelete"
          >
            Supprimer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { useUiStore } from '@/stores/ui'

const uiStore = useUiStore()

const emit = defineEmits(['edit', 'delete'])

const fiches = ref([])
const loading = ref(false)
const search = ref('')
const showDeleteDialog = ref(false)
const deletingFds = ref(null)
const deleteLoading = ref(false)

const headers = [
  { title: 'Type dechet', key: 'type_dechet', sortable: true },
  { title: 'Produit', key: 'nom_produit', sortable: true },
  { title: 'Fournisseur', key: 'fournisseur', sortable: true },
  { title: 'Statut', key: 'status', sortable: false, width: '140' },
  { title: 'Date emission', key: 'date_emission', sortable: true, width: '120' },
  { title: 'Date validite', key: 'date_validite', sortable: true, width: '140' },
  { title: 'Actions', key: 'actions', sortable: false, width: '130' },
]

function isExpired(fds) {
  if (!fds.date_validite) return false
  return new Date(fds.date_validite) < new Date()
}

function isExpiringSoon(fds) {
  if (!fds.date_validite) return false
  const validite = new Date(fds.date_validite)
  const in30days = new Date()
  in30days.setDate(in30days.getDate() + 30)
  return validite > new Date() && validite < in30days
}

function fdsStatusColor(fds) {
  if (!fds.actif) return 'grey'
  if (isExpired(fds)) return 'red'
  return 'green'
}

function fdsStatusVariant(fds) {
  return isExpired(fds) ? 'flat' : 'tonal'
}

function fdsStatusIcon(fds) {
  if (!fds.actif) return 'mdi-file-hidden'
  if (isExpired(fds)) return 'mdi-clock-alert'
  return 'mdi-check-circle'
}

function fdsStatusLabel(fds) {
  if (!fds.actif) return 'Inactive'
  if (isExpired(fds)) return 'Expiree'
  return 'Valide'
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR')
}

function formatFileSize(bytes) {
  if (!bytes) return '-'
  if (bytes < 1024) return bytes + ' o'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' Ko'
  return (bytes / (1024 * 1024)).toFixed(1) + ' Mo'
}

async function fetchFiches() {
  loading.value = true
  try {
    const response = await api.get('/fiches-securite', {
      params: { per_page: 100 },
    })
    fiches.value = response.data.data
  } catch (err) {
    uiStore.showError('Erreur lors du chargement des fiches de securite.')
  } finally {
    loading.value = false
  }
}

async function downloadFds(id) {
  try {
    const response = await api.get(`/fiches-securite/${id}/download`, {
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `fds-${id}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (err) {
    uiStore.showError('Erreur lors du telechargement de la FDS.')
  }
}

function confirmDelete(fds) {
  deletingFds.value = fds
  showDeleteDialog.value = true
}

async function doDelete() {
  deleteLoading.value = true
  try {
    await api.delete(`/fiches-securite/${deletingFds.value.id}`)
    fiches.value = fiches.value.filter(f => f.id !== deletingFds.value.id)
    showDeleteDialog.value = false
    deletingFds.value = null
    emit('delete')
  } catch (err) {
    uiStore.showError('Erreur lors de la suppression de la FDS.')
  } finally {
    deleteLoading.value = false
  }
}

function refresh() {
  fetchFiches()
}

defineExpose({ refresh })

onMounted(() => {
  fetchFiches()
})
</script>
