<template>
  <div>
    <h1 class="text-h5 font-weight-bold mb-6">
      Mon profil
    </h1>

    <v-row>
      <!-- Informations personnelles -->
      <v-col
        cols="12"
        md="6"
      >
        <v-card>
          <v-card-title>Informations personnelles</v-card-title>
          <v-card-text>
            <v-form
              ref="profileFormRef"
              @submit.prevent="saveProfile"
            >
              <v-text-field
                v-model="profileForm.prenom"
                label="Prenom"
                :rules="[rules.required]"
              />
              <v-text-field
                v-model="profileForm.nom"
                label="Nom"
                :rules="[rules.required]"
              />
              <v-text-field
                v-model="profileForm.email"
                label="Email"
                type="email"
                :rules="[rules.required, rules.email]"
              />
              <v-text-field
                v-model="profileForm.telephone"
                label="Telephone"
              />
              <v-btn
                type="submit"
                color="primary"
                :loading="savingProfile"
                class="mt-2"
              >
                Enregistrer
              </v-btn>
            </v-form>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Mot de passe -->
      <v-col
        cols="12"
        md="6"
      >
        <v-card>
          <v-card-title>Changer le mot de passe</v-card-title>
          <v-card-text>
            <v-form
              ref="passwordFormRef"
              @submit.prevent="changePassword"
            >
              <v-text-field
                v-model="passwordForm.current_password"
                label="Mot de passe actuel"
                type="password"
                :rules="[rules.required]"
              />
              <v-text-field
                v-model="passwordForm.password"
                label="Nouveau mot de passe"
                type="password"
                :rules="[rules.required, rules.minLength]"
              />
              <v-text-field
                v-model="passwordForm.password_confirmation"
                label="Confirmer le mot de passe"
                type="password"
                :rules="[rules.required, rules.passwordMatch]"
              />
              <v-btn
                type="submit"
                color="primary"
                :loading="savingPassword"
                class="mt-2"
              >
                Modifier
              </v-btn>
            </v-form>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Preferences de notifications -->
    <v-row class="mt-4">
      <v-col
        cols="12"
        md="6"
      >
        <v-card>
          <v-card-title>Notifications</v-card-title>
          <v-card-text>
            <v-switch
              v-model="profileForm.preferences_notifications.email_alertes_critiques"
              label="Alertes critiques par email"
              color="primary"
              density="compact"
              hide-details
            />
            <v-switch
              v-model="profileForm.preferences_notifications.email_rappels"
              label="Rappels par email"
              color="primary"
              density="compact"
              hide-details
              class="mt-2"
            />
            <v-btn
              color="primary"
              size="small"
              class="mt-4"
              :loading="savingProfile"
              @click="saveProfile"
            >
              Enregistrer
            </v-btn>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Informations compte -->
      <v-col
        cols="12"
        md="6"
      >
        <v-card>
          <v-card-title>Mon compte</v-card-title>
          <v-card-text>
            <v-list density="compact">
              <v-list-item>
                <template #prepend>
                  <v-icon color="primary">
                    mdi-domain
                  </v-icon>
                </template>
                <v-list-item-title>Entreprise</v-list-item-title>
                <v-list-item-subtitle>{{ authStore.user?.entreprise?.raison_sociale || '-' }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <template #prepend>
                  <v-icon color="primary">
                    mdi-shield-account
                  </v-icon>
                </template>
                <v-list-item-title>Role</v-list-item-title>
                <v-list-item-subtitle>{{ authStore.user?.role || '-' }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <template #prepend>
                  <v-icon color="primary">
                    mdi-calendar
                  </v-icon>
                </template>
                <v-list-item-title>Membre depuis</v-list-item-title>
                <v-list-item-subtitle>{{ formatDate(authStore.user?.created_at) }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useUiStore } from '../../stores/ui'
import api from '../../services/api'
import { required as requiredRule, email as emailRule, minLength, passwordMatch } from '../../services/validators'

const authStore = useAuthStore()
const uiStore = useUiStore()

const profileFormRef = ref(null)
const passwordFormRef = ref(null)
const savingProfile = ref(false)
const savingPassword = ref(false)

const profileForm = reactive({
  prenom: '',
  nom: '',
  email: '',
  telephone: '',
  preferences_notifications: {
    email_alertes_critiques: true,
    email_rappels: true,
  },
})

const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const rules = {
  required: requiredRule,
  email: emailRule,
  minLength: minLength(8),
  passwordMatch: passwordMatch(() => passwordForm.password),
}

function loadUser() {
  const u = authStore.user
  if (!u) return
  profileForm.prenom = u.prenom || ''
  profileForm.nom = u.nom || ''
  profileForm.email = u.email || ''
  profileForm.telephone = u.telephone || ''
  profileForm.preferences_notifications = {
    email_alertes_critiques: u.preferences_notifications?.email_alertes_critiques ?? true,
    email_rappels: u.preferences_notifications?.email_rappels ?? true,
  }
}

async function saveProfile() {
  const { valid } = await profileFormRef.value.validate()
  if (!valid) return

  savingProfile.value = true
  try {
    const { data } = await api.put('/auth/profile', profileForm)
    authStore.user = data.user
    uiStore.showSuccess('Profil mis a jour.')
  } catch (e) {
    uiStore.showError(e.response?.data?.message || 'Erreur')
  }
  savingProfile.value = false
}

async function changePassword() {
  const { valid } = await passwordFormRef.value.validate()
  if (!valid) return

  savingPassword.value = true
  try {
    await api.put('/auth/password', passwordForm)
    uiStore.showSuccess('Mot de passe modifie.')
    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
    passwordFormRef.value.resetValidation()
  } catch (e) {
    uiStore.showError(e.response?.data?.message || 'Erreur')
  }
  savingPassword.value = false
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('fr-FR', { year: 'numeric', month: 'long', day: 'numeric' })
}

onMounted(loadUser)
</script>
