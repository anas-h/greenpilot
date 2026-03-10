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
              Creez votre compte et demarrez votre essai gratuit
            </p>
          </div>

          <!-- Bandeau plan Standard -->
          <v-alert
            type="info"
            variant="tonal"
            density="compact"
            class="mb-5"
          >
            <div class="d-flex align-center">
              <v-icon
                size="18"
                class="mr-2"
              >
                mdi-gift-outline
              </v-icon>
              <span class="text-body-2">
                <strong>Plan Standard</strong> — Essai gratuit de 14 jours, sans carte bancaire
              </span>
            </div>
          </v-alert>

          <!-- ==================== Formulaire ==================== -->
          <div class="step-content">
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

          <!-- Note Premium -->
          <div
            class="text-center mt-4 pa-3 rounded-lg"
            style="background: rgba(0,0,0,0.03)"
          >
            <p class="text-body-2 text-medium-emphasis mb-1">
              Besoin de plus de garages ou d'utilisateurs ?
            </p>
            <router-link
              to="/contact"
              class="text-primary text-body-2 font-weight-medium text-decoration-none"
            >
              Contactez-nous pour un devis personnalise
              <v-icon
                size="14"
                class="ml-1"
              >
                mdi-arrow-right
              </v-icon>
            </router-link>
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

const showPassword = ref(false)
const showPasswordConfirm = ref(false)
const form = reactive({ nom: '', prenom: '', email: '', password: '', password_confirmation: '', raison_sociale: '', siret: '', plan: 'standard' })
const errors = reactive({})
const loading = ref(false)
const errorMessage = ref('')

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

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(16px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes slideIn {
  from { opacity: 0; transform: translateX(12px); }
  to { opacity: 1; transform: translateX(0); }
}
</style>
