<template>
  <div>
    <h3 class="text-subtitle-1 font-weight-bold mb-3">
      Historique
    </h3>

    <v-progress-linear
      v-if="loading"
      indeterminate
      color="primary"
      class="mb-2"
    />

    <div
      v-if="!loading && historique.length === 0"
      class="text-center py-4"
    >
      <v-icon
        size="40"
        color="grey-lighten-1"
      >
        mdi-history
      </v-icon>
      <p class="text-body-2 text-grey mt-2">
        Aucun evenement enregistre.
      </p>
    </div>

    <v-timeline
      v-else
      density="compact"
      side="end"
    >
      <v-timeline-item
        v-for="event in historique"
        :key="event.id"
        :dot-color="eventColor(event.type_evenement)"
        size="small"
      >
        <v-card
          variant="tonal"
          class="pa-3"
        >
          <div class="d-flex align-center justify-space-between mb-1">
            <v-chip
              size="x-small"
              :color="eventColor(event.type_evenement)"
              variant="flat"
            >
              {{ eventLabel(event.type_evenement) }}
            </v-chip>
            <span class="text-caption text-grey">{{ formatDate(event.date_evenement) }}</span>
          </div>

          <div class="text-body-2 mt-1">
            <span v-if="event.quantite > 0">+</span>{{ formatNumber(event.quantite) }} {{ unite }}
          </div>

          <div class="text-caption text-grey mt-1">
            {{ formatNumber(event.niveau_avant) }} &rarr; {{ formatNumber(event.niveau_apres) }}
          </div>

          <div
            v-if="event.user"
            class="text-caption text-grey mt-1"
          >
            <v-icon size="x-small">
              mdi-account
            </v-icon>
            {{ event.user.prenom }} {{ event.user.nom }}
          </div>

          <div
            v-if="event.reference_type"
            class="text-caption text-grey mt-1"
          >
            <v-icon size="x-small">
              mdi-link
            </v-icon>
            {{ event.reference_type }} #{{ event.reference_id }}
          </div>
        </v-card>
      </v-timeline-item>
    </v-timeline>

    <!-- Pagination -->
    <div
      v-if="totalPages > 1"
      class="d-flex flex-column align-center mt-4"
    >
      <div class="text-caption text-medium-emphasis mb-2">
        {{ (page - 1) * 10 + 1 }} - {{ Math.min(page * 10, totalItems) }} sur {{ totalItems }} resultats
      </div>
      <v-pagination
        v-model="page"
        :length="totalPages"
        :total-visible="5"
        density="compact"
        @update:model-value="fetchHistorique"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useDechetsStore } from '../../../stores/dechets'

const props = defineProps({
  conteneurId: {
    type: Number,
    required: true,
  },
})

const dechetsStore = useDechetsStore()
const loading = ref(false)
const historique = ref([])
const page = ref(1)
const totalPages = ref(1)
const totalItems = ref(0)
const unite = ref('')

const eventTypes = {
  ajout: { label: 'Ajout', color: 'success' },
  retrait: { label: 'Retrait', color: 'info' },
  enlevement: { label: 'Enlevement', color: 'primary' },
  correction: { label: 'Correction', color: 'warning' },
  vidange: { label: 'Vidange', color: 'secondary' },
  production: { label: 'Production', color: 'success' },
}

function eventColor(type) {
  return eventTypes[type]?.color || 'grey'
}

function eventLabel(type) {
  return eventTypes[type]?.label || type
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function formatNumber(val) {
  if (val === null || val === undefined) return '0'
  return Number(val).toLocaleString('fr-FR', { maximumFractionDigits: 3 })
}

async function fetchHistorique() {
  loading.value = true
  try {
    const result = await dechetsStore.fetchHistorique(props.conteneurId, {
      page: page.value,
      per_page: 10,
    })
    historique.value = result.data || []
    totalPages.value = result.last_page || 1
    totalItems.value = result.total || historique.value.length
  } catch {
    historique.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchHistorique()
})
</script>
