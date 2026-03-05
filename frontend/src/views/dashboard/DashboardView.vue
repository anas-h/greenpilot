<template>
  <div>
    <!-- Header -->
    <h1 class="text-h5 font-weight-bold mb-1">
      Tableau de bord
    </h1>
    <p class="text-body-2 text-medium-emphasis mb-6">
      Vue d'ensemble de votre activite
    </p>

    <!-- KPI Cards -->
    <v-row>
      <v-col
        v-for="(kpi, i) in kpis"
        :key="kpi.title"
        cols="12"
        sm="6"
        md="3"
      >
        <v-card
          class="pa-4 dashboard-card"
          hover
          :style="{ animationDelay: `${i * 100}ms` }"
        >
          <div class="d-flex align-center">
            <v-avatar
              :color="kpi.color"
              variant="tonal"
              size="52"
              rounded="lg"
              class="mr-4"
            >
              <v-icon size="28">
                {{ kpi.icon }}
              </v-icon>
            </v-avatar>
            <div>
              <p
                class="text-caption text-medium-emphasis text-uppercase font-weight-medium"
                style="letter-spacing: 0.05em"
              >
                {{ kpi.title }}
              </p>
              <p class="text-h5 font-weight-bold">
                {{ kpi.displayValue }}
              </p>
            </div>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Alertes + Score conformite -->
    <v-row class="mt-4">
      <!-- Alertes recentes -->
      <v-col
        cols="12"
        md="8"
      >
        <v-card
          class="dashboard-card"
          style="animation-delay: 400ms"
        >
          <v-card-title class="d-flex align-center ga-2 px-4 pt-4">
            <v-icon
              color="warning"
              class="mr-1"
            >
              mdi-bell-alert
            </v-icon>
            <span>Alertes recentes</span>
            <v-chip
              v-if="alertes.length"
              color="warning"
              size="x-small"
              variant="tonal"
            >
              {{ alertes.length }}
            </v-chip>
            <v-spacer />
            <v-btn
              variant="text"
              color="primary"
              size="small"
              to="/alertes"
              append-icon="mdi-chevron-right"
            >
              Voir toutes les alertes
            </v-btn>
          </v-card-title>

          <!-- Skeleton -->
          <template v-if="loading">
            <v-skeleton-loader
              v-for="n in 3"
              :key="n"
              type="list-item-two-line"
              class="mx-2"
            />
          </template>

          <!-- Content -->
          <v-list
            v-else-if="alertes.length"
            lines="two"
            class="pa-2"
          >
            <template
              v-for="(a, index) in alertes"
              :key="a.id"
            >
              <v-list-item class="rounded-lg">
                <template #prepend>
                  <v-avatar
                    :color="getPrioriteColor(a.priorite)"
                    variant="tonal"
                    size="40"
                    rounded="lg"
                  >
                    <v-icon size="20">
                      {{ getPrioriteIcon(a.priorite) }}
                    </v-icon>
                  </v-avatar>
                </template>

                <v-list-item-title class="d-flex align-center ga-2 mb-1">
                  <span class="font-weight-medium text-body-2">{{ a.titre || a.message }}</span>
                  <v-chip
                    :color="getPrioriteColor(a.priorite)"
                    size="x-small"
                    variant="tonal"
                  >
                    {{ a.priorite }}
                  </v-chip>
                </v-list-item-title>
                <v-list-item-subtitle
                  v-if="a.titre"
                  class="text-caption"
                >
                  {{ a.message }}
                </v-list-item-subtitle>

                <template #append>
                  <span class="text-caption text-medium-emphasis">{{ formatRelativeTime(a.created_at) }}</span>
                </template>
              </v-list-item>
              <v-divider
                v-if="index < alertes.length - 1"
                inset
                class="my-1"
              />
            </template>
          </v-list>

          <!-- Empty state -->
          <v-card-text
            v-else
            class="text-center py-12"
          >
            <v-icon
              size="64"
              color="grey-lighten-1"
              class="mb-4"
            >
              mdi-bell-check-outline
            </v-icon>
            <p class="text-h6 text-medium-emphasis">
              Aucune alerte active
            </p>
            <p class="text-body-2 text-medium-emphasis">
              Tout est conforme
            </p>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Score conformite -->
      <v-col
        cols="12"
        md="4"
      >
        <v-card
          class="dashboard-card"
          style="animation-delay: 500ms"
        >
          <v-card-title class="d-flex align-center ga-2 px-4 pt-4">
            <v-icon
              color="success"
              class="mr-1"
            >
              mdi-shield-check
            </v-icon>
            <span>Score conformite</span>
          </v-card-title>

          <v-card-text class="text-center pb-2">
            <!-- Skeleton -->
            <div
              v-if="loading"
              class="d-flex justify-center py-4"
            >
              <v-skeleton-loader
                type="avatar"
                style="width: 160px; height: 160px"
              />
            </div>

            <!-- Doughnut chart -->
            <div
              v-else
              class="donut-container"
            >
              <Doughnut
                :data="doughnutData"
                :options="doughnutOptions"
              />
              <div class="donut-center-text">
                <div
                  class="text-h4 font-weight-bold"
                  :class="`text-${scoreColor}`"
                >
                  {{ scoreConformite }}
                </div>
                <div class="text-caption text-medium-emphasis">
                  /100
                </div>
                <div
                  class="text-caption font-weight-medium"
                  :class="`text-${scoreColor}`"
                >
                  {{ scoreLabel }}
                </div>
              </div>
            </div>
          </v-card-text>

          <v-card-actions class="px-4 pb-4">
            <v-btn
              variant="tonal"
              color="primary"
              block
              to="/conformite"
              prepend-icon="mdi-chevron-right"
            >
              Details conformite
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>

    <!-- Conteneurs + Enlevements -->
    <v-row class="mt-4">
      <!-- Conteneurs les plus remplis -->
      <v-col
        cols="12"
        md="6"
      >
        <v-card
          class="dashboard-card"
          style="animation-delay: 600ms"
        >
          <v-card-title class="d-flex align-center ga-2 px-4 pt-4">
            <v-icon
              color="primary"
              class="mr-1"
            >
              mdi-package-variant
            </v-icon>
            <span>Conteneurs les plus remplis</span>
            <v-chip
              color="primary"
              size="x-small"
              variant="tonal"
            >
              Top 5
            </v-chip>
            <v-spacer />
            <v-btn
              variant="text"
              color="primary"
              size="small"
              to="/conteneurs"
              append-icon="mdi-chevron-right"
            >
              Voir tous les conteneurs
            </v-btn>
          </v-card-title>

          <v-card-text>
            <!-- Skeleton -->
            <template v-if="loading">
              <div
                v-for="n in 3"
                :key="n"
                class="mb-4"
              >
                <v-skeleton-loader
                  type="text"
                  class="mb-2"
                />
                <v-skeleton-loader
                  type="heading"
                  style="height: 12px"
                />
              </div>
            </template>

            <!-- Content -->
            <template v-else-if="conteneurs.length">
              <div
                v-for="c in conteneurs"
                :key="c.id"
                class="mb-4"
              >
                <div class="d-flex align-center justify-space-between mb-1">
                  <span class="text-body-2 font-weight-medium">{{ c.nom }}</span>
                  <div class="d-flex align-center ga-1">
                    <v-icon
                      v-if="c.pourcentage >= 95"
                      color="error"
                      size="16"
                    >
                      mdi-alert
                    </v-icon>
                    <span
                      class="text-body-2 font-weight-bold"
                      :class="conteneurTextColor(c.pourcentage)"
                    >{{ c.pourcentage }}%</span>
                  </div>
                </div>
                <v-progress-linear
                  :model-value="c.pourcentage"
                  :color="conteneurColor(c.pourcentage)"
                  bg-color="grey-lighten-3"
                  height="12"
                  rounded
                />
              </div>
            </template>

            <!-- Empty state -->
            <div
              v-else
              class="text-center py-8"
            >
              <v-icon
                size="64"
                color="grey-lighten-1"
                class="mb-4"
              >
                mdi-package-variant-closed
              </v-icon>
              <p class="text-h6 text-medium-emphasis">
                Aucun conteneur actif
              </p>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Prochains enlevements -->
      <v-col
        cols="12"
        md="6"
      >
        <v-card
          class="dashboard-card"
          style="animation-delay: 700ms"
        >
          <v-card-title class="d-flex align-center ga-2 px-4 pt-4">
            <v-icon
              color="info"
              class="mr-1"
            >
              mdi-truck-delivery
            </v-icon>
            <span>Prochains enlevements</span>
            <v-spacer />
            <v-btn
              variant="text"
              color="primary"
              size="small"
              to="/enlevements"
              append-icon="mdi-chevron-right"
            >
              Voir tous les enlevements
            </v-btn>
          </v-card-title>

          <!-- Skeleton -->
          <template v-if="loading">
            <v-skeleton-loader
              v-for="n in 3"
              :key="n"
              type="list-item-two-line"
              class="mx-2"
            />
          </template>

          <!-- Content -->
          <v-list
            v-else-if="enlevements.length"
            lines="two"
            class="pa-2"
          >
            <template
              v-for="(e, index) in enlevements"
              :key="e.id"
            >
              <v-list-item class="rounded-lg">
                <template #prepend>
                  <v-avatar
                    :color="getStatutColor(e.statut)"
                    variant="tonal"
                    size="40"
                    rounded="lg"
                  >
                    <v-icon size="20">
                      mdi-truck-delivery
                    </v-icon>
                  </v-avatar>
                </template>

                <v-list-item-title class="d-flex align-center ga-2 mb-1">
                  <span class="font-weight-medium text-body-2">{{ e.collecteur?.raison_sociale || 'Collecteur' }}</span>
                  <v-chip
                    :color="getStatutColor(e.statut)"
                    size="x-small"
                    variant="tonal"
                  >
                    {{ getStatutLabel(e.statut) }}
                  </v-chip>
                </v-list-item-title>
                <v-list-item-subtitle class="d-flex align-center ga-1 text-caption">
                  <v-icon size="14">
                    mdi-calendar
                  </v-icon>
                  {{ formatDate(e.date_prevue) }}
                  <span v-if="e.numero">· {{ e.numero }}</span>
                </v-list-item-subtitle>

                <template #append>
                  <span
                    class="text-caption font-weight-medium"
                    :class="daysUntilColor(e.date_prevue)"
                  >
                    {{ daysUntil(e.date_prevue) }}
                  </span>
                </template>
              </v-list-item>
              <v-divider
                v-if="index < enlevements.length - 1"
                inset
                class="my-1"
              />
            </template>
          </v-list>

          <!-- Empty state -->
          <v-card-text
            v-else
            class="text-center py-8"
          >
            <v-icon
              size="64"
              color="grey-lighten-1"
              class="mb-4"
            >
              mdi-truck-delivery-outline
            </v-icon>
            <p class="text-h6 text-medium-emphasis">
              Aucun enlevement prevu
            </p>
            <v-btn
              variant="tonal"
              color="primary"
              class="mt-4"
              to="/enlevements"
              prepend-icon="mdi-plus"
            >
              Planifier
            </v-btn>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'
