<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center mb-6">
      <v-btn
        icon="mdi-arrow-left"
        variant="text"
        color="primary"
        to="/abonnement"
        title="Retour a l'abonnement"
        class="mr-2"
      />
      <div>
        <h1 class="text-h5 font-weight-bold">
          Paiement
        </h1>
        <p class="text-body-2 text-medium-emphasis">
          Finalisez votre abonnement GreenPilot
        </p>
      </div>
    </div>

    <v-row>
      <!-- Left: Payment form -->
      <v-col
        cols="12"
        md="7"
      >
        <v-card
          class="checkout-card"
          rounded="lg"
          :style="{ animationDelay: '80ms' }"
        >
          <v-card-text class="pa-6">
            <!-- Stepper visuel -->
            <div class="d-flex align-center justify-center ga-2 mb-6">
              <div class="text-center">
                <v-avatar
                  :color="paymentStep >= 1 ? 'primary' : 'grey-lighten-2'"
                  size="36"
                  class="step-badge"
                >
                  <v-icon
                    v-if="paymentStep > 1"
                    size="18"
                    color="white"
                  >
                    mdi-check
                  </v-icon>
                  <span
                    v-else
                    class="text-caption font-weight-bold text-white"
                  >1</span>
                </v-avatar>
                <p class="text-caption mt-1 text-primary font-weight-medium">
                  Plan
                </p>
              </div>
              <div
                class="step-line"
                :class="{ active: paymentStep >= 2 }"
              />
              <div class="text-center">
                <v-avatar
                  :color="paymentStep >= 2 ? 'primary' : 'grey-lighten-2'"
                  size="36"
                  class="step-badge"
                >
                  <v-icon
                    v-if="paymentStep > 2"
                    size="18"
                    color="white"
                  >
                    mdi-check
                  </v-icon>
                  <span
                    v-else
                    class="text-caption font-weight-bold"
                    :class="paymentStep >= 2 ? 'text-white' : ''"
                  >2</span>
                </v-avatar>
                <p
                  class="text-caption mt-1"
                  :class="paymentStep >= 2 ? 'text-primary font-weight-medium' : 'text-medium-emphasis'"
                >
                  Paiement
                </p>
              </div>
              <div
                class="step-line"
                :class="{ active: paymentStep >= 3 }"
              />
              <div class="text-center">
                <v-avatar
                  :color="paymentStep >= 3 ? 'success' : 'grey-lighten-2'"
                  size="36"
                  class="step-badge"
                >
                  <v-icon
                    v-if="paymentStep >= 3"
                    size="18"
                    color="white"
                  >
                    mdi-check
                  </v-icon>
                  <span
                    v-else
                    class="text-caption font-weight-bold"
                  >3</span>
                </v-avatar>
                <p
                  class="text-caption mt-1"
                  :class="paymentStep >= 3 ? 'text-success font-weight-medium' : 'text-medium-emphasis'"
                >
                  Confirme
                </p>
              </div>
            </div>

            <!-- Step 2: Payment form -->
            <div v-if="paymentStep === 2">
              <div class="text-center mb-5">
                <h2 class="text-h6 font-weight-bold">
                  Informations de paiement
                </h2>
                <p class="text-body-2 text-medium-emphasis mt-1">
                  <v-icon
                    size="16"
                    color="success"
                    class="mr-1"
                  >
                    mdi-lock
                  </v-icon>
                  Paiement securise par Stripe
                </p>
              </div>

              <!-- Stripe Payment Element container -->
              <div
                id="payment-element"
                class="mb-4"
                style="min-height: 200px"
              >
                <div
                  v-if="loadingStripe"
                  class="d-flex align-center justify-center py-8"
                >
                  <v-progress-circular
                    indeterminate
                    color="primary"
                    size="40"
                    class="mr-3"
                  />
                  <span class="text-body-2 text-medium-emphasis">Chargement du formulaire de paiement...</span>
                </div>
              </div>

              <!-- Error message -->
              <v-alert
                v-if="errorMessage"
                type="error"
                variant="tonal"
                density="compact"
                class="mb-4"
                closable
                @click:close="errorMessage = ''"
              >
                {{ errorMessage }}
              </v-alert>

              <v-btn
                color="primary"
                block
                size="large"
                rounded="lg"
                class="mt-2 text-none"
                :loading="processing"
                @click="handlePayment"
              >
                <v-icon start>
                  mdi-credit-card-check
                </v-icon>
                Payer {{ planPrice }}&euro; / mois
              </v-btn>

              <p class="text-caption text-medium-emphasis text-center mt-3">
                En validant, vous acceptez les conditions generales d'utilisation.
                Votre abonnement sera renouvele automatiquement chaque mois.
              </p>
            </div>

            <!-- Step 3: Success -->
            <div
              v-if="paymentStep === 3"
              class="text-center py-4"
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
                Paiement confirme !
              </h2>
              <p class="text-body-1 text-medium-emphasis mt-2">
                Votre abonnement <strong class="text-capitalize">{{ selectedPlan }}</strong> est maintenant actif.
              </p>

              <v-btn
                color="primary"
                size="large"
                rounded="lg"
                class="mt-6 text-none"
                to="/abonnement?success=1"
              >
                <v-icon start>
                  mdi-view-dashboard
                </v-icon>
                Voir mon abonnement
              </v-btn>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Right: Order summary -->
      <v-col
        cols="12"
        md="5"
      >
        <v-card
          class="checkout-card"
          rounded="lg"
          :style="{ animationDelay: '160ms' }"
        >
          <v-card-text class="pa-6">
            <h3 class="text-subtitle-1 font-weight-bold mb-4">
              Recapitulatif
            </h3>

            <div class="d-flex align-center mb-4">
              <v-avatar
                :color="selectedPlan === 'premium' ? 'warning' : 'primary'"
                variant="tonal"
                size="48"
                rounded="lg"
                class="mr-3"
              >
                <v-icon size="26">
                  {{ selectedPlan === 'premium' ? 'mdi-star-outline' : 'mdi-rocket-launch-outline' }}
                </v-icon>
              </v-avatar>
              <div>
                <div class="text-subtitle-1 font-weight-bold text-capitalize">
                  Plan {{ selectedPlan }}
                </div>
                <div class="text-caption text-medium-emphasis">
                  Abonnement mensuel
                </div>
              </div>
            </div>

            <v-divider class="mb-4" />

            <div class="text-body-2 mb-4">
              <div
                v-for="feat in planFeatures"
                :key="feat"
                class="d-flex align-center mb-2"
              >
                <v-icon
                  size="16"
                  color="success"
                  class="mr-2"
                >
                  mdi-check-circle
                </v-icon>
                {{ feat }}
              </div>
            </div>

            <v-divider class="mb-4" />

            <div class="d-flex align-center justify-space-between mb-2">
              <span class="text-body-2 text-medium-emphasis">Abonnement mensuel</span>
              <span class="text-body-2 font-weight-bold">{{ planPrice }}&euro;</span>
            </div>
            <v-divider class="my-3" />
            <div class="d-flex align-center justify-space-between">
              <span class="text-subtitle-1 font-weight-bold">Total aujourd'hui</span>
              <span class="text-h5 font-weight-black text-primary">{{ planPrice }}&euro;</span>
            </div>

            <v-alert
              type="info"
              variant="tonal"
              density="compact"
              class="mt-4"
            >
              <div class="text-caption">
                <v-icon
                  size="14"
                  class="mr-1"
                >
                  mdi-shield-check
                </v-icon>
                Annulez a tout moment depuis votre espace.
              </div>
            </v-alert>
          </v-card-text>
        </v-card>

        <!-- Badges de confiance -->
        <div
          class="d-flex align-center justify-center ga-4 mt-4 checkout-card"
          :style="{ animationDelay: '240ms' }"
        >
          <div class="text-center">
            <v-icon
              size="24"
              color="grey"
            >
              mdi-lock
            </v-icon>
            <p class="text-caption text-medium-emphasis">
              SSL securise
            </p>
          </div>
          <div class="text-center">
            <v-icon
              size="24"
              color="grey"
            >
              mdi-credit-card-check
            </v-icon>
            <p class="text-caption text-medium-emphasis">
              Stripe
            </p>
          </div>
          <div class="text-center">
            <v-icon
              size="24"
              color="grey"
            >
              mdi-undo
            </v-icon>
            <p class="text-caption text-medium-emphasis">
              Annulation libre
            </p>
          </div>
        </div>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { loadStripe } from '@stripe/stripe-js'
