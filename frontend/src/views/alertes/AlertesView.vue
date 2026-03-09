<template>
  <div class="alertes-view">
    <v-container fluid>
      <!-- Header -->
      <v-row class="mb-4">
        <v-col
          cols="12"
          md="6"
        >
          <h1 class="text-h5 text-sm-h4 font-weight-bold">
            Alertes
          </h1>
          <p class="text-subtitle-1 text-medium-emphasis">
            Suivi des alertes et notifications
          </p>
        </v-col>
        <v-col
          cols="12"
          md="6"
          class="d-flex justify-end align-center ga-2"
        >
          <v-btn
            variant="tonal"
            color="primary"
            prepend-icon="mdi-check-all"
            :disabled="alertesStore.counts.total === 0"
            @click="markAllRead"
          >
            Tout marquer comme lu
          </v-btn>
          <v-btn
            variant="tonal"
            prepend-icon="mdi-refresh"
            :loading="alertesStore.loading"
            @click="refresh"
          >
            Actualiser
          </v-btn>
        </v-col>
      </v-row>

      <!-- Summary cards -->
      <v-row class="mb-4">
        <v-col
          cols="6"
          sm="3"
        >
          <v-card
            :color="alertesStore.counts.critique > 0 ? 'red-lighten-5' : 'grey-lighten-4'"
            variant="flat"
          >
            <v-card-text class="text-center">
              <div
                class="text-h4 font-weight-bold"
                :class="alertesStore.counts.critique > 0 ? 'text-red' : ''"
              >
                {{ alertesStore.counts.critique }}
              </div>
              <div class="text-caption text-medium-emphasis">
                Critiques
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col
          cols="6"
          sm="3"
        >
          <v-card
            :color="alertesStore.counts.haute > 0 ? 'orange-lighten-5' : 'grey-lighten-4'"
            variant="flat"
          >
            <v-card-text class="text-center">
              <div
                class="text-h4 font-weight-bold"
                :class="alertesStore.counts.haute > 0 ? 'text-orange' : ''"
              >
                {{ alertesStore.counts.haute }}
              </div>
              <div class="text-caption text-medium-emphasis">
                Hautes
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col
          cols="6"
          sm="3"
        >
          <v-card
            color="grey-lighten-4"
            variant="flat"
          >
            <v-card-text class="text-center">
              <div class="text-h4 font-weight-bold text-blue">
                {{ alertesStore.counts.moyenne }}
              </div>
              <div class="text-caption text-medium-emphasis">
                Moyennes
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col
          cols="6"
          sm="3"
        >
          <v-card
            color="grey-lighten-4"
            variant="flat"
          >
            <v-card-text class="text-center">
              <div class="text-h4 font-weight-bold">
                {{ alertesStore.counts.basse }}
              </div>
              <div class="text-caption text-medium-emphasis">
                Basses
              </div>
            </v-card-text>
          </v-card>
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
              sm="6"
              md="3"
            >
              <v-select
                v-model="filters.priorite"
                :items="prioriteOptions"
                label="Priorite"
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
              sm="6"
              md="3"
            >
              <v-select
                v-model="filters.type"
                :items="typeOptions"
                label="Type"
                clearable
                multiple
                chips
                closable-chips
                density="compact"
                variant="outlined"
              />
            </v-col>
            <v-col
              cols="6"
              sm="6"
              md="2"
            >
              <v-select
                v-model="filters.lue"
                :items="[
                  { title: 'Non lues', value: 'false' },
                  { title: 'Lues', value: 'true' },
                ]"
                label="Lecture"
                clearable
                density="compact"
                variant="outlined"
              />
            </v-col>
            <v-col
              cols="6"
              sm="6"
              md="2"
            >
              <v-select
                v-model="filters.resolue"
                :items="[
                  { title: 'Non resolues', value: 'false' },
                  { title: 'Resolues', value: 'true' },
                ]"
                label="Resolution"
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

      <v-progress-linear
        v-if="alertesStore.loading"
        indeterminate
        color="primary"
        class="mb-2"
      />

      <!-- Alert list -->
      <v-card>
        <v-list
          v-if="alertesStore.alertes.length > 0"
          lines="three"
        >
          <template
            v-for="(alerte, index) in alertesStore.alertes"
            :key="alerte.id"
          >
            <v-list-item
              :class="{
                'bg-grey-lighten-5': alerte.lue,
                'font-weight-medium': !alerte.lue,
              }"
            >
              <template #prepend>
                <v-icon
                  :color="prioriteColor(alerte.priorite)"
                  :icon="prioriteIcon(alerte.priorite)"
                  size="large"
                />
              </template>

              <v-list-item-title class="d-flex flex-wrap align-center ga-2 mb-1">
                <span :class="{ 'font-weight-bold': !alerte.lue }">
                  {{ alerte.titre }}
                </span>
                <v-chip
                  :color="prioriteColor(alerte.priorite)"
                  size="x-small"
                  variant="tonal"
                >
                  {{ alerte.priorite }}
                </v-chip>
                <v-chip
                  size="x-small"
                  variant="outlined"
                >
                  {{ typeLabel(alerte.type) }}
                </v-chip>
                <v-chip
                  v-if="alerte.resolue"
                  color="green"
                  size="x-small"
                  variant="tonal"
                  prepend-icon="mdi-check"
                >
                  Resolue
                </v-chip>
              </v-list-item-title>

              <v-list-item-subtitle class="text-body-2 mt-1">
                {{ alerte.message }}
              </v-list-item-subtitle>

              <v-list-item-subtitle class="text-caption mt-2 text-medium-emphasis">
                {{ formatDateTime(alerte.created_at) }}
                <span v-if="alerte.resolue && alerte.date_resolution">
                  | Resolue le {{ formatDateTime(alerte.date_resolution) }}
                </span>
              </v-list-item-subtitle>

              <template #append>
                <div class="d-flex ga-1">
                  <v-btn
                    v-if="!alerte.lue"
                    icon="mdi-email-open"
                    size="small"
                    variant="text"
                    color="primary"
                    title="Marquer comme lue"
                    @click.stop="markRead(alerte.id)"
                  />
                  <v-btn
                    v-if="!alerte.resolue"
                    icon="mdi-check-circle"
                    size="small"
                    variant="text"
                    color="success"
                    title="Marquer comme resolue"
                    @click.stop="resolve(alerte.id)"
                  />
                </div>
              </template>
            </v-list-item>

            <v-divider v-if="index < alertesStore.alertes.length - 1" />
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
            Aucune alerte
          </p>
          <p class="text-body-2 text-medium-emphasis">
            Tout est conforme. Les alertes apparaitront ici si des anomalies sont detectees.
          </p>
        </v-card-text>

        <!-- Pagination -->
        <v-card-actions
          v-if="alertesStore.totalAlertes > 50"
          class="justify-center flex-column"
        >
          <div class="text-caption text-medium-emphasis mb-2">
            {{ (currentPage - 1) * 50 + 1 }} - {{ Math.min(currentPage * 50, alertesStore.totalAlertes) }} sur {{ alertesStore.totalAlertes }} resultats
          </div>
          <v-pagination
            v-model="currentPage"
            :length="Math.ceil(alertesStore.totalAlertes / 50)"
            :total-visible="3"
            @update:model-value="onPageChange"
          />
        </v-card-actions>
      </v-card>
    </v-container>
  </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted, onUnmounted } from 'vue'
