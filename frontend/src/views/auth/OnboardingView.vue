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
          class="pa-6 pa-sm-8 onboarding-card"
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
              Configurez votre premier garage en quelques etapes
            </p>
          </div>

          <!-- Stepper visuel -->
          <div class="d-flex align-center justify-center ga-1 mb-6">
            <div
              v-for="(s, i) in stepLabels"
              :key="i"
              class="d-flex align-center"
            >
              <div class="text-center">
                <v-avatar
                  :color="step > i + 1 ? 'primary' : step === i + 1 ? 'primary' : 'grey-lighten-2'"
                  size="36"
                  class="step-badge"
                >
                  <v-icon
                    v-if="step > i + 1"
                    size="18"
                    color="white"
                  >
                    mdi-check
                  </v-icon>
                  <span
                    v-else
                    class="text-caption font-weight-bold"
                    :class="step === i + 1 ? 'text-white' : ''"
                  >{{ i + 1 }}</span>
                </v-avatar>
                <p
                  class="text-caption mt-1"
                  :class="step >= i + 1 ? 'text-primary font-weight-medium' : 'text-medium-emphasis'"
                >
                  {{ s }}
                </p>
              </div>
              <div
                v-if="i < stepLabels.length - 1"
                class="step-line mx-2"
                :class="{ active: step > i + 1 }"
              />
            </div>
          </div>

          <!-- ==================== STEP 1 : Garage ==================== -->
          <div
            v-if="step === 1"
            class="step-content"
          >
            <div class="text-center mb-5">
              <h2 class="text-h6 font-weight-bold">
                Informations du garage
              </h2>
              <p class="text-body-2 text-medium-emphasis mt-1">
                Renseignez les informations de votre etablissement
              </p>
            </div>

            <v-form ref="garageFormRef">
              <p
                class="text-caption text-uppercase font-weight-bold text-medium-emphasis mb-2"
                style="letter-spacing: 0.08em"
              >
                Etablissement
              </p>
              <v-text-field
                v-model="garageForm.nom"
                label="Nom du garage"
                prepend-inner-icon="mdi-garage"
                :rules="[v => !!v || 'Requis']"
                variant="outlined"
                density="comfortable"
              />
              <v-text-field
                v-model="garageForm.adresse"
                label="Adresse"
                prepend-inner-icon="mdi-map-marker-outline"
                :rules="[v => !!v || 'Requis']"
                variant="outlined"
                density="comfortable"
              />
              <v-row dense>
                <v-col
                  cols="12"
                  sm="4"
                >
                  <v-text-field
                    v-model="garageForm.code_postal"
                    label="Code postal"
                    prepend-inner-icon="mdi-mailbox-outline"
                    :rules="[v => !!v || 'Requis']"
                    variant="outlined"
                    density="comfortable"
                  />
                </v-col>
                <v-col
                  cols="12"
                  sm="8"
                >
                  <v-text-field
                    v-model="garageForm.ville"
                    label="Ville"
                    prepend-inner-icon="mdi-city-variant-outline"
                    :rules="[v => !!v || 'Requis']"
                    variant="outlined"
                    density="comfortable"
                  />
                </v-col>
              </v-row>

              <p
                class="text-caption text-uppercase font-weight-bold text-medium-emphasis mb-2 mt-2"
                style="letter-spacing: 0.08em"
              >
                Coordonnees
              </p>
              <v-text-field
                v-model="garageForm.telephone"
                label="Telephone"
                prepend-inner-icon="mdi-phone-outline"
                variant="outlined"
                density="comfortable"
              />
              <v-text-field
                v-model="garageForm.surface_atelier"
                label="Surface atelier (m2)"
                prepend-inner-icon="mdi-ruler-square"
                type="number"
                variant="outlined"
                density="comfortable"
              />
            </v-form>

            <p
              class="text-caption text-uppercase font-weight-bold text-medium-emphasis mb-2 mt-2"
              style="letter-spacing: 0.08em"
            >
              Activites
            </p>
            <v-chip-group
              v-model="garageForm.activites"
              multiple
              column
            >
              <v-chip
                v-for="a in activiteOptions"
                :key="a"
                :value="a"
                filter
                variant="outlined"
                color="primary"
              >
                {{ a }}
              </v-chip>
            </v-chip-group>

            <v-btn
              color="primary"
              block
              size="large"
              rounded="lg"
              class="mt-6 text-none"
              :loading="loading"
              @click="nextStep"
            >
              Continuer
              <v-icon end>
                mdi-arrow-right
              </v-icon>
            </v-btn>
          </div>

          <!-- ==================== STEP 2 : Dechets ==================== -->
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
                @click="step--"
              >
                <v-icon
                  start
                  size="18"
                >
                  mdi-arrow-left
                </v-icon>
                Precedent
              </v-btn>
            </div>

            <div class="text-center mb-5">
              <h2 class="text-h6 font-weight-bold">
                Types de dechets produits
              </h2>
              <p class="text-body-2 text-medium-emphasis mt-1">
                Selectionnez les types de dechets que vous produisez
              </p>
            </div>

            <div
              v-for="group in groupedTypes"
              :key="group.label"
              class="mb-4"
            >
              <p
                class="text-caption text-uppercase font-weight-bold text-medium-emphasis mb-2"
                style="letter-spacing: 0.08em"
              >
                {{ group.label }}
              </p>
              <v-checkbox
                v-for="t in group.items"
                :key="t.id"
                v-model="selectedTypes"
                :value="t.id"
                :label="`${t.denomination} (${t.code_europeen})`"
                density="compact"
                hide-details
                color="primary"
              />
            </div>

            <v-btn
              color="primary"
              block
              size="large"
              rounded="lg"
              class="mt-6 text-none"
              @click="nextStep"
            >
              Continuer
              <v-icon end>
                mdi-arrow-right
              </v-icon>
            </v-btn>
          </div>

          <!-- ==================== STEP 3 : Conteneurs ==================== -->
          <div
            v-if="step === 3"
            class="step-content"
          >
            <div class="d-flex align-center mb-4">
              <v-btn
                variant="text"
                size="small"
                color="primary"
                class="mr-2"
                @click="step--"
              >
                <v-icon
                  start
                  size="18"
                >
                  mdi-arrow-left
                </v-icon>
                Precedent
              </v-btn>
            </div>

            <div class="text-center mb-5">
              <h2 class="text-h6 font-weight-bold">
                Premiers conteneurs
              </h2>
              <p class="text-body-2 text-medium-emphasis mt-1">
                Des conteneurs par defaut seront crees pour les types selectionnes
              </p>
            </div>

            <v-alert
              type="info"
              variant="tonal"
              prominent
              class="mb-4"
            >
              <template #prepend>
                <v-avatar
                  color="info"
                  variant="tonal"
                  size="44"
                  rounded="lg"
                >
                  <v-icon size="22">
                    mdi-package-variant
                  </v-icon>
                </v-avatar>
              </template>
              <div class="text-subtitle-2 font-weight-bold">
                {{ selectedTypes.length }} type{{ selectedTypes.length > 1 ? 's' : '' }} selectionne{{ selectedTypes.length > 1 ? 's' : '' }}
              </div>
              <div class="text-caption">
                Des conteneurs seront crees automatiquement. Vous pourrez les modifier ensuite.
              </div>
            </v-alert>

            <v-btn
              color="primary"
              block
              size="large"
              rounded="lg"
              class="mt-6 text-none"
              :loading="loading"
              @click="nextStep"
            >
              <v-icon start>
                mdi-check-all
              </v-icon>
              Terminer la configuration
            </v-btn>
          </div>

          <!-- ==================== STEP 4 : Termine ==================== -->
          <div
            v-if="step === 4"
            class="step-content text-center"
          >
            <v-avatar
              color="success"
              variant="tonal"
              size="80"
              class="mb-4"
            >
              <v-icon size="44">
                mdi-check-circle
              </v-icon>
            </v-avatar>
            <h2 class="text-h5 font-weight-bold">
              Configuration terminee !
            </h2>
            <p class="text-body-1 text-medium-emphasis mt-2">
              Votre garage est pret a utiliser.
            </p>

            <v-btn
              color="primary"
              block
              size="large"
              rounded="lg"
              class="mt-6 text-none"
              @click="router.push({ name: 'dashboard' })"
            >
              <v-icon start>
                mdi-view-dashboard
              </v-icon>
              Acceder au tableau de bord
            </v-btn>
          </div>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useGarageStore } from '../../stores/garage'
