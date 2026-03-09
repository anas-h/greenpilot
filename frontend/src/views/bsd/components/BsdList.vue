<template>
  <v-card style="overflow-x: auto">
    <v-data-table-server
      v-model:items-per-page="itemsPerPage"
      v-model:page="page"
      :headers="headers"
      :items="bsdStore.bordereaux"
      :items-length="bsdStore.totalBordereaux"
      :loading="bsdStore.loading"
      :row-props="getRowProps"
      hover
      density="comfortable"
      @update:options="loadItems"
      @click:row="onRowClick"
    >
      <!-- Numero -->
      <template #item.trackdechets_numero="{ item }">
        <span class="font-weight-medium">
          {{ item.trackdechets_numero || 'Brouillon' }}
        </span>
      </template>

      <!-- Type BSD -->
      <template #item.type_bsd="{ item }">
        <v-chip
          size="small"
          variant="outlined"
          :color="typeBsdColor(item.type_bsd)"
        >
          {{ item.type_bsd }}
        </v-chip>
      </template>

      <!-- Statut -->
      <template #item.statut="{ item }">
        <v-chip
          size="small"
          :color="statutColor(item.statut)"
          :variant="statutVariant(item.statut)"
        >
          <v-icon
            start
            size="x-small"
          >
            {{ statutIcon(item.statut) }}
          </v-icon>
          {{ statutLabel(item.statut) }}
        </v-chip>
      </template>

      <!-- Dechet -->
      <template #item.denomination_dechet="{ item }">
        <div>
          <div class="text-body-2">
            {{ item.denomination_dechet }}
          </div>
          <div class="text-caption text-medium-emphasis">
            {{ item.code_dechet }}
          </div>
        </div>
      </template>

      <!-- Quantite -->
      <template #item.quantite="{ item }">
        <span v-if="item.quantite">
          {{ item.quantite }} {{ item.unite || '' }}
        </span>
        <span
          v-else
          class="text-medium-emphasis"
        >-</span>
      </template>

      <!-- Destination -->
      <template #item.destination_raison_sociale="{ item }">
        <div v-if="item.destination_raison_sociale">
          <div class="text-body-2">
            {{ item.destination_raison_sociale }}
          </div>
          <div class="text-caption text-medium-emphasis">
            {{ item.destination_siret }}
          </div>
        </div>
        <span
          v-else
          class="text-medium-emphasis"
        >-</span>
      </template>

      <!-- Date emission -->
      <template #item.date_emission="{ item }">
        {{ formatDate(item.date_emission) }}
      </template>

      <!-- Actions -->
      <template #item.actions="{ item }">
        <v-btn
          icon="mdi-eye"
          size="small"
          variant="text"
          @click.stop="$emit('select', item)"
        />
      </template>

      <!-- No data -->
      <template #no-data>
        <div class="text-center py-8">
          <v-icon
            size="64"
            color="grey-lighten-1"
            class="mb-4"
          >
            mdi-file-document-outline
          </v-icon>
          <p class="text-h6 text-medium-emphasis">
            Aucun bordereau trouve
          </p>
          <p class="text-body-2 text-medium-emphasis">
            Ajustez vos filtres ou creez un nouveau bordereau.
          </p>
        </div>
      </template>
    </v-data-table-server>
  </v-card>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useBsdStore } from '@/stores/bsd'

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({}),
  },
  selectedId: {
    type: [Number, null],
    default: null,
  },
})

const emit = defineEmits(['select', 'refresh'])

const bsdStore = useBsdStore()
const page = ref(1)
const itemsPerPage = ref(20)

const headers = [
  { title: 'Numero', key: 'trackdechets_numero', sortable: true },
  { title: 'Type', key: 'type_bsd', sortable: true, width: '100' },
  { title: 'Statut', key: 'statut', sortable: true, width: '160' },
  { title: 'Dechet', key: 'denomination_dechet', sortable: true },
  { title: 'Quantite', key: 'quantite', sortable: true, width: '120' },
  { title: 'Destination', key: 'destination_raison_sociale', sortable: false },
  { title: 'Date', key: 'date_emission', sortable: true, width: '110' },
  { title: '', key: 'actions', sortable: false, width: '50' },
]

const STATUT_CONFIG = {
  DRAFT:              { label: 'Brouillon',        color: 'grey',   variant: 'outlined', icon: 'mdi-pencil' },
  SEALED:             { label: 'Scelle',           color: 'blue',   variant: 'tonal',    icon: 'mdi-lock' },
  SIGNED_BY_PRODUCER: { label: 'Signe',            color: 'indigo', variant: 'tonal',    icon: 'mdi-draw' },
  SENT:               { label: 'Envoye',           color: 'orange', variant: 'tonal',    icon: 'mdi-truck' },
  RECEIVED:           { label: 'Recu',             color: 'teal',   variant: 'tonal',    icon: 'mdi-package-down' },
  PROCESSED:          { label: 'Traite',           color: 'green',  variant: 'flat',     icon: 'mdi-check-circle' },
  REFUSED:            { label: 'Refuse',           color: 'red',    variant: 'flat',     icon: 'mdi-close-circle' },
  CANCELED:           { label: 'Annule',           color: 'grey',   variant: 'flat',     icon: 'mdi-cancel' },
}

function statutColor(statut) {
  return STATUT_CONFIG[statut]?.color || 'grey'
}

function statutVariant(statut) {
  return STATUT_CONFIG[statut]?.variant || 'tonal'
}

function statutLabel(statut) {
  return STATUT_CONFIG[statut]?.label || statut
}

function statutIcon(statut) {
  return STATUT_CONFIG[statut]?.icon || 'mdi-file-document'
}

function typeBsdColor(type) {
  const colors = { BSDD: 'deep-purple', BSDA: 'brown', BSVHU: 'blue-grey' }
  return colors[type] || 'grey'
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

function getRowProps({ item }) {
  return {
    class: item.id === props.selectedId ? 'bg-blue-lighten-5' : '',
    style: 'cursor: pointer',
  }
}

function onRowClick(event, { item }) {
  emit('select', item)
}

function loadItems({ page: p, itemsPerPage: ipp, sortBy }) {
  const params = {
    ...props.filters,
    page: p,
    per_page: ipp,
  }
  if (props.filters.statut?.length) {
    params.statut = props.filters.statut.join(',')
  }
  if (sortBy?.length) {
    params.sort_by = sortBy[0].key
    params.sort_dir = sortBy[0].order
  }
  bsdStore.fetchBordereaux(params)
}

watch(() => props.filters, () => {
  page.value = 1
}, { deep: true })
</script>