import { useAlertesStore } from '@/stores/alertes'

const alertesStore = useAlertesStore()
const currentPage = ref(1)

const filters = reactive({
  priorite: [],
  type: [],
  lue: null,
  resolue: 'false', // Default: show non-resolved
  search: null,
})

const prioriteOptions = [
  { title: 'Critique', value: 'critique' },
  { title: 'Haute', value: 'haute' },
  { title: 'Moyenne', value: 'moyenne' },
  { title: 'Basse', value: 'basse' },
]

const typeOptions = [
  { title: 'Conteneur 80%', value: 'conteneur_80' },
  { title: 'Conteneur 95%', value: 'conteneur_95' },
  { title: 'Stockage 10 mois', value: 'stockage_10mois' },
  { title: 'Stockage 11 mois', value: 'stockage_11mois' },
  { title: 'Autorisation 30j', value: 'autorisation_30j' },
  { title: 'Autorisation expiree', value: 'autorisation_expiree' },
  { title: 'BSD envoye 15j', value: 'bsd_sent_15j' },
  { title: 'BSD refuse', value: 'bsd_refused' },
  { title: 'FDS manquante', value: 'fds_manquante' },
  { title: 'FDS expiree', value: 'fds_expiree' },
  { title: 'Conformite conteneurs', value: 'conformite_conteneurs' },
  { title: 'Trackdechets off', value: 'trackdechets_off' },
  { title: 'Production non validee', value: 'production_non_validee' },
  { title: 'Enlevement J-3', value: 'enlevement_j3' },
  { title: 'DD sans BSD', value: 'dd_sans_bsd' },
]

const TYPE_LABELS = {}
typeOptions.forEach(opt => { TYPE_LABELS[opt.value] = opt.title })

function prioriteColor(priorite) {
  const colors = {
    critique: 'red',
    haute: 'orange',
    moyenne: 'blue',
    basse: 'grey',
  }
  return colors[priorite] || 'grey'
}

function prioriteIcon(priorite) {
  const icons = {
    critique: 'mdi-alert-circle',
    haute: 'mdi-alert',
    moyenne: 'mdi-information',
    basse: 'mdi-information-outline',
  }
  return icons[priorite] || 'mdi-bell'
}

function typeLabel(type) {
  return TYPE_LABELS[type] || type
}

function formatDateTime(date) {
  if (!date) return ''
  return new Date(date).toLocaleString('fr-FR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

function buildParams() {
  const params = { page: currentPage.value }
  if (filters.priorite?.length) params.priorite = filters.priorite.join(',')
  if (filters.type?.length) params.type = filters.type.join(',')
  if (filters.lue !== null) params.lue = filters.lue
  if (filters.resolue !== null) params.resolue = filters.resolue
  if (filters.search) params.search = filters.search
  return params
}

async function refresh() {
  await alertesStore.fetchAlertes(buildParams())
  await alertesStore.fetchCounts()
}

async function markRead(id) {
  await alertesStore.markRead(id)
}

async function markAllRead() {
  await alertesStore.markAllRead()
  await refresh()
}

async function resolve(id) {
  await alertesStore.resolve(id)
  await alertesStore.fetchCounts()
}

function onPageChange(page) {
  currentPage.value = page
  alertesStore.fetchAlertes(buildParams())
}

// Debounced filter watching
let filterTimeout = null
watch(filters, () => {
  clearTimeout(filterTimeout)
  filterTimeout = setTimeout(() => {
    currentPage.value = 1
    alertesStore.fetchAlertes(buildParams())
  }, 300)
}, { deep: true })

onMounted(() => {
  refresh()
  alertesStore.startPolling()
})

onUnmounted(() => {
  alertesStore.stopPolling()
})
</script>

<style scoped>
.alertes-view {
  min-height: 100%;
}
</style>