import { useUiStore } from '../../stores/ui'
import api from '../../services/api'

const router = useRouter()
const garageStore = useGarageStore()
const uiStore = useUiStore()

const step = ref(1)
const loading = ref(false)
const garageFormRef = ref(null)
const stepLabels = ['Garage', 'Dechets', 'Conteneurs', 'Termine']
const activiteOptions = ['mecanique', 'carrosserie', 'peinture', 'pneumatique']
const garageForm = reactive({ nom: '', adresse: '', code_postal: '', ville: '', telephone: '', surface_atelier: null, activites: [] })
const typesDechets = ref([])
const selectedTypes = ref([])

const groupedTypes = computed(() => [
  { label: 'Dechets dangereux (DD)', items: typesDechets.value.filter(t => t.dangereux) },
  { label: 'Dechets non dangereux (DND)', items: typesDechets.value.filter(t => !t.dangereux) },
])

async function nextStep() {
  if (step.value === 1 && garageFormRef.value) {
    const { valid } = await garageFormRef.value.validate()
    if (!valid) return
  }
  loading.value = true
  try {
    if (step.value === 1) {
      const garage = await garageStore.createGarage(garageForm)
      garageStore.setCurrentGarage(garage.id)
    } else if (step.value === 3) {
      await api.post('/onboarding/complete', { type_dechet_ids: selectedTypes.value })
    }
    step.value++
  } catch (error) {
    uiStore.showError(error.response?.data?.message || 'Erreur')
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  try {
    const { data } = await api.get('/types-dechets/systeme')
    typesDechets.value = data.data || data
  } catch { /* ignore */ }
})
</script>

<style scoped>
.onboarding-card {
  animation: fadeIn 0.5s ease-out;
}

.step-content {
  animation: slideIn 0.3s ease-out;
}

.step-line {
  width: 40px;
  height: 3px;
  background: #e0e0e0;
  border-radius: 2px;
  transition: background 0.3s ease;
  margin-bottom: 20px;
}

.step-line.active {
  background: #2E7D32;
}

.step-badge {
  transition: all 0.3s ease;
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
