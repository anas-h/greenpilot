<template>
  <v-card
    class="bsd-detail"
    elevation="2"
  >
    <!-- Header -->
    <v-card-title class="d-flex align-center pa-4">
      <div>
        <div class="text-h6">
          {{ bsd?.trackdechets_numero || 'Brouillon' }}
        </div>
        <div class="text-caption text-medium-emphasis">
          {{ bsd?.type_bsd }} - Cree le {{ formatDate(bsd?.created_at) }}
        </div>
      </div>
      <v-spacer />
      <v-chip
        :color="statutColor(bsd?.statut)"
        :variant="statutVariant(bsd?.statut)"
        class="mr-2"
      >
        {{ statutLabel(bsd?.statut) }}
      </v-chip>
      <v-btn
        icon="mdi-close"
        variant="text"
        size="small"
        title="Fermer"
        @click="$emit('close')"
      />
    </v-card-title>

    <v-divider />

    <v-card-text
      v-if="loading"
      class="text-center py-8"
    >
      <v-progress-circular
        indeterminate
        color="primary"
      />
    </v-card-text>

    <template v-else-if="bsd">
      <v-card-text class="pa-4">
        <!-- Timeline -->
        <BsdTimeline
          :bsd="bsd"
          class="mb-6"
        />

        <!-- Producteur -->
        <v-card
          variant="tonal"
          color="blue"
          class="mb-4"
        >
          <v-card-subtitle class="font-weight-bold pt-3">
            <v-icon
              start
              size="small"
            >
              mdi-factory
            </v-icon>
            Producteur (Emetteur)
          </v-card-subtitle>
          <v-card-text>
            <div class="text-body-2">
              <strong>{{ bsd.garage?.entreprise?.raison_sociale }}</strong>
            </div>
            <div class="text-caption">
              SIRET: {{ bsd.garage?.entreprise?.siret }}
            </div>
            <div class="text-caption">
              {{ bsd.garage?.adresse }}, {{ bsd.garage?.code_postal }} {{ bsd.garage?.ville }}
            </div>
            <div
              v-if="bsd.date_signature_producteur"
              class="text-caption mt-1"
            >
              <v-icon
                size="x-small"
                color="success"
              >
                mdi-check
              </v-icon>
              Signe le {{ formatDateTime(bsd.date_signature_producteur) }}
              par {{ bsd.cree_par ? (bsd.cree_par.nom + ' ' + bsd.cree_par.prenom) : 'le producteur' }}
            </div>
          </v-card-text>
        </v-card>

        <!-- Transporteur -->
        <v-card
          variant="tonal"
          color="orange"
          class="mb-4"
        >
          <v-card-subtitle class="font-weight-bold pt-3">
            <v-icon
              start
              size="small"
            >
              mdi-truck
            </v-icon>
            Transporteur
          </v-card-subtitle>
          <v-card-text>
            <template v-if="bsd.transporteur_raison_sociale">
              <div class="text-body-2">
                <strong>{{ bsd.transporteur_raison_sociale }}</strong>
              </div>
              <div class="text-caption">
                SIRET: {{ bsd.transporteur_siret }}
              </div>
              <div
                v-if="bsd.transporteur_adresse"
                class="text-caption"
              >
                {{ bsd.transporteur_adresse }}
              </div>
              <div
                v-if="bsd.transporteur_recepisse"
                class="text-caption"
              >
                Recepisse: {{ bsd.transporteur_recepisse }}
              </div>
              <div
                v-if="bsd.date_enlevement"
                class="text-caption mt-1"
              >
                <v-icon
                  size="x-small"
                  color="success"
                >
                  mdi-check
                </v-icon>
                Enlevement le {{ formatDateTime(bsd.date_enlevement) }}
              </div>
            </template>
            <span
              v-else
              class="text-caption text-medium-emphasis"
            >Non renseigne</span>
          </v-card-text>
        </v-card>

        <!-- Destination -->
        <v-card
          variant="tonal"
          color="teal"
          class="mb-4"
        >
          <v-card-subtitle class="font-weight-bold pt-3">
            <v-icon
              start
              size="small"
            >
              mdi-domain
            </v-icon>
            Destination
          </v-card-subtitle>
          <v-card-text>
            <template v-if="bsd.destination_raison_sociale">
              <div class="text-body-2">
                <strong>{{ bsd.destination_raison_sociale }}</strong>
              </div>
              <div class="text-caption">
                SIRET: {{ bsd.destination_siret }}
              </div>
              <div
                v-if="bsd.destination_adresse"
                class="text-caption"
              >
                {{ bsd.destination_adresse }}
              </div>
              <div
                v-if="bsd.cap"
                class="text-caption"
              >
                CAP: {{ bsd.cap }}
              </div>
              <div
                v-if="bsd.code_traitement"
                class="text-caption"
              >
                Traitement: {{ bsd.code_traitement }}
              </div>
              <div
                v-if="bsd.date_reception"
                class="text-caption mt-1"
              >
                <v-icon
                  size="x-small"
                  color="success"
                >
                  mdi-check
                </v-icon>
                Recu le {{ formatDateTime(bsd.date_reception) }}
              </div>
              <div
                v-if="bsd.date_traitement"
                class="text-caption mt-1"
              >
                <v-icon
                  size="x-small"
                  color="success"
                >
                  mdi-check
                </v-icon>
                Traite le {{ formatDateTime(bsd.date_traitement) }}
              </div>
            </template>
            <span
              v-else
              class="text-caption text-medium-emphasis"
            >Non renseigne</span>
          </v-card-text>
        </v-card>

        <!-- Dechet -->
        <v-card
          variant="tonal"
          color="purple"
          class="mb-4"
        >
          <v-card-subtitle class="font-weight-bold pt-3">
            <v-icon
              start
              size="small"
            >
              mdi-delete-variant
            </v-icon>
            Dechet
          </v-card-subtitle>
          <v-card-text>
            <v-row dense>
              <v-col cols="6">
                <div class="text-caption text-medium-emphasis">
                  Code dechet
                </div>
                <div class="text-body-2 font-weight-medium">
                  {{ bsd.code_dechet }}
                </div>
              </v-col>
              <v-col cols="6">
                <div class="text-caption text-medium-emphasis">
                  Denomination
                </div>
                <div class="text-body-2">
                  {{ bsd.denomination_dechet }}
                </div>
              </v-col>
              <v-col cols="4">
                <div class="text-caption text-medium-emphasis">
                  Quantite
                </div>
                <div class="text-body-2">
                  {{ bsd.quantite || '-' }} {{ bsd.unite || '' }}
                </div>
              </v-col>
              <v-col cols="4">
                <div class="text-caption text-medium-emphasis">
                  Consistance
                </div>
                <div class="text-body-2">
                  {{ bsd.consistance || '-' }}
                </div>
              </v-col>
              <v-col cols="4">
                <div class="text-caption text-medium-emphasis">
                  Conditionnement
                </div>
                <div class="text-body-2">
                  {{ bsd.conditionnement || '-' }}
                </div>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <!-- REFUSED section -->
        <BsdRefusedActions
          v-if="bsd.statut === 'REFUSED'"
          :bsd="bsd"
          class="mb-4"
          @dupliquer="onDupliquer"
        />

        <!-- Observations -->
        <div
          v-if="bsd.observations"
          class="mb-4"
        >
          <div class="text-subtitle-2 text-medium-emphasis mb-1">
            Observations
          </div>
          <div class="text-body-2">
            {{ bsd.observations }}
          </div>
        </div>

        <!-- Trackdechets link -->
        <div
          v-if="bsd.trackdechets_id"
          class="mb-4"
        >
          <v-chip
            color="green"
            variant="outlined"
            size="small"
            prepend-icon="mdi-link"
          >
            Trackdechets: {{ bsd.trackdechets_id }}
          </v-chip>
        </div>
      </v-card-text>

      <v-divider />

      <!-- Action buttons -->
      <v-card-actions class="pa-4">
        <v-btn
          v-if="bsd.statut === 'DRAFT'"
          color="blue"
          variant="tonal"
          prepend-icon="mdi-lock"
          :loading="actionLoading"
          @click="onPublish"
        >
          Sceller
        </v-btn>

        <v-btn
          v-if="bsd.statut === 'SEALED'"
          color="indigo"
          variant="tonal"
          prepend-icon="mdi-draw"
          :loading="actionLoading"
          @click="onSign"
        >
          Signer
        </v-btn>

        <v-btn
          v-if="bsd.pdf_path || bsd.trackdechets_id"
          color="grey"
          variant="tonal"
          prepend-icon="mdi-file-pdf-box"
          @click="onDownloadPdf"
        >
          PDF
        </v-btn>

        <v-spacer />

        <v-btn
          v-if="canCancel"
          color="red"
          variant="text"
          prepend-icon="mdi-cancel"
          @click="showCancelDialog = true"
        >
          Annuler
        </v-btn>

        <v-btn
          v-if="bsd.statut === 'DRAFT'"
          color="red"
          variant="text"
          prepend-icon="mdi-delete"
          @click="showDeleteDialog = true"
        >
          Supprimer
        </v-btn>
      </v-card-actions>
    </template>

    <!-- Cancel Dialog -->
    <v-dialog
      v-model="showCancelDialog"
      max-width="500"
    >
      <v-card>
        <v-card-title>Annuler le bordereau</v-card-title>
        <v-card-text>
          <v-textarea
            v-model="motifAnnulation"
            label="Motif d'annulation *"
            rows="3"
            variant="outlined"
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="text"
            @click="showCancelDialog = false"
          >
            Retour
          </v-btn>
          <v-btn
            color="red"
            :disabled="!motifAnnulation"
            :loading="actionLoading"
            @click="onCancel"
          >
            Confirmer l'annulation
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete Dialog -->
    <v-dialog
      v-model="showDeleteDialog"
      max-width="400"
    >
      <v-card>
        <v-card-title>Supprimer le brouillon</v-card-title>
        <v-card-text>
          Etes-vous sur de vouloir supprimer ce brouillon ? Cette action est irreversible.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="text"
            @click="showDeleteDialog = false"
          >
            Retour
          </v-btn>
          <v-btn
            color="red"
            :loading="actionLoading"
            @click="onDelete"
          >
            Supprimer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useBsdStore } from '@/stores/bsd'
