<template>
  <v-container
    class="fill-height"
    fluid
    style="background: linear-gradient(135deg, #1B5E20, #2E7D32, #43A047)"
  >
    <v-row
      align="center"
      justify="center"
    >
      <v-col
        cols="12"
        sm="11"
        md="9"
        lg="7"
      >
        <v-card
          class="pa-6 pa-sm-8 register-card"
          rounded="xl"
          elevation="12"
        >
          <!-- Logo + titre -->
          <div class="text-center mb-2">
            <svg
              width="52"
              height="52"
              viewBox="0 0 32 32"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                d="M5 26c0 0 2.5-9.5 9.5-14c2.3-1.5 6-2.5 8.5-1.2c-1 5-4 8.5-8.5 11c-2.5 1.4-5 1.8-6 1.8"
                stroke="#2E7D32"
                stroke-width="2.2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
              <path
                d="M14.5 12c0 0-1.2 5.5-1.2 9.5c0 1.8.6 3 1.2 4.2"
                stroke="#2E7D32"
                stroke-width="1.6"
                stroke-linecap="round"
              />
              <path
                d="M10 18.5c2.5 0 4.5.6 5.8 2.2"
                stroke="#2E7D32"
                stroke-width="1.6"
                stroke-linecap="round"
              />
            </svg>
            <h1 class="text-h5 font-weight-bold text-primary mt-1">
              GreenPilot
            </h1>
            <p class="text-body-2 text-medium-emphasis">
              Creez votre compte en quelques etapes
            </p>
          </div>

          <!-- Stepper visuel -->
          <div class="d-flex align-center justify-center ga-2 mb-6">
            <v-avatar
              :color="step >= 1 ? 'primary' : 'grey-lighten-2'"
              size="32"
              class="step-badge"
            >
              <v-icon
                v-if="step > 1"
                size="18"
              >
                mdi-check
              </v-icon>
              <span
                v-else
                class="text-caption font-weight-bold"
                :class="step === 1 ? 'text-white' : ''"
              >1</span>
            </v-avatar>
            <div
              class="step-line"
              :class="{ active: step >= 2 }"
            />
            <v-avatar
              :color="step >= 2 ? 'primary' : 'grey-lighten-2'"
              size="32"
              class="step-badge"
            >
              <span
                class="text-caption font-weight-bold"
                :class="step === 2 ? 'text-white' : ''"
              >2</span>
            </v-avatar>
          </div>

          <!-- ==================== STEP 1 : Plan ==================== -->
          <div
            v-if="step === 1"
            class="step-content"
          >
            <div class="text-center mb-5">
              <h2 class="text-h6 font-weight-bold">
                Choisissez votre plan
              </h2>
              <p class="text-body-2 text-medium-emphasis mt-1">
                <v-icon
                  size="16"
                  color="success"
                  class="mr-1"
                >
                  mdi-gift-outline
                </v-icon>
                Essai gratuit de 14 jours, sans carte bancaire
              </p>
            </div>

            <v-row dense>
              <!-- Standard -->
              <v-col
                cols="12"
                sm="6"
              >
                <v-card
                  :class="['plan-card', { 'plan-active': form.plan === 'standard' }]"
                  variant="outlined"
                  rounded="lg"
                  @click="form.plan = 'standard'"
                >
                  <v-card-text class="pa-5">
                    <div class="d-flex align-center justify-space-between mb-3">
                      <div class="d-flex align-center">
                        <v-avatar
                          color="primary"
                          variant="tonal"
                          size="40"
                          rounded="lg"
                          class="plan-avatar mr-3"
                        >
                          <v-icon size="22">
                            mdi-rocket-launch-outline
                          </v-icon>
                        </v-avatar>
                        <span class="text-subtitle-1 font-weight-bold">Standard</span>
                      </div>
                      <v-icon
                        v-if="form.plan === 'standard'"
                        size="22"
                        class="plan-check"
                      >
                        mdi-check-circle
                      </v-icon>
                    </div>

                    <div class="mb-3">
                      <span class="text-h4 font-weight-black">49</span>
                      <span class="text-body-2">&euro; / mois</span>
                    </div>

                    <v-divider class="mb-3 plan-divider" />

                    <div class="text-body-2 plan-features">
                      <div
                        v-for="feat in standardFeatures"
                        :key="feat"
                        class="d-flex align-center mb-2"
                      >
                        <v-icon
                          size="16"
                          color="success"
                          class="mr-2 plan-feat-icon"
                        >
                          mdi-check-circle
                        </v-icon>
                        {{ feat }}
                      </div>
                    </div>
                  </v-card-text>
                </v-card>
              </v-col>

              <!-- Premium -->
              <v-col
                cols="12"
                sm="6"
              >
                <v-card
                  :class="['plan-card', { 'plan-active': form.plan === 'premium' }]"
                  variant="outlined"
                  rounded="lg"
                  @click="form.plan = 'premium'"
                >
                  <!-- Badge populaire -->
                  <v-chip
                    v-if="form.plan !== 'premium'"
                    color="warning"
                    size="x-small"
                    variant="flat"
                    class="popular-badge"
                  >
                    Populaire
                  </v-chip>

                  <v-card-text class="pa-5">
                    <div class="d-flex align-center justify-space-between mb-3">
                      <div class="d-flex align-center">
                        <v-avatar
                          color="warning"
                          variant="tonal"
                          size="40"
                          rounded="lg"
                          class="plan-avatar mr-3"
                        >
                          <v-icon size="22">
                            mdi-star-outline
                          </v-icon>
                        </v-avatar>
                        <span class="text-subtitle-1 font-weight-bold">Premium</span>
                      </div>
                      <v-icon
                        v-if="form.plan === 'premium'"
                        size="22"
                        class="plan-check"
                      >
                        mdi-check-circle
                      </v-icon>
                    </div>

                    <div class="mb-3">
                      <span class="text-h4 font-weight-black">99</span>
                      <span class="text-body-2">&euro; / mois</span>
                    </div>

                    <v-divider class="mb-3 plan-divider" />

                    <div class="text-body-2 plan-features">
                      <div
                        v-for="feat in premiumFeatures"
                        :key="feat"
                        class="d-flex align-center mb-2"
                      >
                        <v-icon
                          size="16"
                          color="success"
                          class="mr-2 plan-feat-icon"
                        >
                          mdi-check-circle
                        </v-icon>
                        {{ feat }}
                      </div>
                    </div>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>

            <v-btn
              color="primary"
              block
              size="large"
              rounded="lg"
              class="mt-6 text-none"
              :disabled="!form.plan"
              @click="step = 2"
            >
              Continuer
              <v-icon end>
                mdi-arrow-right
              </v-icon>
            </v-btn>
          </div>

          <!-- ==================== STEP 2 : Formulaire ==================== -->
          <div
            v-if="step === 2"
            class="step-content"
          >
            <div class="d-flex align-center mb-4">
              <v-btn
                variant="text"
                size="small"
                color="primary"
                class="mr-2"
                @click="step = 1"
              >
                <v-icon
                  start
                  size="18"
                >
                  mdi-arrow-left
                </v-icon>
                Retour
              </v-btn>
              <v-spacer />
              <v-chip
                color="primary"
                variant="tonal"
                size="small"
                prepend-icon="mdi-check-circle"
              >
                Plan {{ form.plan === 'premium' ? 'Premium' : 'Standard' }} — 14 jours gratuits
              </v-chip>
            </div>

            <div class="text-center mb-5">
              <h2 class="text-h6 font-weight-bold">
                Vos informations
              </h2>
              <p class="text-body-2 text-medium-emphasis mt-1">
                Remplissez les champs ci-dessous pour demarrer
              </p>
            </div>

            <v-form
              :disabled="loading"
              @submit.prevent="handleRegister"
            >
              <!-- Identite -->
              <p
                class="text-caption text-uppercase font-weight-bold text-medium-emphasis mb-2"
                style="letter-spacing: 0.08em"
              >
                Identite
              </p>
              <v-row dense>
                <v-col
                  cols="12"
                  sm="6"
                >
                  <v-text-field
                    v-model="form.prenom"
                    label="Prenom"
                    prepend-inner-icon="mdi-account-outline"
                    :error-messages="errors.prenom"
                    variant="outlined"
                    density="comfortable"
                  />
                </v-col>
                <v-col
                  cols="12"
                  sm="6"
                >
                  <v-text-field
                    v-model="form.nom"
                    label="Nom"
                    prepend-inner-icon="mdi-account-outline"
                    :error-messages="errors.nom"
                    variant="outlined"
                    density="comfortable"
                  />
                </v-col>
              </v-row>
              <v-text-field
                v-model="form.email"
                label="Adresse email"
                type="email"
                prepend-inner-icon="mdi-email-outline"
                :error-messages="errors.email"
                variant="outlined"
                density="comfortable"
              />

              <!-- Entreprise -->
              <p
                class="text-caption text-uppercase font-weight-bold text-medium-emphasis mb-2 mt-2"
                style="letter-spacing: 0.08em"
              >
                Entreprise
              </p>
              <v-text-field
                v-model="form.raison_sociale"
                label="Raison sociale"
                prepend-inner-icon="mdi-domain"
                :error-messages="errors.raison_sociale"
                variant="outlined"
                density="comfortable"
              />
              <v-text-field
                v-model="form.siret"
                label="Numero SIRET"
                prepend-inner-icon="mdi-card-account-details-outline"
                maxlength="14"
                counter="14"
                :error-messages="errors.siret"
                variant="outlined"
                density="comfortable"
              />

              <!-- Securite -->
              <p
                class="text-caption text-uppercase font-weight-bold text-medium-emphasis mb-2 mt-2"
                style="letter-spacing: 0.08em"
              >
                Securite
              </p>
              <v-text-field
                v-model="form.password"
                label="Mot de passe"
                :type="showPassword ? 'text' : 'password'"
                prepend-inner-icon="mdi-lock-outline"
                :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                :error-messages="errors.password"
                variant="outlined"
                density="comfortable"
                @click:append-inner="showPassword = !showPassword"
              />
              <v-text-field
                v-model="form.password_confirmation"
                label="Confirmer le mot de passe"
                :type="showPasswordConfirm ? 'text' : 'password'"
                prepend-inner-icon="mdi-lock-check-outline"
                :append-inner-icon="showPasswordConfirm ? 'mdi-eye-off' : 'mdi-eye'"
                variant="outlined"
                density="comfortable"
                @click:append-inner="showPasswordConfirm = !showPasswordConfirm"
              />

              <v-btn
                type="submit"
                color="primary"
                block
                size="large"
                rounded="lg"
                class="mt-2 text-none"
                :loading="loading"
              >
                <v-icon start>
                  mdi-account-plus
                </v-icon>
                Creer mon compte
              </v-btn>
            </v-form>
          </div>

          <!-- Lien login + erreur -->
          <div class="text-center mt-5">
            <span class="text-body-2 text-medium-emphasis">Deja un compte ?</span>
            <router-link
              to="/login"
              class="text-primary text-body-2 font-weight-medium text-decoration-none ml-1"
            >
              Se connecter
            </router-link>
          </div>

          <v-alert
            v-if="errorMessage"
            type="error"
            variant="tonal"
            class="mt-4"
            density="compact"
            closable
            @click:close="errorMessage = ''"
          >
            {{ errorMessage }}
          </v-alert>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const step = ref(1)
