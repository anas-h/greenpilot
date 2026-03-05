<template>
  <v-card>
    <v-card-text>
      <v-row align="center">
        <!-- Circular gauge -->
        <v-col
          cols="12"
          md="4"
          class="d-flex justify-center"
        >
          <div class="score-gauge-container">
            <v-progress-circular
              v-if="!loading && score"
              :model-value="score.score_global"
              :size="200"
              :width="16"
              :color="globalScoreColor"
              class="score-gauge"
            >
              <div class="text-center">
                <div
                  class="text-h3 font-weight-bold"
                  :class="'text-' + globalScoreColor"
                >
                  {{ score.score_global }}
                </div>
                <div class="text-caption text-medium-emphasis">
                  /100
                </div>
                <div
                  class="text-body-2 mt-1"
                  :class="'text-' + globalScoreColor"
                >
                  {{ globalScoreLabel }}
                </div>
              </div>
            </v-progress-circular>

            <v-progress-circular
              v-else
              indeterminate
              :size="200"
              :width="16"
              color="grey-lighten-2"
            />
          </div>
        </v-col>

        <!-- Breakdown bars -->
        <v-col
          cols="12"
          md="8"
        >
          <div class="text-h6 font-weight-bold mb-4">
            Repartition du score
          </div>

          <template v-if="score?.details">
            <div
              v-for="(detail, key) in score.details"
              :key="key"
              class="mb-4"
            >
              <div class="d-flex justify-space-between align-center mb-1">
                <div class="d-flex align-center">
                  <v-icon
                    size="small"
                    :color="criteriaColor(detail.score)"
                    class="mr-2"
                  >
                    {{ criteriaIcon(key) }}
                  </v-icon>
                  <span class="text-body-2 font-weight-medium">
                    {{ criteriaLabel(key) }}
                  </span>
                </div>
                <div class="d-flex align-center ga-2">
                  <span
                    class="text-body-2 font-weight-bold"
                    :class="'text-' + criteriaColor(detail.score)"
                  >
                    {{ detail.score }}%
                  </span>
                  <span class="text-caption text-medium-emphasis">
                    ({{ detail.poids }}%)
                  </span>
                  <span
                    class="text-caption font-weight-medium"
                    :class="'text-' + criteriaColor(detail.score)"
                  >
                    {{ contributionPoints(detail) }} pts
                  </span>
                </div>
              </div>
              <v-progress-linear
                :model-value="detail.score"
                :color="criteriaColor(detail.score)"
                :height="10"
                rounded
                bg-color="grey-lighten-3"
              />
            </div>
          </template>

          <template v-else>
            <div
              v-for="i in 6"
              :key="i"
              class="mb-4"
            >
              <v-skeleton-loader
                type="text"
                class="mb-1"
              />
              <v-skeleton-loader
                type="text"
                width="100%"
                height="10"
              />
            </div>
          </template>

          <!-- Legend -->
          <v-divider class="my-4" />
          <div class="d-flex ga-4 text-caption">
            <div class="d-flex align-center ga-1">
              <v-icon
                size="x-small"
                color="green"
              >
                mdi-circle
              </v-icon>
              Conforme (80-100)
            </div>
            <div class="d-flex align-center ga-1">
              <v-icon
                size="x-small"
                color="orange"
              >
                mdi-circle
              </v-icon>
              Attention (60-79)
            </div>
            <div class="d-flex align-center ga-1">
              <v-icon
                size="x-small"
                color="red"
              >
                mdi-circle
              </v-icon>
              Non conforme (0-59)
            </div>
          </div>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  score: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const CRITERIA_LABELS = {
  conteneurs: 'Conteneurs conformes',
  bsd: 'BSD a jour',
  registre: 'Registre complet',
  autorisations: 'Autorisations valides',
  fds: 'FDS completes',
  trackdechets: 'Trackdechets actif',
}

const CRITERIA_ICONS = {
  conteneurs: 'mdi-package-variant',
  bsd: 'mdi-file-document-check',
  registre: 'mdi-book-open-page-variant',
  autorisations: 'mdi-certificate',
  fds: 'mdi-shield-check',
  trackdechets: 'mdi-cloud-sync',
}

const globalScoreColor = computed(() => {
  if (!props.score) return 'grey'
  return scoreColor(props.score.score_global)
})

const globalScoreLabel = computed(() => {
  if (!props.score) return ''
  const s = props.score.score_global
  if (s >= 90) return 'Excellent'
  if (s >= 80) return 'Conforme'
  if (s >= 60) return 'A ameliorer'
  if (s >= 40) return 'Insuffisant'
  return 'Critique'
})

function scoreColor(value) {
  if (value >= 80) return 'green'
  if (value >= 60) return 'orange'
  return 'red'
}

function criteriaColor(score) {
  return scoreColor(score)
}

function criteriaLabel(key) {
  return CRITERIA_LABELS[key] || key
}

function criteriaIcon(key) {
  return CRITERIA_ICONS[key] || 'mdi-check'
}

function contributionPoints(detail) {
  return ((detail.poids / 100) * detail.score).toFixed(1)
}
</script>

<style scoped>
.score-gauge-container {
  padding: 16px;
}

.score-gauge {
  transition: all 0.5s ease;
}
</style>