import BsdTimeline from './BsdTimeline.vue'
import BsdRefusedActions from './BsdRefusedActions.vue'

const props = defineProps({
  bsdId: {
    type: Number,
    required: true,
  },
})

const emit = defineEmits(['close', 'updated'])

const bsdStore = useBsdStore()
const bsd = ref(null)
const loading = ref(false)
const actionLoading = ref(false)
const showCancelDialog = ref(false)
const showDeleteDialog = ref(false)
const motifAnnulation = ref('')

const STATUT_CONFIG = {
  DRAFT:              { label: 'Brouillon',   color: 'grey',   variant: 'outlined' },
  SEALED:             { label: 'Scelle',      color: 'blue',   variant: 'tonal' },
  SIGNED_BY_PRODUCER: { label: 'Signe',       color: 'indigo', variant: 'tonal' },
  SENT:               { label: 'Envoye',      color: 'orange', variant: 'tonal' },
  RECEIVED:           { label: 'Recu',        color: 'teal',   variant: 'tonal' },
  PROCESSED:          { label: 'Traite',      color: 'green',  variant: 'flat' },
  REFUSED:            { label: 'Refuse',      color: 'red',    variant: 'flat' },
  CANCELED:           { label: 'Annule',      color: 'grey',   variant: 'flat' },
}

