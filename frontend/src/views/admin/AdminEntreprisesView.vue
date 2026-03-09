<template>
  <div>
    <div class="d-flex flex-wrap align-center mb-6">
      <h1 class="text-h4">
        Entreprises
      </h1>
      <v-spacer />
      <v-btn
        color="primary"
        prepend-icon="mdi-plus"
        @click="openCreateDialog"
      >
        Ajouter une entreprise
      </v-btn>
    </div>

    <v-card class="mb-4">
      <v-card-text>
        <v-row>
          <v-col
            cols="12"
            sm="4"
          >
            <v-select
              v-model="filterPlan"
              label="Filtrer par plan"
              :items="[{ title: 'Tous', value: '' }, { title: 'Gratuit', value: 'gratuit' }, { title: 'Standard', value: 'standard' }, { title: 'Premium', value: 'premium' }]"
              clearable
              density="compact"
              @update:model-value="loadEntreprises"
            />
          </v-col>
          <v-col
            cols="12"
            sm="4"
          >
            <v-select
              v-model="filterActif"
              label="Statut"
              :items="[{ title: 'Tous', value: '' }, { title: 'Actif', value: 'true' }, { title: 'Inactif', value: 'false' }]"
              clearable
              density="compact"
              @update:model-value="loadEntreprises"
            />
          </v-col>
          <v-col
            cols="12"
            sm="4"
          >
            <v-select
              v-model="filterArchived"
              label="Archivage"
              :items="[{ title: 'Actives (non archivees)', value: '' }, { title: 'Archivees', value: 'true' }, { title: 'Toutes', value: 'all' }]"
              density="compact"
              @update:model-value="loadEntreprises"
            />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <v-card>
      <div style="overflow-x: auto">
        <v-data-table
          :headers="headers"
          :items="adminStore.entreprises"
          :loading="adminStore.loading"
          density="comfortable"
          class="cursor-pointer"
          @click:row="(_, { item }) => $router.push(`/admin/entreprises/${item.id}`)"
        >
          <template #item.plan="{ item }">
            <v-chip
              size="small"
              :color="planColor(item.plan)"
            >
              {{ item.plan }}
            </v-chip>
          </template>
          <template #item.actif="{ item }">
            <v-icon :color="item.actif ? 'success' : 'error'">
              {{ item.actif ? 'mdi-check-circle' : 'mdi-close-circle' }}
            </v-icon>
          </template>
          <template #item.archived_at="{ item }">
            <v-chip
              v-if="item.archived_at"
              size="small"
              color="grey"
            >
              Archivee
            </v-chip>
          </template>
          <template #item.actions="{ item }">
            <v-btn
              size="small"
              variant="text"
              :color="item.actif ? 'error' : 'success'"
              @click.stop="toggleEntreprise(item.id)"
            >
              {{ item.actif ? 'Desactiver' : 'Activer' }}
            </v-btn>
            <v-btn
              size="small"
              variant="text"
              :color="item.archived_at ? 'info' : 'grey'"
              @click.stop="toggleArchiveEntreprise(item.id)"
            >
              {{ item.archived_at ? 'Desarchiver' : 'Archiver' }}
            </v-btn>
          </template>
        </v-data-table>
      </div>
    </v-card>

    <!-- DIALOG CREATION ENTREPRISE -->
    <v-dialog
      v-model="createDialog"
      max-width="600"
      :fullscreen="mobile"
    >
      <v-card>
        <v-card-title>Nouvelle entreprise</v-card-title>
        <v-card-text>
          <v-text-field
            v-model="createForm.raison_sociale"
            label="Raison sociale"
          />
          <v-text-field
            v-model="createForm.siret"
            label="SIRET"
          />
          <v-select
            v-model="createForm.forme_juridique"
            label="Forme juridique"
            :items="['SAS', 'SARL', 'SA', 'EURL', 'SCI', 'Auto-entrepreneur']"
            clearable
          />
          <v-text-field
            v-model="createForm.adresse"
            label="Adresse"
          />
          <v-row dense>
            <v-col
              cols="12"
              sm="4"
            >
              <v-text-field
                v-model="createForm.code_postal"
                label="Code postal"
              />
            </v-col>
            <v-col
              cols="12"
              sm="8"
            >
              <v-text-field
                v-model="createForm.ville"
                label="Ville"
              />
            </v-col>
          </v-row>
          <v-text-field
            v-model="createForm.telephone"
            label="Telephone"
          />
          <v-text-field
            v-model="createForm.email_contact"
            label="Email de contact"
            type="email"
          />
          <v-divider class="my-3" />
          <v-select
            v-model="createForm.plan"
            label="Plan"
            :items="['gratuit', 'standard', 'premium']"
          />
          <v-row dense>
            <v-col
              cols="12"
              sm="6"
            >
              <v-text-field
                v-model.number="createForm.max_garages"
                label="Max garages"
                type="number"
                min="1"
              />
            </v-col>
            <v-col
              cols="12"
              sm="6"
            >
              <v-text-field
                v-model.number="createForm.max_users"
                label="Max utilisateurs"
                type="number"
                min="1"
              />
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="text"
            @click="createDialog = false"
          >
            Annuler
          </v-btn>
          <v-btn
            color="primary"
            :loading="creating"
            @click="createEntreprise"
          >
            Creer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useDisplay } from 'vuetify'