import api from '../../services/api'
import { useUiStore } from '../../stores/ui'

ChartJS.register(ArcElement, Tooltip, Legend)

const uiStore = useUiStore()

const loading = ref(true)
const alertes = ref([])
const conteneurs = ref([])
const enlevements = ref([])
const scoreConformite = ref(0)

const kpis = ref([
  { title: 'Productions ce mois', value: 0, displayValue: '0', icon: 'mdi-plus-circle', color: 'primary' },
  { title: 'Enlevements planifies', value: 0, displayValue: '0', icon: 'mdi-truck', color: 'info' },
  { title: 'Alertes actives', value: 0, displayValue: '0', icon: 'mdi-bell-alert', color: 'warning' },
  { title: 'Score conformite', value: 0, displayValue: '0%', icon: 'mdi-shield-check', color: 'success' },
])

// --- Conformity score ---
const scoreColor = computed(() => {
  if (scoreConformite.value >= 90) return 'success'
  if (scoreConformite.value >= 70) return 'warning'
  return 'error'
})

const scoreLabel = computed(() => {
  const s = scoreConformite.value
  if (s >= 90) return 'Excellent'
  if (s >= 75) return 'Conforme'
  if (s >= 50) return 'A ameliorer'
  if (s >= 25) return 'Insuffisant'
  return 'Critique'
})