const canCancel = computed(() => {
  return bsd.value && ['DRAFT', 'SEALED', 'SIGNED_BY_PRODUCER', 'SENT'].includes(bsd.value.statut)
})

function statutColor(statut) {
  return STATUT_CONFIG[statut]?.color || 'grey'
}

function statutVariant(statut) {
  return STATUT_CONFIG[statut]?.variant || 'tonal'
}

function statutLabel(statut) {
  return STATUT_CONFIG[statut]?.label || statut
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR')
}

function formatDateTime(date) {
  if (!date) return '-'
  return new Date(date).toLocaleString('fr-FR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

async function fetchBsd() {
  loading.value = true
  try {
    bsd.value = await bsdStore.fetchBordereau(props.bsdId)
  } finally {
    loading.value = false
  }
}

async function onPublish() {
  actionLoading.value = true
  try {
    await bsdStore.publishBordereau(props.bsdId)
    await fetchBsd()
    emit('updated')
  } finally {
    actionLoading.value = false
  }
}

async function onSign() {
  actionLoading.value = true
  try {
    await bsdStore.signBordereau(props.bsdId)
    await fetchBsd()
    emit('updated')
  } finally {
    actionLoading.value = false
  }
}

async function onCancel() {
  actionLoading.value = true
  try {
    await bsdStore.cancelBordereau(props.bsdId, motifAnnulation.value)
    showCancelDialog.value = false
    motifAnnulation.value = ''
    await fetchBsd()
    emit('updated')
  } finally {
    actionLoading.value = false
  }
}

async function onDelete() {
  actionLoading.value = true
  try {
    await bsdStore.deleteBordereau(props.bsdId)
    showDeleteDialog.value = false
    emit('close')
    emit('updated')
  } finally {
    actionLoading.value = false
  }
}

async function onDupliquer() {
  actionLoading.value = true
  try {
    const newBsd = await bsdStore.dupliquerBordereau(props.bsdId)
    emit('updated')
    bsd.value = newBsd
  } finally {
    actionLoading.value = false
  }
}

function onDownloadPdf() {
  bsdStore.downloadPdf(props.bsdId)
}

watch(() => props.bsdId, () => {
  fetchBsd()
}, { immediate: true })
</script>

<style scoped>
.bsd-detail {
  position: sticky;
  top: 80px;
  max-height: calc(100vh - 100px);
  overflow-y: auto;
}
</style>
