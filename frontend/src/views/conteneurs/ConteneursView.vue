<template>
  <div>
    <!-- Toolbar -->
    <div class="d-flex flex-wrap align-center justify-space-between mb-6 ga-3">
      <div>
        <h1 class="text-h5 text-sm-h4 font-weight-bold">
          Conteneurs
        </h1>
        <p class="text-body-2 text-grey mt-1">
          Gestion des conteneurs de dechets du garage
        </p>
      </div>
      <div class="d-flex flex-wrap ga-2">
        <v-btn
          color="secondary"
          variant="outlined"
          prepend-icon="mdi-qrcode-scan"
          size="default"
          @click="showScanner = true"
        >
          Scanner QR
        </v-btn>
        <v-btn
          color="primary"
          prepend-icon="mdi-plus"
          size="default"
          @click="openCreateDialog"
        >
          Nouveau conteneur
        </v-btn>
      </div>
    </div>

    <!-- Filters -->
    <v-card class="mb-4 pa-4">
      <v-row dense>
        <v-col
          cols="12"
          sm="4"
        >
          <v-text-field
            v-model="search"
            label="Rechercher"
            prepend-inner-icon="mdi-magnify"
            clearable
            hide-details
            @update:model-value="debouncedFetch"
          />
        </v-col>
        <v-col
          cols="12"
          sm="4"
        >
          <v-select
            v-model="filterType"
            label="Type de dechet"
            :items="typeOptions"
            item-title="text"
            item-value="value"
            clearable
            hide-details
            @update:model-value="fetchData"
          />
        </v-col>
        <v-col
          cols="12"
          sm="4"
          class="d-flex align-center justify-end"
        >
          <v-btn-toggle
            v-model="viewMode"
            mandatory
            density="compact"
            variant="outlined"
          >
            <v-btn
              value="grid"
              icon="mdi-view-grid"
              title="Vue grille"
            />
            <v-btn
              value="list"
              icon="mdi-view-list"
              title="Vue liste"
            />
          </v-btn-toggle>
        </v-col>
      </v-row>
    </v-card>

    <!-- Conformite alert -->
    <v-alert
      v-if="conformite && !conformite.conforme"
      type="warning"
      variant="tonal"
      class="mb-4"
      closable
    >
      <div class="font-weight-bold">
        Non-conformite detectee
      </div>
      <ul class="mt-1">
        <li
          v-for="(issue, i) in conformite.issues"
          :key="i"
        >
          {{ issue }}
        </li>
      </ul>
    </v-alert>

    <!-- Loading -->
    <v-progress-linear
      v-if="dechetsStore.loadingConteneurs"
      indeterminate
      color="primary"
      class="mb-4"
    />

    <!-- Grid -->
    <ConteneurGrid
      :conteneurs="dechetsStore.conteneurs"
      :view-mode="viewMode"
      @edit="openEditDialog"
      @delete="handleDelete"
      @show="openDetailDialog"
      @download-qr="handleDownloadQr"
    />

    <!-- Empty state -->
    <v-card
      v-if="!dechetsStore.loadingConteneurs && dechetsStore.conteneurs.length === 0"
      class="pa-8 text-center"
    >
      <v-icon
        size="64"
        color="grey-lighten-1"
      >
        mdi-delete-empty
      </v-icon>
      <h3 class="text-h6 mt-4 text-grey">
        Aucun conteneur
      </h3>
      <p class="text-body-2 text-grey mt-2">
        Ajoutez votre premier conteneur pour commencer le suivi des dechets.
      </p>
      <v-btn
        color="primary"
        class="mt-4"
        prepend-icon="mdi-plus"
        @click="openCreateDialog"
      >
        Ajouter un conteneur
      </v-btn>
    </v-card>

    <!-- Create/Edit dialog -->
    <ConteneurDialog
      v-model="dialogOpen"
      :conteneur="selectedConteneur"
      :types="dechetsStore.typesDechets"
      @saved="onSaved"
    />

    <!-- Detail dialog with historique -->
    <v-dialog
      v-model="detailDialogOpen"
      max-width="700"
      scrollable
    >
      <v-card v-if="detailConteneur">
        <v-card-title class="d-flex align-center justify-space-between">
          <span>{{ detailConteneur.nom }}</span>
          <v-btn
            icon="mdi-close"
            variant="text"
            title="Fermer"
            @click="detailDialogOpen = false"
          />
        </v-card-title>
        <v-card-text>
          <v-row>
            <v-col
              cols="12"
              sm="6"
            >
              <ConteneurJauge
                :niveau="Number(detailConteneur.niveau_actuel)"
                :capacite="Number(detailConteneur.capacite)"
                :unite="detailConteneur.unite"
                height="200"
              />
            </v-col>
            <v-col
              cols="12"
              sm="6"
            >
              <div class="text-body-2 mb-2">
                <strong>Type:</strong> {{ detailConteneur.type_dechet?.denomination }}
              </div>
              <div class="text-body-2 mb-2">
                <strong>Emplacement:</strong> {{ detailConteneur.emplacement || '-' }}
              </div>
              <div class="text-body-2 mb-2">
                <strong>Code QR:</strong> {{ detailConteneur.code_qr }}
              </div>
              <div class="text-body-2 mb-2">
                <strong>Mixte:</strong> {{ detailConteneur.mixte ? 'Oui' : 'Non' }}
              </div>
              <div class="text-body-2 mb-2">
                <strong>Mise en service:</strong> {{ detailConteneur.date_mise_en_service || '-' }}
              </div>
            </v-col>
          </v-row>
          <v-divider class="my-4" />
          <ConteneurHistorique :conteneur-id="detailConteneur.id" />
        </v-card-text>
      </v-card>
    </v-dialog>

    <ConfirmDialog
      v-model="confirmDialogOpen"
      title="Confirmer la suppression"
      :message="pendingDelete ? `Supprimer le conteneur &quot;${pendingDelete.nom}&quot; ?` : ''"
      confirm-text="Supprimer"
      @confirm="doDelete"
    />

    <!-- QR Scanner dialog -->
    <v-dialog
      v-model="showScanner"
      max-width="500"
    >
      <v-card>
        <v-card-title class="d-flex align-center justify-space-between">
          <span>Scanner un QR Code</span>
          <v-btn
            icon="mdi-close"
            variant="text"
            title="Fermer"
            @click="showScanner = false"
          />
        </v-card-title>
        <v-card-text>
          <QrScanner @decoded="onQrDecoded" />
        </v-card-text>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useDechetsStore } from '../../stores/dechets'