import { useSubscriptionStore } from '../../stores/subscription'
import { useUiStore } from '../../stores/ui'

const route = useRoute()
const router = useRouter()
const subscriptionStore = useSubscriptionStore()
const uiStore = useUiStore()

const selectedPlan = computed(() => route.params.plan || 'standard')
const planPrice = computed(() => selectedPlan.value === 'premium' ? 99 : 49)
const planFeatures = computed(() => {
  if (selectedPlan.value === 'premium') {
    return [
      'Garages illimites',
      'Utilisateurs illimites',
      'Tout du plan Standard',
      'API personnalisee',
      'Account manager dedie',
    ]
  }
  return [
    'Jusqu\'a 3 garages',
    '15 utilisateurs',
    'Synchronisation Trackdechets',
    'Score conformite ICPE',
    'Support prioritaire',
  ]
})

const paymentStep = ref(2)
const loadingStripe = ref(true)
const processing = ref(false)
const errorMessage = ref('')

let stripe = null
let elements = null
let stripeSubscriptionId = null
let intentType = null // 'payment_intent' or 'setup_intent'

onMounted(async () => {
  if (!['standard', 'premium'].includes(selectedPlan.value)) {
    router.push({ name: 'abonnement' })
    return
  }

  await subscriptionStore.fetchStatus()
  if (subscriptionStore.subscribed) {
    router.push({ name: 'abonnement' })
    return
  }

  await initializePayment()
})

