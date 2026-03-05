<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center mb-1">
      <div>
        <h1 class="text-h5 font-weight-bold">
          Administration Plateforme
        </h1>
        <p class="text-body-2 text-medium-emphasis">
          Vue d'ensemble de l'activite
        </p>
      </div>
      <v-spacer />
      <v-btn
        variant="tonal"
        color="primary"
        to="/admin/entreprises"
        prepend-icon="mdi-domain"
        class="d-none d-sm-flex"
      >
        Gerer les entreprises
      </v-btn>
    </div>

    <!-- Loading skeleton -->
    <template v-if="!stats">
      <v-row class="mt-4">
        <v-col
          v-for="n in 4"
          :key="n"
          cols="12"
          sm="6"
          md="3"
        >
          <v-skeleton-loader type="card" />
        </v-col>
      </v-row>
      <v-row class="mt-2">
        <v-col
          cols="12"
          md="4"
        >
          <v-skeleton-loader type="card" />
        </v-col>
        <v-col
          cols="12"
          md="8"
        >
          <v-skeleton-loader type="table" />
        </v-col>
      </v-row>
    </template>

    <template v-else>
      <!-- KPI Cards -->
      <v-row class="mt-4">
        <v-col
          v-for="(kpi, i) in kpis"
          :key="kpi.label"
          cols="12"
          sm="6"
          md="3"
        >
          <v-card
            class="pa-4 admin-card clickable-card"
            hover
            :to="kpi.to"
            :style="{ animationDelay: `${i * 80}ms` }"
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
              <div class="flex-grow-1">
                <p
                  class="text-caption text-medium-emphasis text-uppercase font-weight-medium"
                  style="letter-spacing: 0.05em"
                >
                  {{ kpi.label }}
                </p>
                <p class="text-h5 font-weight-bold">
                  {{ kpi.value }}
                </p>
              </div>
              <v-icon
                size="18"
                color="grey-lighten-1"
              >
                mdi-chevron-right
              </v-icon>
            </div>
          </v-card>
        </v-col>
      </v-row>

      <!-- Abonnements row -->
      <v-row class="mt-2">
        <v-col
          v-for="(sub, i) in subscriptionStats"
          :key="sub.label"
          cols="12"
          sm="4"
        >
          <v-card
            class="admin-card clickable-card"
            hover
            :to="sub.to"
            :style="{ animationDelay: `${(i + 4) * 80}ms` }"
          >
            <v-card-text class="d-flex align-center pa-4">
              <v-avatar
                :color="sub.color"
                variant="tonal"
                size="44"
                rounded="lg"
                class="mr-4"
              >
                <v-icon size="22">
                  {{ sub.icon }}
                </v-icon>
              </v-avatar>
              <div class="flex-grow-1">
                <p class="text-caption text-medium-emphasis">
                  {{ sub.label }}
                </p>
                <p class="text-h6 font-weight-bold">
                  {{ sub.value }}
                </p>
              </div>
              <v-chip
                v-if="sub.percent !== null"
                :color="sub.color"
                variant="tonal"
                size="small"
              >
                {{ sub.percent }}%
              </v-chip>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Alertes conditionnelles -->
      <v-row
        v-if="stats.bordereaux_en_erreur > 0 || stats.entreprises_archivees > 0"
        class="mt-2"
      >
        <v-col
          v-if="stats.bordereaux_en_erreur > 0"
          cols="12"
          sm="6"
        >
          <v-alert
            type="error"
            variant="tonal"
            prominent
            class="admin-card"
            :style="{ animationDelay: '560ms' }"
          >
            <template #prepend>
              <v-avatar
                color="error"
                variant="tonal"
                size="44"
                rounded="lg"
              >
                <v-icon size="22">
                  mdi-file-alert
                </v-icon>
              </v-avatar>
            </template>
            <div class="d-flex align-center">
              <div>
                <div class="text-subtitle-2 font-weight-bold">
                  {{ stats.bordereaux_en_erreur }} BSD en erreur
                </div>
                <div class="text-caption">
                  Necessite une intervention
                </div>
              </div>
              <v-spacer />
              <v-btn
                variant="outlined"
                color="error"
                size="small"
                to="/admin/bordereaux"
              >
                Voir
              </v-btn>
            </div>
          </v-alert>
        </v-col>
        <v-col
          v-if="stats.entreprises_archivees > 0"
          cols="12"
          sm="6"
        >
          <v-alert
            type="warning"
            variant="tonal"
            prominent
            class="admin-card"
            :style="{ animationDelay: '640ms' }"
          >
            <template #prepend>
              <v-avatar
                color="warning"
                variant="tonal"
                size="44"
                rounded="lg"
              >
                <v-icon size="22">
                  mdi-archive
                </v-icon>
              </v-avatar>
            </template>
            <div>
              <div class="text-subtitle-2 font-weight-bold">
                {{ stats.entreprises_archivees }} entreprise{{ stats.entreprises_archivees > 1 ? 's' : '' }} archivee{{ stats.entreprises_archivees > 1 ? 's' : '' }}
              </div>
              <div class="text-caption">
                Compte{{ stats.entreprises_archivees > 1 ? 's' : '' }} desactive{{ stats.entreprises_archivees > 1 ? 's' : '' }}
              </div>
            </div>
          </v-alert>
        </v-col>
      </v-row>

      <!-- Repartition + Entreprises recentes -->
      <v-row class="mt-2">
        <!-- Repartition par plan -->
        <v-col
          cols="12"
          md="4"
        >
          <v-card
            class="admin-card"
            :style="{ animationDelay: '640ms' }"
          >
            <v-card-title class="d-flex align-center px-4 pt-4">
              <v-icon
                color="primary"
                size="small"
                class="mr-2"
              >
                mdi-chart-pie
              </v-icon>
              Repartition par plan
            </v-card-title>
            <v-card-text class="px-4 pb-4">
              <div
                v-for="(count, plan) in stats.par_plan"
                :key="plan"
                class="mb-4"
              >
                <div class="d-flex align-center justify-space-between mb-1">
                  <span class="text-body-2 font-weight-medium text-capitalize">{{ plan }}</span>
                  <span class="text-body-2 font-weight-bold">{{ count }}</span>
                </div>
                <v-progress-linear
                  :model-value="stats.total_entreprises ? (count / stats.total_entreprises) * 100 : 0"
                  :color="planColor(plan)"
                  height="8"
                  rounded
                />
              </div>

              <v-divider class="my-4" />

              <!-- Resume statut -->
              <div class="d-flex justify-space-around text-center">
                <div>
                  <div class="text-h6 font-weight-bold text-success">
                    {{ stats.entreprises_actives }}
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    Actives
                  </div>
                </div>
                <div>
                  <div class="text-h6 font-weight-bold text-grey">
                    {{ stats.entreprises_inactives }}
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    Inactives
                  </div>
                </div>
                <div>
                  <div class="text-h6 font-weight-bold text-warning">
                    {{ stats.entreprises_archivees }}
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    Archivees
                  </div>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Entreprises recentes -->
        <v-col
          cols="12"
          md="8"
        >
          <v-card
            class="admin-card"
            :style="{ animationDelay: '720ms' }"
          >
            <v-card-title class="d-flex align-center px-4 pt-4">
              <v-icon
                color="primary"
                size="small"
                class="mr-2"
              >
                mdi-domain
              </v-icon>
              <span>Entreprises recentes</span>
              <v-spacer />
              <v-btn
                variant="text"
                color="primary"
                size="small"
                to="/admin/entreprises"
                append-icon="mdi-chevron-right"
              >
                Tout voir
              </v-btn>
            </v-card-title>

            <v-table
              density="comfortable"
              hover
              class="mt-1"
            >
              <thead>
                <tr>
                  <th>Entreprise</th>
                  <th class="text-center">
                    Plan
                  </th>
                  <th class="text-center d-none d-sm-table-cell">
                    Garages
                  </th>
                  <th class="text-center d-none d-sm-table-cell">
                    Users
                  </th>
                  <th class="text-center">
                    Statut
                  </th>
                  <th class="text-right" />
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="item in stats.entreprises_recentes || []"
                  :key="item.id"
                >
                  <td>
                    <div class="d-flex align-center py-2">
                      <v-avatar
                        color="primary"
                        variant="tonal"
                        size="36"
                        rounded="lg"
                        class="mr-3"
                      >
                        <span class="text-caption font-weight-bold">{{ getInitials(item.raison_sociale) }}</span>
                      </v-avatar>
                      <div>
                        <div class="text-body-2 font-weight-medium">
                          {{ item.raison_sociale }}
                        </div>
                        <div class="text-caption text-medium-emphasis">
                          {{ item.ville }} — {{ formatDate(item.created_at) }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="text-center">
                    <v-chip
                      size="small"
                      :color="planColor(item.plan)"
                      variant="tonal"
                    >
                      {{ item.plan }}
                    </v-chip>
                  </td>
                  <td class="text-center d-none d-sm-table-cell">
                    <span class="font-weight-medium">{{ item.garages_count }}</span>
                  </td>
                  <td class="text-center d-none d-sm-table-cell">
                    <span class="font-weight-medium">{{ item.users_count }}</span>
                  </td>
                  <td class="text-center">
                    <v-chip
                      size="small"
                      :color="getStatusColor(item)"
                      variant="tonal"
                    >
                      {{ getStatusLabel(item) }}
                    </v-chip>
                  </td>
                  <td class="text-right">
                    <v-btn
                      icon="mdi-chevron-right"
                      variant="text"
                      size="small"
                      color="primary"
                      :to="`/admin/entreprises/${item.id}`"
                      title="Voir le detail"
                    />
                  </td>
                </tr>
              </tbody>
            </v-table>
          </v-card>
        </v-col>
      </v-row>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useAdminStore } from '../../stores/admin'