const showPassword = ref(false)
const showPasswordConfirm = ref(false)
const form = reactive({ nom: '', prenom: '', email: '', password: '', password_confirmation: '', raison_sociale: '', siret: '', plan: 'standard' })
const errors = reactive({})
const loading = ref(false)
const errorMessage = ref('')

const standardFeatures = [
  '3 garages',
  '15 utilisateurs',
  'Sync Trackdechets',
  'Score conformite ICPE',
  'Support prioritaire',
]

const premiumFeatures = [
  'Garages illimites',
  'Utilisateurs illimites',
  'Tout du Standard',
  'API personnalisee',
  'Account manager dedie',
]

async function handleRegister() {
  loading.value = true
  errorMessage.value = ''
  Object.keys(errors).forEach(k => delete errors[k])
  try {
    await authStore.register(form)
    router.push({ name: 'onboarding' })
  } catch (error) {
    if (error.response?.status === 422) {
      Object.assign(errors, error.response.data.errors || {})
    } else {
      errorMessage.value = error.response?.data?.message || 'Erreur lors de l\'inscription'
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.register-card {
  animation: fadeIn 0.5s ease-out;
}

.step-content {
  animation: slideIn 0.3s ease-out;
}

.step-line {
  width: 60px;
  height: 3px;
  background: #e0e0e0;
  border-radius: 2px;
  transition: background 0.3s ease;
}

.step-line.active {
  background: #2E7D32;
}

.step-badge {
  transition: all 0.3s ease;
}

.plan-card {
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
  overflow: visible;
}

.plan-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

/* Selected plan: green background + all text/icons white */
.plan-active {
  background-color: #2E7D32 !important;
  border-color: #2E7D32 !important;
  color: #fff !important;
  box-shadow: 0 8px 32px rgba(46, 125, 50, 0.35);
}

.plan-active :deep(.v-card-text) {
  color: #fff !important;
}

.plan-active :deep(.v-avatar) {
  background-color: rgba(255, 255, 255, 0.2) !important;
}

.plan-active :deep(.v-avatar .v-icon) {
  color: #fff !important;
}

.plan-active :deep(.plan-check) {
  color: #fff !important;
}

.plan-active :deep(.plan-feat-icon) {
  color: #fff !important;
}

.plan-active :deep(.v-divider) {
  border-color: rgba(255, 255, 255, 0.3) !important;
}

.plan-active .plan-features {
  color: rgba(255, 255, 255, 0.9) !important;
}

.popular-badge {
  position: absolute;
  top: -10px;
  right: 12px;
  z-index: 1;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(16px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes slideIn {
  from { opacity: 0; transform: translateX(12px); }
  to { opacity: 1; transform: translateX(0); }
}
</style>