async function initializePayment() {
  loadingStripe.value = true
  errorMessage.value = ''

  try {
    // 1. Get Stripe public key
    const publicKey = await subscriptionStore.getConfig()
    if (!publicKey) {
      throw new Error('Configuration Stripe non disponible.')
    }

    // 2. Create an incomplete Stripe subscription → returns client_secret (PaymentIntent or SetupIntent)
    const result = await subscriptionStore.createSubscription(selectedPlan.value)
    stripeSubscriptionId = result.subscription_id
    intentType = result.type // 'payment_intent' or 'setup_intent'
    const clientSecret = result.client_secret

    // 3. Initialize Stripe
    stripe = await loadStripe(publicKey)
    if (!stripe) {
      throw new Error('Impossible de charger Stripe.')
    }

    // 4. Create Elements with the client_secret (works for both PaymentIntent and SetupIntent)
    const elementsOptions = {
      clientSecret,
      appearance: {
        theme: 'stripe',
        variables: {
          colorPrimary: '#2E7D32',
          colorBackground: '#ffffff',
          colorText: '#1f2937',
          colorDanger: '#ef4444',
          fontFamily: 'system-ui, -apple-system, sans-serif',
          spacingUnit: '4px',
          borderRadius: '8px',
        },
      },
      locale: 'fr',
    }
    elements = stripe.elements(elementsOptions)

    // 5. Mount Payment Element
    await nextTick()
    const paymentElement = elements.create('payment')
    paymentElement.mount('#payment-element')

    paymentElement.on('ready', () => {
      loadingStripe.value = false
    })

    setTimeout(() => { loadingStripe.value = false }, 3000)

    paymentElement.on('change', (event) => {
      if (event.error) {
        errorMessage.value = event.error.message
      } else {
        errorMessage.value = ''
      }
    })
  } catch (e) {
    errorMessage.value = e.response?.data?.message || e.message || 'Erreur lors de l\'initialisation du paiement.'
    loadingStripe.value = false
  }
}

async function handlePayment() {
  if (!stripe || !elements) {
    errorMessage.value = 'Le formulaire de paiement n\'est pas pret.'
    return
  }

  processing.value = true
  errorMessage.value = ''

  try {
    // Submit the payment element form (validates fields)
    const { error: submitError } = await elements.submit()
    if (submitError) {
      errorMessage.value = submitError.message || 'Erreur de validation.'
      processing.value = false
      return
    }

    // Confirm the intent (PaymentIntent or SetupIntent depending on what Stripe created)
    let confirmError, confirmed

    if (intentType === 'setup_intent') {
      const result = await stripe.confirmSetup({
        elements,
        confirmParams: {
          return_url: window.location.origin + '/abonnement?success=1',
        },
        redirect: 'if_required',
      })
      confirmError = result.error
      confirmed = result.setupIntent && result.setupIntent.status === 'succeeded'
    } else {
      const result = await stripe.confirmPayment({
        elements,
        confirmParams: {
          return_url: window.location.origin + '/abonnement?success=1',
        },
        redirect: 'if_required',
      })
      confirmError = result.error
      confirmed = result.paymentIntent && (result.paymentIntent.status === 'succeeded' || result.paymentIntent.status === 'processing')
    }

    if (confirmError) {
      errorMessage.value = confirmError.message || 'Erreur lors du paiement.'
      processing.value = false
      return
    }

    if (confirmed) {
      // Payment/Setup confirmed — sync subscription on backend
      try {
        await subscriptionStore.confirmSubscription(stripeSubscriptionId, selectedPlan.value)
      } catch {
        // Webhook will handle it if this fails
      }
      paymentStep.value = 3
    } else {
      errorMessage.value = 'Le paiement n\'a pas pu etre confirme.'
    }
  } catch (e) {
    errorMessage.value = e.message || 'Une erreur inattendue est survenue.'
  } finally {
    processing.value = false
  }
}
</script>

<style scoped>
.checkout-card {
  animation: fadeInUp 0.4s ease-out both;
}

.step-line {
  width: 50px;
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