import { useAdminStore } from '../../stores/admin'
import { useUiStore } from '../../stores/ui'

const { mobile } = useDisplay()
const router = useRouter()
const adminStore = useAdminStore()
const uiStore = useUiStore()

const filterPlan = ref('')
const filterActif = ref('')
const filterArchived = ref('')
const createDialog = ref(false)
const creating = ref(false)
const createForm = reactive({
  raison_sociale: '', siret: '', forme_juridique: '', adresse: '', code_postal: '', ville: '',
  telephone: '', email_contact: '', plan: 'gratuit', max_garages: 1, max_users: 5,
})

const headers = computed(() => {
  const base = [
    { title: 'Raison sociale', key: 'raison_sociale' },
    { title: 'SIRET', key: 'siret' },
    { title: 'Plan', key: 'plan' },
    { title: 'Garages', key: 'garages_count' },
    { title: 'Utilisateurs', key: 'users_count' },
    { title: 'Actif', key: 'actif' },
  ]
  if (filterArchived.value === 'true' || filterArchived.value === 'all') {
    base.push({ title: 'Archivee', key: 'archived_at', sortable: false })
  }
  base.push({ title: 'Actions', key: 'actions', sortable: false })
  return base
})

function planColor(plan) {
  return { gratuit: 'grey', standard: 'info', premium: 'success' }[plan] || 'grey'
}

function loadEntreprises() {
  const params = {}
  if (filterPlan.value) params.plan = filterPlan.value
  if (filterActif.value) params.actif = filterActif.value
  if (filterArchived.value) params.archived = filterArchived.value
  adminStore.fetchEntreprises(params)
}

async function toggleEntreprise(id) {
  try {
    const result = await adminStore.toggleActive(id)
    uiStore.showSuccess(result.message)
  } catch (e) {
    uiStore.showError(e.response?.data?.message || 'Erreur')
  }
}

async function toggleArchiveEntreprise(id) {
  try {
    const result = await adminStore.toggleArchive(id)
    uiStore.showSuccess(result.message)
  } catch (e) {
    uiStore.showError(e.response?.data?.message || 'Erreur')
  }
}

function openCreateDialog() {
  Object.assign(createForm, {
    raison_sociale: '', siret: '', forme_juridique: '', adresse: '', code_postal: '', ville: '',
    telephone: '', email_contact: '', plan: 'gratuit', max_garages: 1, max_users: 5,
  })
  createDialog.value = true
}

async function createEntreprise() {
  creating.value = true
  try {
    const entreprise = await adminStore.createEntreprise(createForm)
    createDialog.value = false
    uiStore.showSuccess('Entreprise creee')
    router.push(`/admin/entreprises/${entreprise.id}`)
  } catch (e) {
    uiStore.showError(e.response?.data?.message || 'Erreur')
  }
  creating.value = false
}

onMounted(loadEntreprises)
</script>

<style scoped>
.cursor-pointer :deep(tbody tr) {
  cursor: pointer;
}
</style>