const scoreColorHex = computed(() => {
  if (scoreConformite.value >= 90) return '#388E3C'
  if (scoreConformite.value >= 70) return '#F57C00'
  return '#D32F2F'
})

const doughnutData = computed(() => ({
  datasets: [{
    data: [scoreConformite.value, 100 - scoreConformite.value],
    backgroundColor: [scoreColorHex.value, '#E8F5E9'],
    borderWidth: 0,
    borderRadius: 6,
  }],
}))

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: true,
  cutout: '75%',
  plugins: {
    legend: { display: false },
    tooltip: { enabled: false },
  },
  animation: {
    duration: 1200,
    easing: 'easeOutQuart',
  },
}

// --- Helpers ---
function getPrioriteIcon(p) {
  return { critique: 'mdi-alert-circle', haute: 'mdi-alert', moyenne: 'mdi-information', basse: 'mdi-information-outline' }[p] || 'mdi-information'
}

function getPrioriteColor(p) {
  return { critique: 'error', haute: 'warning', moyenne: 'info', basse: 'grey' }[p] || 'grey'
}

function conteneurColor(pct) {
  if (pct >= 95) return 'error'
  if (pct >= 80) return 'warning'
  return 'success'
}

function conteneurTextColor(pct) {
  if (pct >= 95) return 'text-error'
  if (pct >= 80) return 'text-warning'
  return 'text-success'
}

