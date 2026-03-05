<template>
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between mb-6 ga-3">
      <div>
        <h1 class="text-h5 text-sm-h4 font-weight-bold text-primary">
          Collecteurs
        </h1>
        <p class="text-body-2 text-grey mt-1">
          Gestion des collecteurs et comparatif des tarifs
        </p>
      </div>
      <div class="d-flex flex-wrap ga-2">
        <v-btn
          :variant="showComparatif ? 'flat' : 'outlined'"
          color="primary"
          prepend-icon="mdi-table"
          @click="showComparatif = !showComparatif"
        >
          Comparatif tarifs
        </v-btn>
        <v-btn
          color="primary"
          prepend-icon="mdi-plus"
          @click="openDialog()"
        >
          Ajouter un collecteur
        </v-btn>
      </div>
    </div>

    <v-card
      v-if="!showComparatif"
      class="mb-6"
    >
      <v-card-text>
        <v-row>
          <v-col
            cols="12"
            md="4"
          >
            <v-text-field
              v-model="search"
              prepend-inner-icon="mdi-magnify"
              label="Rechercher un collecteur..."
              clearable
              hide-details
            />
          </v-col>
          <v-col
            cols="12"
            md="3"
          >
            <v-select
              v-model="filterActif"
              :items="statutOptions"
              label="Statut"
              clearable
              hide-details
            />
          </v-col>
          <v-col
            cols="12"
            md="3"
          >
            <v-select
              v-model="filterAutorisation"
              :items="autorisationOptions"
              label="Autorisation"
              clearable
              hide-details
            />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <ComparatifTarifs v-if="showComparatif" />

    <CollecteursList
      v-else
      :search="search"
      :filter-actif="filterActif"
      :filter-autorisation="filterAutorisation"
      :collecteurs="collecteurs"
      :loading="loading"
      @edit="openDialog"
      @delete="deleteCollecteur"
    />

    <CollecteurDialog
      v-model="dialogOpen"
      :collecteur="editingCollecteur"
      @saved="onSaved"
    />

    <ConfirmDialog
      v-model="confirmDialogOpen"
      title="Confirmer la suppression"
      :message="pendingDelete ? `Supprimer le collecteur &quot;${pendingDelete.raison_sociale}&quot; ?` : ''"
      confirm-text="Supprimer"
      @confirm="doDelete"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'
import { useUiStore } from '../../stores/ui'
import CollecteursList from './components/CollecteursList.vue'
import CollecteurDialog from './components/CollecteurDialog.vue'
import ComparatifTarifs from './components/ComparatifTarifs.vue'
import ConfirmDialog from '../../components/ConfirmDialog.vue'

const uiStore = useUiStore()

const collecteurs = ref([])
const loading = ref(false)
const search = ref('')
const filterActif = ref(null)
const filterAutorisation = ref(null)
const showComparatif = ref(false)
const dialogOpen = ref(false)
const editingCollecteur = ref(null)
const confirmDialogOpen = ref(false)
const pendingDelete = ref(null)

const statutOptions = [
  { title: 'Actif', value: true },
  { title: 'Inactif', value: false },
]

const autorisationOptions = [
  { title: 'Valide', value: true },
  { title: 'Expiree / Manquante', value: false },
]

async function fetchCollecteurs() {
  loading.value = true
  try {
    const params = {}
    if (search.value) params.search = search.value
    if (filterActif.value !== null && filterActif.value !== undefined) params.actif = filterActif.value
    if (filterAutorisation.value !== null && filterAutorisation.value !== undefined) params.autorisation_valide = filterAutorisation.value
    const { data } = await api.get('/collecteurs', { params })
    collecteurs.value = data.data || []
  } catch (e) {
    uiStore.showError('Erreur lors du chargement des collecteurs.')
  } finally {
    loading.value = false
  }
}

function openDialog(collecteur = null) {
  editingCollecteur.value = collecteur
  dialogOpen.value = true
}

function deleteCollecteur(collecteur) {
  pendingDelete.value = collecteur
  confirmDialogOpen.value = true
}

async function doDelete() {
  confirmDialogOpen.value = false
  try {
    await api.delete(`/collecteurs/${pendingDelete.value.id}`)
    uiStore.showSuccess('Collecteur supprime.')
    fetchCollecteurs()
  } catch (e) {
    uiStore.showError('Erreur lors de la suppression.')
  } finally {
    pendingDelete.value = null
  }
}

function onSaved() {
  fetchCollecteurs()
}

onMounted(fetchCollecteurs)
</script>
