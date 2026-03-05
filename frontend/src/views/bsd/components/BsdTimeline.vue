<template>
  <div class="bsd-timeline">
    <div class="text-subtitle-2 text-medium-emphasis mb-3">
      <v-icon
        start
        size="small"
      >
        mdi-timeline-clock
      </v-icon>
      Cycle de vie du bordereau
    </div>

    <!-- Visual step indicators -->
    <div class="timeline-steps d-flex align-center mb-4">
      <div
        v-for="(step, index) in lifecycleSteps"
        :key="step.statut"
        class="timeline-step d-flex align-center"
        :class="{ 'flex-grow-1': index < lifecycleSteps.length - 1 }"
      >
        <!-- Step dot -->
        <v-tooltip :text="step.label + (step.date ? ' - ' + formatDate(step.date) : '')">
          <template #activator="{ props: tooltipProps }">
            <div
              v-bind="tooltipProps"
              class="step-dot d-flex align-center justify-center"
              :class="stepClass(step)"
            >
              <v-icon
                v-if="step.completed"
                size="14"
                color="white"
              >
                mdi-check
              </v-icon>
              <v-icon
                v-else-if="step.current"
                size="14"
                color="white"
              >
                {{ step.icon }}
              </v-icon>
              <span
                v-else
                class="step-number text-caption"
              >{{ index + 1 }}</span>
            </div>
          </template>
        </v-tooltip>

        <!-- Connector line -->
        <div
          v-if="index < lifecycleSteps.length - 1"
          class="step-line flex-grow-1 mx-1"
          :class="{ 'line-completed': step.completed }"
        />
      </div>
    </div>

    <!-- Step labels -->
    <div class="timeline-labels d-flex mb-4">
      <div
        v-for="(step, index) in lifecycleSteps"
        :key="'label-' + step.statut"
        class="step-label text-center"
        :class="{
          'flex-grow-1': index < lifecycleSteps.length - 1,
          'text-medium-emphasis': !step.completed && !step.current,
          'font-weight-bold': step.current,
        }"
      >
        <div class="text-caption">
          {{ step.label }}
        </div>
        <div
          v-if="step.date"
          class="text-caption text-medium-emphasis"
        >
          {{ formatDate(step.date) }}
        </div>
      </div>
    </div>

    <!-- Detailed history (from statut_histories) -->
    <v-expansion-panels
      v-if="bsd.statut_histories?.length"
      variant="accordion"
      density="compact"
    >
      <v-expansion-panel>
        <v-expansion-panel-title class="text-caption">
          <v-icon
            start
            size="small"
          >
            mdi-history
          </v-icon>
          Historique detaille ({{ bsd.statut_histories.length }} evenements)
        </v-expansion-panel-title>
        <v-expansion-panel-text>
          <v-timeline
            density="compact"
            side="end"
            line-thickness="1"
          >
            <v-timeline-item
              v-for="history in sortedHistories"
              :key="history.id"
              :dot-color="getHistoryDotColor(history.nouveau_statut)"
              size="x-small"
            >
              <div class="d-flex align-center">
                <div>
                  <div class="text-body-2 font-weight-medium">
                    {{ statutLabel(history.ancien_statut) }}
                    <v-icon
                      size="x-small"
                      class="mx-1"
                    >
                      mdi-arrow-right
                    </v-icon>
                    {{ statutLabel(history.nouveau_statut) }}
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    {{ formatDateTime(history.date_changement) }}
                    <span v-if="history.user"> - {{ history.user?.nom }} {{ history.user?.prenom }}</span>
                  </div>
                  <div
                    v-if="history.commentaire"
                    class="text-caption mt-1"
                  >
                    {{ history.commentaire }}
                  </div>
                </div>
              </div>
            </v-timeline-item>
          </v-timeline>
        </v-expansion-panel-text>
      </v-expansion-panel>
    </v-expansion-panels>

    <!-- Special statuses -->
    <v-alert
      v-if="bsd.statut === 'REFUSED'"
      type="error"
      variant="tonal"
      density="compact"
      class="mt-3"
    >
      <div class="font-weight-bold">
        BSD Refuse
      </div>
      <div
        v-if="bsd.motif_refus"
        class="text-body-2 mt-1"
      >
        Motif : {{ bsd.motif_refus }}
      </div>
      <div
        v-if="bsd.date_refus"
        class="text-caption"
      >
        Date de refus : {{ formatDateTime(bsd.date_refus) }}
      </div>
    </v-alert>

    <v-alert
      v-if="bsd.statut === 'CANCELED'"
      type="warning"
      variant="tonal"
      density="compact"
      class="mt-3"
    >
      <div class="font-weight-bold">
        BSD Annule
      </div>
      <div
        v-if="bsd.motif_annulation"
        class="text-body-2 mt-1"
      >
        Motif : {{ bsd.motif_annulation }}
      </div>
      <div
        v-if="bsd.date_annulation"
        class="text-caption"
      >
        Date d'annulation : {{ formatDateTime(bsd.date_annulation) }}
      </div>
    </v-alert>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  bsd: {
    type: Object,
    required: true,
  },
})