import { storeToRefs } from 'pinia'

const adminStore = useAdminStore()
const { stats } = storeToRefs(adminStore)

const kpis = computed(() => {
  if (!stats.value) return []
  return [
    { label: 'Entreprises', value: stats.value.total_entreprises, icon: 'mdi-domain', color: 'primary', to: '/admin/entreprises' },
    { label: 'Garages', value: stats.value.total_garages, icon: 'mdi-garage', color: 'success', to: '/admin/entreprises' },
    { label: 'Utilisateurs', value: stats.value.total_users, icon: 'mdi-account-group', color: 'info', to: '/admin/entreprises' },
    { label: 'Actives', value: stats.value.entreprises_actives, icon: 'mdi-check-circle', color: 'warning', to: '/admin/entreprises' },
  ]
})

const subscriptionStats = computed(() => {
  if (!stats.value) return []
  const total = stats.value.total_entreprises || 1
  return [
    { label: 'En essai', value: stats.value.en_trial || 0, icon: 'mdi-clock-outline', color: 'blue', percent: Math.round(((stats.value.en_trial || 0) / total) * 100), to: '/admin/entreprises' },
    { label: 'Abonnes', value: stats.value.abonnes || 0, icon: 'mdi-check-decagram', color: 'success', percent: Math.round(((stats.value.abonnes || 0) / total) * 100), to: '/admin/entreprises' },
    { label: 'Expires', value: stats.value.expires || 0, icon: 'mdi-alert-circle-outline', color: 'error', percent: Math.round(((stats.value.expires || 0) / total) * 100), to: '/admin/entreprises' },
  ]
})

function planColor(plan) {
  return { standard: 'info', premium: 'success', enterprise: 'warning' }[plan] || 'grey'
}

function getInitials(name) {
  if (!name) return '?'
  return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase()
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })
}

function getStatusColor(item) {
  if (item.archived_at) return 'grey'
  if (item.trial_ends_at && new Date(item.trial_ends_at) > new Date()) return 'blue'
  return 'success'
}

function getStatusLabel(item) {
  if (item.archived_at) return 'Archive'
  if (item.trial_ends_at && new Date(item.trial_ends_at) > new Date()) return 'Essai'
  return 'Actif'
}

onMounted(() => {
  adminStore.fetchStats()
})
</script>

<style scoped>
.admin-card {
  animation: fadeInUp 0.4s ease-out both;
}

.clickable-card {
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.clickable-card:hover {
  transform: translateY(-2px);
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
