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
        sm="8"
        md="4"
      >
        <v-card
          class="pa-8"
          rounded="xl"
          elevation="8"
        >
          <div class="text-center mb-6">
            <svg
              width="64"
              height="64"
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
            <h1 class="text-h4 font-weight-bold text-primary mt-2">
              GreenPilot
            </h1>
            <p class="text-body-2 text-grey mt-1">
              Gestion des dechets pour garages
            </p>
          </div>
          <v-form
            :disabled="loading"
            @submit.prevent="handleLogin"
          >
            <v-text-field
              v-model="form.email"
              label="Email"
              type="email"
              prepend-inner-icon="mdi-email"
              :error-messages="errors.email"
              class="mb-2"
            />
            <v-text-field
              v-model="form.password"
              label="Mot de passe"
              :type="showPassword ? 'text' : 'password'"
              prepend-inner-icon="mdi-lock"
              :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
              :error-messages="errors.password"
              class="mb-4"
              @click:append-inner="showPassword = !showPassword"
            />
            <v-btn
              type="submit"
              color="primary"
              block
              size="large"
              :loading="loading"
            >
              Se connecter
            </v-btn>
          </v-form>
          <v-alert
            v-if="errorMessage"
            type="error"
            variant="tonal"
            class="mt-4"
            density="compact"
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
import { useUiStore } from '../../stores/ui'

const router = useRouter()
const authStore = useAuthStore()
const uiStore = useUiStore()

const form = reactive({ email: '', password: '' })
const errors = reactive({ email: '', password: '' })
const loading = ref(false)
const showPassword = ref(false)
const errorMessage = ref('')

async function handleLogin() {
  loading.value = true
  errorMessage.value = ''
  errors.email = ''
  errors.password = ''
  try {
    await authStore.login(form)
    uiStore.showSuccess('Connexion reussie')
    router.push({ name: 'dashboard' })
  } catch (error) {
    if (error.response?.status === 422) {
      const e = error.response.data.errors || {}
      errors.email = e.email?.[0] || ''
      errors.password = e.password?.[0] || ''
    } else {
      errorMessage.value = error.response?.data?.message || 'Erreur de connexion'
    }
  } finally {
    loading.value = false
  }
}
</script>
