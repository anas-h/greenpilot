<template>
  <div v-if="entreprise">
    <div class="d-flex flex-wrap align-center mb-6">
      <v-btn
        icon
        variant="text"
        class="mr-2"
        @click="$router.push('/admin/entreprises')"
      >
        <v-icon>mdi-arrow-left</v-icon>
      </v-btn>
      <h1 class="text-h4">
        {{ entreprise.raison_sociale }}
      </h1>
      <v-spacer />
      <v-chip
        :color="entreprise.actif ? 'success' : 'error'"
        class="ml-2"
      >
        {{ entreprise.actif ? 'Active' : 'Inactive' }}
      </v-chip>
      <v-chip
        v-if="entreprise.archived_at"
        color="grey"
        class="ml-2"
      >
        Archivee
      </v-chip>
    </div>

    <v-alert
      v-if="entreprise.archived_at"
      type="warning"
      variant="tonal"
      class="mb-4"
    >
      Cette entreprise est archivee. Elle n'apparait pas dans la liste par defaut.
    </v-alert>

    <v-row>
      <v-col
        cols="12"
        md="6"
      >
        <v-card>
          <v-card-title>Informations entreprise</v-card-title>
          <v-card-text>
            <v-text-field
              v-model="editForm.raison_sociale"
              label="Raison sociale"
            />
            <v-text-field
              v-model="editForm.siret"
              label="SIRET"
            />
            <v-select
              v-model="editForm.forme_juridique"
              label="Forme juridique"
              :items="['SAS', 'SARL', 'SA', 'EURL', 'SCI', 'Auto-entrepreneur']"
              clearable
            />
            <v-text-field
              v-model="editForm.adresse"
              label="Adresse"
            />
            <v-row dense>
              <v-col
                cols="12"
                sm="4"
              >
                <v-text-field
                  v-model="editForm.code_postal"
                  label="Code postal"
                />
              </v-col>
              <v-col
                cols="12"
                sm="8"
              >
                <v-text-field
                  v-model="editForm.ville"
                  label="Ville"
                />
              </v-col>
            </v-row>
            <v-text-field
              v-model="editForm.telephone"
              label="Telephone"
            />
            <v-text-field
              v-model="editForm.email_contact"
              label="Email de contact"
              type="email"
            />
          </v-card-text>
        </v-card>
      </v-col>

      <v-col
        cols="12"
        md="6"
      >
        <v-card>
          <v-card-title>Configuration du plan</v-card-title>
          <v-card-text>
            <v-select
              v-model="editForm.plan"
              label="Plan"
              :items="['standard', 'premium']"
            />
            <v-text-field
              v-model.number="editForm.max_garages"
              label="Max garages"
              type="number"
              min="1"
            />
            <v-text-field
              v-model.number="editForm.max_users"
              label="Max utilisateurs"
              type="number"
              min="1"
            />
          </v-card-text>
        </v-card>

        <!-- Subscription status -->
        <v-card
          v-if="subscriptionInfo"
          class="mt-4"
        >
          <v-card-title>Statut abonnement</v-card-title>
          <v-card-text>
            <v-chip
              :color="subscriptionInfo.subscribed ? 'success' : subscriptionInfo.on_trial ? 'info' : 'error'"
              class="mb-3"
            >
              {{ subscriptionInfo.subscribed ? 'Abonne' : subscriptionInfo.on_trial ? 'En essai' : 'Expire' }}
            </v-chip>
            <div
              v-if="subscriptionInfo.on_trial"
              class="text-body-2 mb-2"
            >
              Jours restants : <strong>{{ subscriptionInfo.trial_days_remaining }}</strong>
            </div>
            <div
              v-if="subscriptionInfo.read_only"
              class="text-body-2 text-error"
            >
              Compte en mode lecture seule
            </div>
            <div
              v-if="entreprise.stripe_id"
              class="mt-3"
            >
              <v-btn
                size="small"
                variant="outlined"
                color="primary"
                :href="`https://dashboard.stripe.com/customers/${entreprise.stripe_id}`"
                target="_blank"
              >
                <v-icon
                  start
                  size="small"
                >
                  mdi-open-in-new
                </v-icon>
                Voir sur Stripe
              </v-btn>
            </div>
          </v-card-text>
        </v-card>

        <v-card class="mt-4">
          <v-card-actions>
            <v-btn
              color="primary"
              :loading="saving"
              prepend-icon="mdi-content-save"
              @click="saveEntreprise"
            >
              Enregistrer les modifications
            </v-btn>
            <v-spacer />
            <v-btn
              :color="entreprise.actif ? 'error' : 'success'"
              variant="outlined"
              @click="toggleActive"
            >
              {{ entreprise.actif ? 'Desactiver' : 'Activer' }}
            </v-btn>
            <v-btn
              :color="entreprise.archived_at ? 'info' : 'grey'"
              variant="outlined"
              class="ml-2"
              @click="toggleArchive"
            >
              {{ entreprise.archived_at ? 'Desarchiver' : 'Archiver' }}
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>

    <!-- GARAGES -->
    <v-row class="mt-4">
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex flex-wrap align-center">
            Garages ({{ entreprise.garages?.length || 0 }})
            <v-spacer />
            <v-btn
              color="primary"
              size="small"
              prepend-icon="mdi-plus"
              @click="openGarageDialog"
            >
              Ajouter un garage
            </v-btn>
          </v-card-title>
          <div style="overflow-x: auto">
            <v-data-table
              :headers="garageHeaders"
              :items="entreprise.garages || []"
              density="comfortable"
              :items-per-page="10"
            >
              <template #item.actif="{ item }">
                <v-icon
                  :color="item.actif ? 'success' : 'grey'"
                  size="small"
                >
                  {{ item.actif ? 'mdi-check-circle' : 'mdi-close-circle' }}
                </v-icon>
              </template>
              <template #item.actions="{ item }">
                <v-btn
                  size="small"
                  variant="text"
                  color="info"
                  title="Modifier"
                  @click="openEditGarageDialog(item)"
                >
                  <v-icon size="small">
                    mdi-pencil
                  </v-icon>
                </v-btn>
                <v-btn
                  size="small"
                  variant="text"
                  color="error"
                  title="Supprimer"
                  @click="confirmDeleteGarage(item)"
                >
                  <v-icon size="small">
                    mdi-delete
                  </v-icon>
                </v-btn>
              </template>
            </v-data-table>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- TRACKDECHETS CONFIG -->
    <v-row class="mt-4">
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex align-center">
            <v-icon
              color="green"
              class="mr-2"
            >
              mdi-file-document-check
            </v-icon>
            Configuration Trackdechets
          </v-card-title>
          <v-card-text>
            <v-row dense>
              <v-col
                cols="12"
                md="6"
              >
                <v-text-field
                  v-model="tdForm.api_token"
                  label="Token API"
                  type="password"
                  hint="Mon compte > Integration API sur Trackdechets"
                  persistent-hint
                />
              </v-col>
              <v-col
                cols="12"
                md="6"
              >
                <v-select
                  v-model="tdForm.environnement"
                  label="Environnement"
                  :items="['sandbox', 'production']"
                />
              </v-col>
            </v-row>
            <v-switch
              v-model="tdForm.actif"
              label="Activer l'integration"
              color="primary"
              density="compact"
            />
            <div class="d-flex flex-wrap ga-2 mt-2">
              <v-btn
                color="primary"
                size="small"
                :loading="tdSaving"
                @click="saveTrackdechetsConfig"
              >
                Enregistrer
              </v-btn>
              <v-btn
                variant="outlined"
                color="info"
                size="small"
                :loading="tdTesting"
                @click="testTrackdechetsConfig"
              >
                Tester la connexion
              </v-btn>
            </div>
            <v-alert
              v-if="tdTestResult"
              :type="tdTestResult.success ? 'success' : 'error'"
              variant="tonal"
              density="compact"
              class="mt-3"
            >
              {{ tdTestResult.message }}
            </v-alert>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- USERS -->
    <v-row class="mt-4">
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex flex-wrap align-center">
            Utilisateurs ({{ entreprise.users?.length || 0 }})
            <v-spacer />
            <v-btn
              color="primary"
              size="small"
              prepend-icon="mdi-plus"
              @click="openUserDialog"
            >
              Ajouter un utilisateur
            </v-btn>
          </v-card-title>
          <div style="overflow-x: auto">
            <v-data-table
              :headers="userHeaders"
              :items="entreprise.users || []"
              density="comfortable"
              :items-per-page="10"
            >
              <template #item.role="{ item }">
                <v-chip
                  size="small"
                  :color="roleColor(item.role)"
                >
                  {{ item.role }}
                </v-chip>
              </template>
              <template #item.garages="{ item }">
                <v-chip
                  v-for="g in (item.garages || [])"
                  :key="g.id"
                  size="x-small"
                  class="mr-1"
                >
                  {{ g.nom }}
                </v-chip>
                <span
                  v-if="!item.garages?.length"
                  class="text-grey"
                >Aucun</span>
              </template>
              <template #item.actif="{ item }">
                <v-icon
                  :color="item.actif ? 'success' : 'grey'"
                  size="small"
                >
                  {{ item.actif ? 'mdi-check-circle' : 'mdi-close-circle' }}
                </v-icon>
              </template>
              <template #item.actions="{ item }">
                <v-btn
                  size="small"
                  variant="text"
                  color="info"
                  title="Modifier"
                  @click="openEditUserDialog(item)"
                >
                  <v-icon size="small">
                    mdi-pencil
                  </v-icon>
                </v-btn>
                <v-btn
                  size="small"
                  variant="text"
                  color="primary"
                  title="Se connecter en tant que"
                  @click="impersonate(item.id)"
                >
                  <v-icon size="small">
                    mdi-account-switch
                  </v-icon>
                </v-btn>
                <v-btn
                  size="small"
                  variant="text"
                  color="error"
                  title="Supprimer"
                  @click="confirmDeleteUser(item)"
                >
                  <v-icon size="small">
                    mdi-delete
                  </v-icon>
                </v-btn>
              </template>
            </v-data-table>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- DIALOG GARAGE -->
    <v-dialog
      v-model="garageDialog"
      max-width="550"
      :fullscreen="mobile"
    >
      <v-card>
        <v-card-title>{{ editingGarageId ? 'Modifier le garage' : 'Ajouter un garage' }}</v-card-title>
        <v-card-text>
          <v-text-field
            v-model="garageForm.nom"
            label="Nom du garage"
          />
          <v-text-field
            v-model="garageForm.siret_etablissement"
            label="SIRET etablissement"
          />
          <v-text-field
            v-model="garageForm.adresse"
            label="Adresse"
          />
          <v-row dense>
            <v-col
              cols="12"
              sm="4"
            >
              <v-text-field
                v-model="garageForm.code_postal"
                label="Code postal"
              />
            </v-col>
            <v-col
              cols="12"
              sm="8"
            >
              <v-text-field
                v-model="garageForm.ville"
                label="Ville"
              />
            </v-col>
          </v-row>
          <v-text-field
            v-model="garageForm.telephone"
            label="Telephone"
          />
          <v-text-field
            v-model.number="garageForm.surface_atelier"
            label="Surface atelier (m2)"
            type="number"
          />
          <v-switch
            v-if="editingGarageId"
            v-model="garageForm.actif"
            label="Actif"
            color="success"
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="text"
            @click="garageDialog = false"
          >
            Annuler
          </v-btn>
          <v-btn
            color="primary"
            :loading="savingGarage"
            @click="saveGarage"
          >
            {{ editingGarageId ? 'Enregistrer' : 'Creer' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- DIALOG USER (create / edit) -->
    <v-dialog
      v-model="userDialog"
      max-width="550"
      :fullscreen="mobile"
    >
      <v-card>
        <v-card-title>{{ editingUserId ? 'Modifier' : 'Ajouter' }} un utilisateur</v-card-title>
        <v-card-text>
          <v-row dense>
            <v-col
              cols="12"
              sm="6"
            >
              <v-text-field
                v-model="userForm.prenom"
                label="Prenom"
              />
            </v-col>
            <v-col
              cols="12"
              sm="6"
            >
              <v-text-field
                v-model="userForm.nom"
                label="Nom"
              />
            </v-col>
          </v-row>
          <v-text-field
            v-model="userForm.email"
            label="Email"
            type="email"
          />
          <v-text-field
            v-model="userForm.telephone"
            label="Telephone"
          />
          <v-text-field
            v-model="userForm.password"
            :label="editingUserId ? 'Nouveau mot de passe (laisser vide pour ne pas changer)' : 'Mot de passe'"
            type="password"
          />
          <v-select
            v-model="userForm.role"
            label="Role"
            :items="['admin', 'chef_atelier', 'mecanicien', 'comptable']"
          />
          <v-select
            v-model="userForm.garages_ids"
            label="Garages accessibles"
            :items="garageItems"
            multiple
            chips
            closable-chips
          />
          <v-switch
            v-if="editingUserId"
            v-model="userForm.actif"
            label="Actif"
            color="success"
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="text"
            @click="userDialog = false"
          >
            Annuler
          </v-btn>
          <v-btn
            color="primary"
            :loading="savingUser"
            @click="saveUser"
          >
            {{ editingUserId ? 'Enregistrer' : 'Creer' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <!-- DIALOG CONFIRMATION SUPPRESSION -->
    <v-dialog
      v-model="confirmDialog"
      max-width="400"
    >
      <v-card>
        <v-card-title class="text-h6">
          Confirmer la suppression
        </v-card-title>
        <v-card-text>{{ confirmTitle }}</v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="text"
            @click="confirmDialog = false"
          >
            Annuler
          </v-btn>
          <v-btn
            color="error"
            @click="executeDelete"
          >
            Supprimer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
  <v-progress-circular
    v-else
    indeterminate
    class="ma-auto d-block mt-10"
  />
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useDisplay } from 'vuetify'
import { useAdminStore } from '../../stores/admin'
import { useAuthStore } from '../../stores/auth'
import { useUiStore } from '../../stores/ui'
import api from '../../services/api'
import { storeToRefs } from 'pinia'

const { mobile } = useDisplay()
const route = useRoute()
const router = useRouter()
const adminStore = useAdminStore()
const authStore = useAuthStore()
const uiStore = useUiStore()
const { currentEntreprise: entreprise } = storeToRefs(adminStore)

const subscriptionInfo = ref(null)
const saving = ref(false)
const editForm = reactive({
  raison_sociale: '', siret: '', forme_juridique: '', adresse: '', code_postal: '', ville: '',
  telephone: '', email_contact: '', plan: '', max_garages: 1, max_users: 5,
})

// Garage dialog
const garageDialog = ref(false)
const savingGarage = ref(false)
const editingGarageId = ref(null)
const garageForm = reactive({ nom: '', siret_etablissement: '', adresse: '', code_postal: '', ville: '', telephone: '', surface_atelier: null, actif: true })

// User dialog
const userDialog = ref(false)
const savingUser = ref(false)
const editingUserId = ref(null)
const userForm = reactive({ prenom: '', nom: '', email: '', telephone: '', password: '', role: 'mecanicien', garages_ids: [], actif: true })

// Trackdechets config
const tdSaving = ref(false)
const tdTesting = ref(false)
const tdTestResult = ref(null)
const tdForm = reactive({ api_token: '', environnement: 'sandbox', actif: false })

// Delete confirmation
const confirmDialog = ref(false)
const confirmTitle = ref('')
const confirmAction = ref(null)

const garageHeaders = [
  { title: 'Nom', key: 'nom' },
  { title: 'SIRET', key: 'siret_etablissement' },
  { title: 'Ville', key: 'ville' },
  { title: 'Actif', key: 'actif' },
  { title: '', key: 'actions', sortable: false },
]

const userHeaders = [
  { title: 'Prenom', key: 'prenom' },
  { title: 'Nom', key: 'nom' },
  { title: 'Email', key: 'email' },
  { title: 'Role', key: 'role' },
  { title: 'Garages', key: 'garages', sortable: false },
  { title: 'Actif', key: 'actif' },
  { title: '', key: 'actions', sortable: false },
]

const garageItems = computed(() => {
  return (entreprise.value?.garages || []).map(g => ({ title: g.nom, value: g.id }))
})

function roleColor(role) {
  return { admin: 'primary', chef_atelier: 'info', mecanicien: 'success', comptable: 'warning' }[role] || 'grey'
}

watch(entreprise, (e) => {
  if (e) {
    editForm.raison_sociale = e.raison_sociale || ''
    editForm.siret = e.siret || ''
    editForm.forme_juridique = e.forme_juridique || ''
    editForm.adresse = e.adresse || ''
    editForm.code_postal = e.code_postal || ''
    editForm.ville = e.ville || ''
    editForm.telephone = e.telephone || ''
    editForm.email_contact = e.email_contact || ''
    editForm.plan = e.plan
    editForm.max_garages = e.max_garages
    editForm.max_users = e.max_users
  }
})

async function saveEntreprise() {
  saving.value = true
  try {
    await adminStore.updateEntreprise(entreprise.value.id, editForm)
    uiStore.showSuccess('Entreprise mise a jour')
  } catch (e) {
    uiStore.showError(e.response?.data?.message || 'Erreur')
  }
  saving.value = false
}

async function toggleActive() {
  try {
    const result = await adminStore.toggleActive(entreprise.value.id)
    uiStore.showSuccess(result.message)
  } catch (e) {
    uiStore.showError(e.response?.data?.message || 'Erreur')
  }
}

async function toggleArchive() {
  try {
    const result = await adminStore.toggleArchive(entreprise.value.id)
    uiStore.showSuccess(result.message)
  } catch (e) {
    uiStore.showError(e.response?.data?.message || 'Erreur')
  }
}

// Garage
function openGarageDialog() {
  editingGarageId.value = null
  Object.assign(garageForm, { nom: '', siret_etablissement: '', adresse: '', code_postal: '', ville: '', telephone: '', surface_atelier: null, actif: true })
  garageDialog.value = true
}

function openEditGarageDialog(garage) {
  editingGarageId.value = garage.id
  Object.assign(garageForm, {
    nom: garage.nom || '',
    siret_etablissement: garage.siret_etablissement || '',
    adresse: garage.adresse || '',
    code_postal: garage.code_postal || '',
    ville: garage.ville || '',
    telephone: garage.telephone || '',
    surface_atelier: garage.surface_atelier || null,
    actif: garage.actif,
  })
  garageDialog.value = true
}

async function saveGarage() {
  savingGarage.value = true
  try {
    if (editingGarageId.value) {
      await api.put(`/admin/garages/${editingGarageId.value}`, garageForm)
      await adminStore.fetchEntreprise(entreprise.value.id)
      uiStore.showSuccess('Garage mis a jour')
    } else {
      await adminStore.createGarage(entreprise.value.id, garageForm)
      uiStore.showSuccess('Garage cree')
    }
    garageDialog.value = false
  } catch (e) {
    uiStore.showError(e.response?.data?.message || 'Erreur')
  }
  savingGarage.value = false
}

// User
function openUserDialog() {
  editingUserId.value = null
  Object.assign(userForm, { prenom: '', nom: '', email: '', telephone: '', password: '', role: 'mecanicien', garages_ids: [], actif: true })
  userDialog.value = true
}

function openEditUserDialog(user) {
  editingUserId.value = user.id
  Object.assign(userForm, {
    prenom: user.prenom || '',
    nom: user.nom || '',
    email: user.email || '',
    telephone: user.telephone || '',
    password: '',
    role: user.role || 'mecanicien',
    garages_ids: (user.garages || []).map(g => g.id),
    actif: user.actif,
  })
  userDialog.value = true
}

async function saveUser() {
  savingUser.value = true
  try {
    if (editingUserId.value) {
      await adminStore.updateUser(editingUserId.value, userForm)
      await adminStore.fetchEntreprise(entreprise.value.id)
      uiStore.showSuccess('Utilisateur mis a jour')
    } else {
      await adminStore.createUser(entreprise.value.id, userForm)
      uiStore.showSuccess('Utilisateur cree')
    }
    userDialog.value = false
  } catch (e) {
    uiStore.showError(e.response?.data?.message || 'Erreur')
  }
  savingUser.value = false
}

// Delete
function confirmDeleteUser(user) {
  confirmTitle.value = `Supprimer l'utilisateur ${user.prenom} ${user.nom} (${user.email}) ?`
  confirmAction.value = async () => {
    try {
      await adminStore.deleteUser(user.id)
      uiStore.showSuccess('Utilisateur supprime')
    } catch (e) {
      uiStore.showError(e.response?.data?.message || 'Erreur')
    }
  }
  confirmDialog.value = true
}

function confirmDeleteGarage(garage) {
  confirmTitle.value = `Supprimer le garage "${garage.nom}" ?`
  confirmAction.value = async () => {
    try {
      await adminStore.deleteGarage(garage.id)
      uiStore.showSuccess('Garage supprime')
    } catch (e) {
      uiStore.showError(e.response?.data?.message || 'Erreur')
    }
  }
  confirmDialog.value = true
}

async function executeDelete() {
  confirmDialog.value = false
  if (confirmAction.value) {
    await confirmAction.value()
    confirmAction.value = null
  }
}

// Impersonation
async function impersonate(userId) {
  try {
    const data = await adminStore.loginAs(userId)
    localStorage.setItem('original_token', authStore.token)
    authStore.token = data.token
    authStore.user = data.user
    authStore.permissions = data.permissions || []
    localStorage.setItem('token', data.token)
    router.push('/dashboard')
    uiStore.showSuccess(`Connecte en tant que ${data.user.prenom} ${data.user.nom}`)
  } catch (e) {
    uiStore.showError(e.response?.data?.message || 'Erreur')
  }
}

// Trackdechets: load config for entreprise
async function loadTrackdechetsConfig() {
  const id = route.params.id
  if (!id) return
  try {
    const { data } = await api.get(`/admin/entreprises/${id}/config-trackdechets`)
    if (data.config) {
      tdForm.api_token = ''
      tdForm.environnement = data.config.environnement || 'sandbox'
      tdForm.actif = data.config.actif || false
    }
  } catch { /* no config yet */ }
}

async function saveTrackdechetsConfig() {
  tdSaving.value = true
  try {
    await api.post(`/admin/entreprises/${route.params.id}/config-trackdechets`, tdForm)
    uiStore.showSuccess('Configuration Trackdechets enregistree')
  } catch (e) {
    uiStore.showError(e.response?.data?.message || 'Erreur')
  }
  tdSaving.value = false
}

async function testTrackdechetsConfig() {
  tdTesting.value = true
  tdTestResult.value = null
  try {
    const { data } = await api.post(`/admin/entreprises/${route.params.id}/config-trackdechets/test`)
    tdTestResult.value = data
  } catch (e) {
    tdTestResult.value = { success: false, message: e.response?.data?.message || 'Echec de la connexion' }
  }
  tdTesting.value = false
}

onMounted(async () => {
  try {
    const { data } = await api.get(`/admin/entreprises/${route.params.id}`)
    adminStore.currentEntreprise = data.data || data
    subscriptionInfo.value = data.subscription || null
  } catch (e) {
    console.error('Erreur chargement entreprise:', e)
  }
  loadTrackdechetsConfig()
})
</script>
