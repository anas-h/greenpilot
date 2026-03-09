<template>
  <div class="conformite-view">
    <v-container fluid>
      <!-- Header -->
      <v-row class="mb-4">
        <v-col
          cols="12"
          md="6"
        >
          <h1 class="text-h4 font-weight-bold">
            Conformite
          </h1>
          <p class="text-subtitle-1 text-medium-emphasis">
            Score de conformite reglementaire et gestion des FDS
          </p>
        </v-col>
        <v-col
          cols="12"
          md="6"
          class="d-flex flex-wrap justify-end align-center"
        >
          <v-btn
            variant="tonal"
            prepend-icon="mdi-refresh"
            :loading="loadingScore"
            @click="refreshScore"
          >
            Recalculer
          </v-btn>
        </v-col>
      </v-row>

      <!-- Score section -->
      <v-row class="mb-6">
        <v-col cols="12">
          <ScoreConformite
            :score="scoreData"
            :loading="loadingScore"
          />
        </v-col>
      </v-row>

      <!-- Tabs: FDS management -->
      <v-card>
        <v-tabs
          v-model="activeTab"
          color="primary"
        >
          <v-tab value="fds">
            <v-icon start>
              mdi-file-document-check
            </v-icon>
            Fiches de Donnees de Securite
          </v-tab>
          <v-tab value="details">
            <v-icon start>
              mdi-chart-bar
            </v-icon>
            Details du score
          </v-tab>
        </v-tabs>

        <v-tabs-window v-model="activeTab">
          <!-- FDS Tab -->
          <v-tabs-window-item value="fds">
            <v-card-text>
              <div class="d-flex justify-end mb-4">
                <v-btn
                  color="primary"
                  prepend-icon="mdi-upload"
                  @click="showUploadDialog = true"
                >
                  Ajouter une FDS
                </v-btn>
              </div>
              <FdsList
                ref="fdsListRef"
                @edit="onEditFds"
                @delete="onDeleteFds"
              />
            </v-card-text>
          </v-tabs-window-item>

          <!-- Score details tab -->
          <v-tabs-window-item value="details">
            <v-card-text>
              <v-row>
                <v-col
                  v-for="(detail, key) in scoreData?.details"
                  :key="key"
                  cols="12"
                  md="6"
                >
                  <v-card
                    variant="outlined"
                    class="mb-3"
                  >
                    <v-card-title class="text-subtitle-1 d-flex align-center">
                      <v-icon
                        start
                        :color="scoreColor(detail.score)"
                      >
                        {{ criteriaIcon(key) }}
                      </v-icon>
                      {{ criteriaLabel(key) }}
                      <v-spacer />
                      <v-chip
                        :color="scoreColor(detail.score)"
                        size="small"
                      >
                        {{ detail.score }}%
                      </v-chip>
                      <span class="text-caption text-medium-emphasis ml-2">
                        (poids: {{ detail.poids }}%)
                      </span>
                    </v-card-title>
                    <v-card-text>
                      <v-progress-linear
                        :model-value="detail.score"
                        :color="scoreColor(detail.score)"
                        height="8"
                        rounded
                        class="mb-3"
                      />
                      <div
                        v-if="detail.details"
                        class="text-caption"
                      >
                        <template v-if="key === 'conteneurs'">
                          <span v-if="detail.details.total">
                            {{ detail.details.conformes }}/{{ detail.details.total }} conteneurs conformes
                          </span>
                          <span v-else>Aucun conteneur actif</span>
                        </template>
                        <template v-else-if="key === 'bsd'">
                          {{ detail.details.anomalies }} anomalie(s) sur {{ detail.details.total_actifs }} BSD actifs
                        </template>
                        <template v-else-if="key === 'registre'">
                          {{ detail.details.dd_sans_bsd }} DD sans BSD sur {{ detail.details.total_dd }} productions DD
                        </template>
                        <template v-else-if="key === 'autorisations'">
                          {{ detail.details.expires }} expiree(s), {{ detail.details.bientot_expires }} bientot expiree(s) sur {{ detail.details.total_actifs }} collecteurs
                        </template>
                        <template v-else-if="key === 'fds'">
                          {{ detail.details.presentes }}/{{ detail.details.requis }} presentes
                          <span v-if="detail.details.expirees"> ({{ detail.details.expirees }} expiree(s))</span>
                          <span v-if="detail.details.manquantes > 0"> - {{ detail.details.manquantes }} manquante(s)</span>
                        </template>
                        <template v-else-if="key === 'trackdechets'">
                          <span v-if="detail.details.configure && detail.details.verifie">
                            Configure et verifie
                          </span>
                          <span v-else-if="detail.details.configure">
                            Configure mais non verifie
                          </span>
                          <span v-else>
                            Non configure
                          </span>
                          <span v-if="detail.details.derniere_synchro">
                            - Derniere synchro: {{ formatDate(detail.details.derniere_synchro) }}
                          </span>
                        </template>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-card-text>
          </v-tabs-window-item>
        </v-tabs-window>
      </v-card>

      <!-- Upload dialog -->
      <v-dialog
        v-model="showUploadDialog"
        max-width="700"
        :fullscreen="mobile"
        persistent
      >
        <FdsUpload
          :edit-fds="editingFds"
          @saved="onFdsSaved"
          @cancel="closeUploadDialog"
        />
      </v-dialog>
    </v-container>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useDisplay } from 'vuetify'
import api from '@/services/api'
import ScoreConformite from './components/ScoreConformite.vue'
import FdsList from './components/FdsList.vue'
import FdsUpload from './components/FdsUpload.vue'

const { mobile } = useDisplay()
const activeTab = ref('fds')
const scoreData = ref(null)
const loadingScore = ref(false)
const showUploadDialog = ref(false)
const editingFds = ref(null)
const fdsListRef = ref(null)

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

function criteriaLabel(key) {
  return CRITERIA_LABELS[key] || key
}

function criteriaIcon(key) {
  return CRITERIA_ICONS[key] || 'mdi-check'
}

function scoreColor(score) {
  if (score >= 80) return 'green'
  if (score >= 60) return 'orange'
  return 'red'
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR')
}

async function refreshScore() {
  loadingScore.value = true
  try {
    const response = await api.get('/conformite/score')
    scoreData.value = response.data.data
  } catch (err) {
    console.error('Failed to load conformity score:', err)
  } finally {
    loadingScore.value = false
  }
}

function onEditFds(fds) {
  editingFds.value = fds
  showUploadDialog.value = true
}

function onDeleteFds() {
  refreshScore()
}

function onFdsSaved() {
  showUploadDialog.value = false
  editingFds.value = null
  fdsListRef.value?.refresh()
  refreshScore()
}

function closeUploadDialog() {
  showUploadDialog.value = false
  editingFds.value = null
}

onMounted(() => {
  refreshScore()
})
</script>

<style scoped>
.conformite-view {
  min-height: 100%;
}
</style>
