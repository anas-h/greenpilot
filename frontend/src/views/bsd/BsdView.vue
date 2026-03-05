<template>
  <div class="bsd-view">
    <v-container fluid>
      <!-- Header -->
      <v-row
        class="mb-4"
        align="center"
      >
        <v-col
          cols="12"
          sm="8"
        >
          <h1 class="text-h5 text-sm-h4 font-weight-bold">
            Bordereaux de Suivi des Dechets
          </h1>
          <p class="text-subtitle-1 text-medium-emphasis">
            Gestion et suivi de vos BSD
          </p>
        </v-col>
        <v-col
          cols="12"
          sm="4"
          class="d-flex justify-sm-end align-center"
        >
          <v-btn
            color="primary"
            prepend-icon="mdi-plus"
            @click="showCreateDialog = true"
          >
            Nouveau BSD
          </v-btn>
        </v-col>
      </v-row>

      <!-- Filters -->
      <v-card
        class="mb-4"
        variant="outlined"
      >
        <v-card-text>
          <v-row dense>
            <v-col
              cols="12"
              md="3"
            >
              <v-select
                v-model="filters.statut"
                :items="statutOptions"
                label="Statut"
                clearable
                multiple
                chips
                closable-chips
                density="compact"
                variant="outlined"
              />
            </v-col>
            <v-col
              cols="12"
              md="2"
            >
              <v-select
                v-model="filters.type_bsd"
                :items="typeBsdOptions"
                label="Type BSD"
                clearable
                density="compact"
                variant="outlined"
              />
            </v-col>
            <v-col
              cols="12"
              md="2"
            >
              <v-text-field
                v-model="filters.date_debut"
                label="Date debut"
                type="date"
                density="compact"
                variant="outlined"
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
                density="compact"
                variant="outlined"
              />
            </v-col>
            <v-col
              cols="12"
              md="3"
            >
              <v-text-field
                v-model="filters.search"
                label="Rechercher..."
                prepend-inner-icon="mdi-magnify"
                clearable
                density="compact"
                variant="outlined"
              />
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <!-- Content -->
      <v-row>
        <!-- BSD List -->
        <v-col
          cols="12"
          :md="selectedBsd ? 7 : 12"
        >
          <BsdList
            :filters="filters"
            :selected-id="selectedBsd?.id"
            @select="onSelectBsd"
            @refresh="refreshList"
          />
        </v-col>

        <!-- Detail Panel -->
        <v-col
          v-if="selectedBsd"
          cols="12"
          md="5"
        >
          <BsdDetail
            :bsd-id="selectedBsd.id"
            @close="selectedBsd = null"
            @updated="refreshList"
          />
        </v-col>
      </v-row>

      <!-- Create Dialog -->
      <v-dialog
        v-model="showCreateDialog"
        max-width="900"
        persistent
      >
        <v-card>
          <v-card-title class="d-flex align-center">
            <span>Nouveau Bordereau</span>
            <v-spacer />
            <v-btn
              icon="mdi-close"
              variant="text"
              title="Fermer"
              @click="showCreateDialog = false"
            />
          </v-card-title>
          <v-card-text>
            <BsdForm
              @saved="onBsdCreated"
              @cancel="showCreateDialog = false"
            />
          </v-card-text>
        </v-card>
      </v-dialog>
    </v-container>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { useBsdStore } from '@/stores/bsd'
import BsdList from './components/BsdList.vue'
import BsdDetail from './components/BsdDetail.vue'
import BsdForm from './components/BsdForm.vue'

const bsdStore = useBsdStore()

const selectedBsd = ref(null)
const showCreateDialog = ref(false)
const listKey = ref(0)

const filters = reactive({
  statut: [],
  type_bsd: null,
  date_debut: null,
  date_fin: null,
  search: null,
})

const statutOptions = [
  { title: 'Brouillon', value: 'DRAFT' },
  { title: 'Scelle', value: 'SEALED' },
  { title: 'Signe producteur', value: 'SIGNED_BY_PRODUCER' },
  { title: 'Envoye', value: 'SENT' },
  { title: 'Recu', value: 'RECEIVED' },
  { title: 'Traite', value: 'PROCESSED' },
  { title: 'Refuse', value: 'REFUSED' },
  { title: 'Annule', value: 'CANCELED' },
]

const typeBsdOptions = [
  { title: 'BSDD - Dechets dangereux', value: 'BSDD' },
  { title: 'BSDA - Amiante', value: 'BSDA' },
  { title: 'BSVHU - VHU', value: 'BSVHU' },
]

function onSelectBsd(bsd) {
  selectedBsd.value = bsd
}

function refreshList() {
  listKey.value++
  bsdStore.fetchBordereaux(filters)
}

function onBsdCreated(bsd) {
  showCreateDialog.value = false
  refreshList()
  selectedBsd.value = bsd
}

// Debounced filter watching
let filterTimeout = null
watch(filters, () => {
  clearTimeout(filterTimeout)
  filterTimeout = setTimeout(() => {
    bsdStore.fetchBordereaux(filters)
  }, 300)
}, { deep: true })
</script>

<style scoped>
.bsd-view {
  min-height: 100%;
}
</style>