function getStatutColor(statut) {
  return { brouillon: 'grey', planifie: 'info', complete: 'success', annule: 'error' }[statut] || 'grey'
}

function getStatutLabel(statut) {
  return { brouillon: 'Brouillon', planifie: 'Planifie', complete: 'Complete', annule: 'Annule' }[statut] || statut
}

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}

function daysUntil(date) {
  if (!date) return ''
  const now = new Date()
  now.setHours(0, 0, 0, 0)
  const target = new Date(date)
  target.setHours(0, 0, 0, 0)
  const diff = Math.round((target - now) / (1000 * 60 * 60 * 24))
  if (diff === 0) return "Aujourd'hui"
  if (diff === 1) return 'Demain'
  if (diff > 1) return `Dans ${diff}j`
  if (diff === -1) return 'Hier'
  return `Il y a ${Math.abs(diff)}j`
}

function daysUntilColor(date) {
  if (!date) return ''
  const now = new Date()
  now.setHours(0, 0, 0, 0)
  const target = new Date(date)
  target.setHours(0, 0, 0, 0)
  const diff = Math.round((target - now) / (1000 * 60 * 60 * 24))
  if (diff <= 1) return 'text-error'
  if (diff <= 3) return 'text-warning'
  return 'text-medium-emphasis'
}

function formatRelativeTime(date) {
  if (!date) return ''
  const now = new Date()
  const d = new Date(date)
  const diffMs = now - d
  const diffMin = Math.floor(diffMs / 60000)
  if (diffMin < 1) return "A l'instant"
  if (diffMin < 60) return `Il y a ${diffMin}min`
  const diffH = Math.floor(diffMin / 60)
  if (diffH < 24) return `Il y a ${diffH}h`
  const diffD = Math.floor(diffH / 24)
  return `Il y a ${diffD}j`
}

// --- Animated counter ---
function easeOutQuart(t) {
  return 1 - Math.pow(1 - t, 4)
}

function animateValue(kpi, target, suffix = '') {
  const duration = 800
  const start = performance.now()

  function tick(now) {
    const elapsed = now - start
    const progress = Math.min(elapsed / duration, 1)
    const current = Math.round(easeOutQuart(progress) * target)
    kpi.displayValue = current + suffix
    if (progress < 1) {
      requestAnimationFrame(tick)
    }
  }

  requestAnimationFrame(tick)
}

// --- Data fetch ---
onMounted(async () => {
  try {
    const { data } = await api.get('/dashboard')

    if (data.kpis) {
      const targets = [
        { idx: 0, val: data.kpis.productions_mois || 0, suffix: '' },
        { idx: 1, val: data.kpis.enlevements_planifies || 0, suffix: '' },
        { idx: 2, val: data.kpis.alertes_actives || 0, suffix: '' },
        { idx: 3, val: data.kpis.score_conformite || 0, suffix: '%' },
      ]
      targets.forEach(({ idx, val, suffix }) => {
        kpis.value[idx].value = val
        animateValue(kpis.value[idx], val, suffix)
      })
      scoreConformite.value = data.kpis.score_conformite || 0
    }

    alertes.value = data.alertes || []
    conteneurs.value = data.conteneurs || []
    enlevements.value = data.enlevements || []
  } catch (e) {
    console.error('Dashboard error:', e.response?.status, e.response?.data || e.message)
    uiStore.showError(e.response?.data?.message || 'Erreur lors du chargement du tableau de bord')
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.dashboard-card {
  animation: cardEntrance 0.5s ease both;
}

@keyframes cardEntrance {
  from {
    opacity: 0;
    transform: translateY(16px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.donut-container {
  position: relative;
  max-width: 200px;
  margin: 0 auto;
}

.donut-center-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
}
</style>