import { useUiStore } from '../../stores/ui'
import ConteneurGrid from './components/ConteneurGrid.vue'
import ConteneurDialog from './components/ConteneurDialog.vue'
import ConteneurJauge from './components/ConteneurJauge.vue'
import ConteneurHistorique from './components/ConteneurHistorique.vue'
import QrScanner from '../../components/QrScanner.vue'
import ConfirmDialog from '../../components/ConfirmDialog.vue'

const dechetsStore = useDechetsStore()
const uiStore = useUiStore()

const search = ref('')
const filterType = ref(null)
const viewMode = ref('grid')
const dialogOpen = ref(false)
const selectedConteneur = ref(null)
const detailDialogOpen = ref(false)
const detailConteneur = ref(null)
const showScanner = ref(false)
const conformite = ref(null)
const confirmDialogOpen = ref(false)
const pendingDelete = ref(null)

let debounceTimer = null

const typeOptions = computed(() => {
  return dechetsStore.typesDechets.map(t => ({
    text: `${t.denomination}${t.dangereux ? ' (DD)' : ''}`,
    value: t.id,
  }))
})

function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetchData(), 300)
}

async function fetchData() {
  const params = {}
  if (search.value) params.search = search.value
  if (filterType.value) params.type_dechet_id = filterType.value
  await dechetsStore.fetchConteneurs(params)
}

function openCreateDialog() {
  selectedConteneur.value = null
  dialogOpen.value = true
}

function openEditDialog(conteneur) {
  selectedConteneur.value = { ...conteneur }
  dialogOpen.value = true
}

async function openDetailDialog(conteneur) {
  try {
    detailConteneur.value = await dechetsStore.fetchConteneur(conteneur.id)
    detailDialogOpen.value = true
  } catch {
    uiStore.showError('Erreur lors du chargement du conteneur.')
  }
}

function handleDelete(conteneur) {
  pendingDelete.value = conteneur
  confirmDialogOpen.value = true
}

async function doDelete() {
  confirmDialogOpen.value = false
  try {
    await dechetsStore.deleteConteneur(pendingDelete.value.id)
    uiStore.showSuccess('Conteneur supprime.')
  } catch {
    uiStore.showError('Erreur lors de la suppression.')
  } finally {
    pendingDelete.value = null
  }
}

async function handleDownloadQr(conteneur) {
  try {
    const response = await dechetsStore.downloadQrCode(conteneur.id)
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `qr-${conteneur.code_qr}.svg`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch {
    uiStore.showError('Erreur lors du telechargement du QR code.')
  }
}

function onSaved() {
  dialogOpen.value = false
  fetchData()
  uiStore.showSuccess(selectedConteneur.value ? 'Conteneur mis a jour.' : 'Conteneur cree.')
}

async function onQrDecoded(code) {
  showScanner.value = false
  try {
    const conteneur = await dechetsStore.scanQrCode(code)
    detailConteneur.value = conteneur
    detailDialogOpen.value = true
  } catch {
    uiStore.showError('Conteneur non trouve pour ce QR code.')
  }
}

async function loadConformite() {
  try {
    conformite.value = await dechetsStore.checkConformite()
  } catch {
    uiStore.showError('Erreur lors de la verification de conformite.')
  }
}

onMounted(async () => {
  await Promise.all([
    dechetsStore.fetchTypes(),
    fetchData(),
    loadConformite(),
  ])
})
</script>