const STATUT_ORDER = [
  'DRAFT', 'SEALED', 'SIGNED_BY_PRODUCER', 'SENT', 'RECEIVED', 'PROCESSED',
]

const STATUT_LABELS = {
  DRAFT:              'Brouillon',
  SEALED:             'Scelle',
  SIGNED_BY_PRODUCER: 'Signe',
  SENT:               'Envoye',
  RECEIVED:           'Recu',
  PROCESSED:          'Traite',
  REFUSED:            'Refuse',
  CANCELED:           'Annule',
}

const STATUT_ICONS = {
  DRAFT:              'mdi-pencil',
  SEALED:             'mdi-lock',
  SIGNED_BY_PRODUCER: 'mdi-draw',
  SENT:               'mdi-truck',
  RECEIVED:           'mdi-package-down',
  PROCESSED:          'mdi-check-circle',
}

const STATUT_DATES = {
  DRAFT:              'created_at',
  SEALED:             null,
  SIGNED_BY_PRODUCER: 'date_signature_producteur',
  SENT:               'date_enlevement',
  RECEIVED:           'date_reception',
  PROCESSED:          'date_traitement',
}

const lifecycleSteps = computed(() => {
  const currentIndex = STATUT_ORDER.indexOf(props.bsd.statut)
  const isTerminal = ['REFUSED', 'CANCELED'].includes(props.bsd.statut)

  return STATUT_ORDER.map((statut, index) => {
    const dateField = STATUT_DATES[statut]
    const date = dateField ? props.bsd[dateField] : findDateFromHistory(statut)

    let completed = false
    let current = false

    if (isTerminal) {
      // For refused/canceled, mark steps up to the point they were at
      const lastGoodStatut = getLastGoodStatut()
      const lastGoodIndex = STATUT_ORDER.indexOf(lastGoodStatut)
      completed = index < lastGoodIndex
      current = index === lastGoodIndex
    } else {
      completed = index < currentIndex
      current = index === currentIndex
    }

    return {
      statut,
      label: STATUT_LABELS[statut],
      icon: STATUT_ICONS[statut],
      date,
      completed,
      current,
    }
  })
})

const sortedHistories = computed(() => {
  if (!props.bsd.statut_histories) return []
  return [...props.bsd.statut_histories].sort(
    (a, b) => new Date(a.date_changement) - new Date(b.date_changement)
  )
})

function getLastGoodStatut() {
  if (!props.bsd.statut_histories?.length) return 'DRAFT'
  const histories = [...props.bsd.statut_histories].sort(
    (a, b) => new Date(b.date_changement) - new Date(a.date_changement)
  )
  // Find the transition that led to REFUSED/CANCELED
  const terminal = histories.find(
    h => h.nouveau_statut === props.bsd.statut
  )
  return terminal?.ancien_statut || 'DRAFT'
}

function findDateFromHistory(statut) {
  if (!props.bsd.statut_histories) return null
  const entry = props.bsd.statut_histories.find(h => h.nouveau_statut === statut)
  return entry?.date_changement || null
}

function stepClass(step) {
  if (step.completed) return 'step-completed'
  if (step.current) return 'step-current'
  return 'step-pending'
}

function getHistoryDotColor(statut) {
  const colors = {
    DRAFT: 'grey', SEALED: 'blue', SIGNED_BY_PRODUCER: 'indigo',
    SENT: 'orange', RECEIVED: 'teal', PROCESSED: 'green',
    REFUSED: 'red', CANCELED: 'grey',
  }
  return colors[statut] || 'grey'
}

function statutLabel(statut) {
  return STATUT_LABELS[statut] || statut
}

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
  })
}

function formatDateTime(date) {
  if (!date) return ''
  return new Date(date).toLocaleString('fr-FR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}
</script>

<style scoped>
.step-dot {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  flex-shrink: 0;
  cursor: pointer;
  transition: all 0.2s ease;
}

.step-completed {
  background-color: rgb(var(--v-theme-success));
}

.step-current {
  background-color: rgb(var(--v-theme-primary));
  box-shadow: 0 0 0 4px rgba(var(--v-theme-primary), 0.2);
}

.step-pending {
  background-color: #e0e0e0;
  color: #9e9e9e;
}

.step-number {
  color: #757575;
  font-size: 11px;
}

.step-line {
  height: 3px;
  background-color: #e0e0e0;
  border-radius: 2px;
}

.line-completed {
  background-color: rgb(var(--v-theme-success));
}

.step-label {
  min-width: 0;
  font-size: 11px;
}

.timeline-steps {
  padding: 0 4px;
}

.timeline-labels {
  padding: 0 4px;
}
</style>
