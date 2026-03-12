<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center mb-1">
      <div>
        <h1 class="text-h5 font-weight-bold">
          Abonnement
        </h1>
        <p class="text-body-2 text-medium-emphasis">
          Gerez votre abonnement GreenPilot
        </p>
      </div>
      <v-spacer />
      <v-chip
        v-if="subscriptionStore.status"
        :color="statusColor"
        variant="tonal"
        size="large"
        :prepend-icon="statusIcon"
      >
        {{ statusLabel }}
      </v-chip>
    </div>

    <!-- Success / Canceled alerts -->
    <v-alert
      v-if="$route.query.success"
      type="success"
      variant="tonal"
      class="mt-4"
      density="compact"
      closable
      prominent
    >
      <template #prepend>
        <v-avatar
          color="success"
          variant="tonal"
          size="40"
          rounded="lg"
        >
          <v-icon size="22">
            mdi-check-circle
          </v-icon>
        </v-avatar>
      </template>
      <div class="text-subtitle-2 font-weight-bold">
        Paiement confirme
      </div>
      <div class="text-caption">
        Votre abonnement a ete active avec succes !
      </div>
    </v-alert>
    <v-alert
      v-if="$route.query.canceled"
      type="warning"
      variant="tonal"
      class="mt-4"
      density="compact"
      closable
      prominent
    >
      <template #prepend>
        <v-avatar
          color="warning"
          variant="tonal"
          size="40"
          rounded="lg"
        >
          <v-icon size="22">
            mdi-close-circle
          </v-icon>
        </v-avatar>
      </template>
      <div class="text-subtitle-2 font-weight-bold">
        Paiement annule
      </div>
      <div class="text-caption">
        Le paiement n'a pas abouti. Vous pouvez reessayer a tout moment.
      </div>
    </v-alert>

    <!-- Loading skeleton -->
    <template v-if="subscriptionStore.loading">
      <v-row class="mt-4">
        <v-col
          v-for="n in 2"
          :key="n"
          cols="12"
          sm="6"
        >
          <v-skeleton-loader type="card" />
        </v-col>
      </v-row>
    </template>

    <template v-else-if="subscriptionStore.status">
      <!-- Active subscription details -->
      <v-card
        v-if="subscriptionStore.subscribed"
        class="mt-4 sub-card"
        :style="{ animationDelay: '80ms' }"
      >
        <v-card-text class="pa-5">
          <div class="d-flex align-center mb-4">
            <v-avatar
              color="success"
              variant="tonal"
              size="52"
              rounded="lg"
              class="mr-4"
            >
              <v-icon size="28">
                mdi-check-decagram
              </v-icon>
            </v-avatar>
            <div class="flex-grow-1">
              <div class="text-h6 font-weight-bold text-success">
                Abonnement actif
              </div>
              <div class="text-body-2 text-medium-emphasis">
                Plan <strong class="text-capitalize">{{ subscriptionStore.plan }}</strong> — Acces complet a toutes les fonctionnalites
              </div>
            </div>
            <v-chip
              color="success"
              variant="tonal"
              size="large"
              class="d-none d-sm-flex"
            >
              <span class="text-h6 font-weight-black mr-1">{{ subscriptionStore.plan === 'premium' ? 'Sur devis' : '49' }}</span>
              <span v-if="subscriptionStore.plan !== 'premium'" class="text-caption">&euro;/mois</span>
            </v-chip>
          </div>

          <v-divider class="mb-4" />

          <v-row dense>
            <v-col
              cols="12"
              sm="4"
            >
              <div class="d-flex align-center">
                <v-avatar
                  color="primary"
                  variant="tonal"
                  size="36"
                  rounded="lg"
                  class="mr-3"
                >
                  <v-icon size="18">
                    mdi-calendar-check
                  </v-icon>
                </v-avatar>
                <div>
                  <div class="text-caption text-medium-emphasis">
                    Debut de periode
                  </div>
                  <div class="text-body-2 font-weight-bold">
                    {{ formatDate(subscriptionStore.subscription?.current_period_start) }}
                  </div>
                </div>
              </div>
            </v-col>
            <v-col
              cols="12"
              sm="4"
            >
              <div class="d-flex align-center">
                <v-avatar
                  color="info"
                  variant="tonal"
                  size="36"
                  rounded="lg"
                  class="mr-3"
                >
                  <v-icon size="18">
                    mdi-calendar-clock
                  </v-icon>
                </v-avatar>
                <div>
                  <div class="text-caption text-medium-emphasis">
                    Prochain renouvellement
                  </div>
                  <div class="text-body-2 font-weight-bold">
                    {{ formatDate(subscriptionStore.subscription?.current_period_end) }}
                  </div>
                </div>
              </div>
            </v-col>
            <v-col
              cols="12"
              sm="4"
            >
              <div class="d-flex align-center">
                <v-avatar
                  color="grey"
                  variant="tonal"
                  size="36"
                  rounded="lg"
                  class="mr-3"
                >
                  <v-icon size="18">
                    mdi-credit-card-outline
                  </v-icon>
                </v-avatar>
                <div>
                  <div class="text-caption text-medium-emphasis">
                    Moyen de paiement
                  </div>
                  <div class="text-body-2 font-weight-bold">
                    <span v-if="subscriptionStore.pmLastFour">
                      {{ cardBrand }} **** {{ subscriptionStore.pmLastFour }}
                    </span>
                    <span
                      v-else
                      class="text-medium-emphasis"
                    >-</span>
                  </div>
                </div>
              </div>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <!-- Trial / ReadOnly banner -->
      <v-alert
        v-if="subscriptionStore.onTrial"
        type="info"
        variant="tonal"
        prominent
        class="mt-4 sub-card"
        :style="{ animationDelay: '80ms' }"
      >
        <template #prepend>
          <v-avatar
            color="info"
            variant="tonal"
            size="44"
            rounded="lg"
          >
            <v-icon size="22">
              mdi-clock-outline
            </v-icon>
          </v-avatar>
        </template>
        <div class="d-flex align-center">
          <div>
            <div class="text-subtitle-2 font-weight-bold">
              Essai gratuit — {{ subscriptionStore.trialDaysRemaining }} jour{{ subscriptionStore.trialDaysRemaining > 1 ? 's' : '' }} restant{{ subscriptionStore.trialDaysRemaining > 1 ? 's' : '' }}
            </div>
            <div class="text-caption">
              Expire le {{ formatDate(subscriptionStore.status.trial_ends_at) }}. Souscrivez pour continuer a utiliser GreenPilot.
            </div>
          </div>
          <v-spacer />
          <v-progress-circular
            :model-value="trialProgress"
            :size="48"
            :width="5"
            color="info"
            class="d-none d-sm-flex"
          >
            <span class="text-caption font-weight-bold">{{ subscriptionStore.trialDaysRemaining }}j</span>
          </v-progress-circular>
        </div>
      </v-alert>

      <v-alert
        v-if="subscriptionStore.isReadOnly"
        type="error"
        variant="tonal"
        prominent
        class="mt-4 sub-card"
        :style="{ animationDelay: '80ms' }"
      >
        <template #prepend>
          <v-avatar
            color="error"
            variant="tonal"
            size="44"
            rounded="lg"
          >
            <v-icon size="22">
              mdi-lock-outline
            </v-icon>
          </v-avatar>
        </template>
        <div>
          <div class="text-subtitle-2 font-weight-bold">
            Compte en lecture seule
          </div>
          <div class="text-caption">
            Votre essai a expire. Souscrivez un abonnement pour retrouver un acces complet.
          </div>
        </div>
      </v-alert>

      <!-- Plan cards -->
      <v-row class="mt-4">
        <!-- Standard -->
        <v-col
          cols="12"
          sm="6"
        >
          <v-card
            :class="['plan-card sub-card', { 'plan-current': subscriptionStore.plan === 'standard' && subscriptionStore.subscribed }]"
            :variant="subscriptionStore.plan === 'standard' && subscriptionStore.subscribed ? 'flat' : 'outlined'"
            rounded="lg"
            :style="{ animationDelay: '160ms' }"
          >
            <v-chip
              v-if="subscriptionStore.plan === 'standard' && subscriptionStore.subscribed"
              color="white"
              size="x-small"
              variant="flat"
              class="current-badge"
            >
              Plan actuel
            </v-chip>

            <v-card-text class="pa-5">
              <div class="d-flex align-center mb-3">
                <v-avatar
                  color="primary"
                  variant="tonal"
                  size="44"
                  rounded="lg"
                  class="plan-avatar mr-3"
                >
                  <v-icon size="24">
                    mdi-rocket-launch-outline
                  </v-icon>
                </v-avatar>
                <div>
                  <div class="text-subtitle-1 font-weight-bold">
                    Standard
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    Pour les garages independants
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <span class="text-h3 font-weight-black">49</span>
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

              <!-- Actions -->
              <div class="mt-4">
                <v-btn
                  v-if="!subscriptionStore.subscribed"
                  color="primary"
                  block
                  size="large"
                  rounded="lg"
                  class="text-none"
                  :to="{ name: 'checkout', params: { plan: 'standard' } }"
                >
                  <v-icon start>
                    mdi-lightning-bolt
                  </v-icon>
                  Souscrire Standard
                </v-btn>
                <v-btn
                  v-else-if="subscriptionStore.plan === 'premium'"
                  variant="tonal"
                  block
                  size="large"
                  rounded="lg"
                  class="text-none"
                  disabled
                >
                  Passer en Standard
                </v-btn>
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
            :class="['plan-card sub-card', { 'plan-current': subscriptionStore.plan === 'premium' && subscriptionStore.subscribed }]"
            :variant="subscriptionStore.plan === 'premium' && subscriptionStore.subscribed ? 'flat' : 'outlined'"
            rounded="lg"
            :style="{ animationDelay: '240ms' }"
          >
            <v-chip
              v-if="subscriptionStore.plan === 'premium' && subscriptionStore.subscribed"
              color="white"
              size="x-small"
              variant="flat"
              class="current-badge"
            >
              Plan actuel
            </v-chip>
            <v-chip
              v-else-if="!(subscriptionStore.plan === 'standard' && subscriptionStore.subscribed)"
              color="grey"
              size="x-small"
              variant="flat"
              class="popular-badge"
            >
              Sur devis
            </v-chip>

            <v-card-text class="pa-5">
              <div class="d-flex align-center mb-3">
                <v-avatar
                  color="warning"
                  variant="tonal"
                  size="44"
                  rounded="lg"
                  class="plan-avatar mr-3"
                >
                  <v-icon size="24">
                    mdi-star-outline
                  </v-icon>
                </v-avatar>
                <div>
                  <div class="text-subtitle-1 font-weight-bold">
                    Premium
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    Pour les reseaux multi-sites
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <span class="text-h5 font-weight-black">Sur devis</span>
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

              <!-- Actions -->
              <div class="mt-4">
                <v-btn
                  v-if="!subscriptionStore.subscribed"
                  color="grey"
                  block
                  size="large"
                  rounded="lg"
                  class="text-none"
                  disabled
                >
                  <v-icon start>
                    mdi-email-outline
                  </v-icon>
                  Demander un devis
                </v-btn>
                <v-btn
                  v-else-if="subscriptionStore.plan === 'standard'"
                  color="grey"
                  variant="tonal"
                  block
                  size="large"
                  rounded="lg"
                  class="text-none"
                  disabled
                >
                  <v-icon start>
                    mdi-email-outline
                  </v-icon>
                  Demander un devis
                </v-btn>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Contact message -->
      <v-alert
        type="info"
        variant="tonal"
        prominent
        class="mt-4 sub-card"
        :style="{ animationDelay: '280ms' }"
      >
        <template #prepend>
          <v-avatar
            color="info"
            variant="tonal"
            size="44"
            rounded="lg"
          >
            <v-icon size="22">
              mdi-email-outline
            </v-icon>
          </v-avatar>
        </template>
        <div>
          <div class="text-subtitle-2 font-weight-bold">
            Interessé par un abonnement ?
          </div>
          <div class="text-body-2 mt-1">
            Contactez notre equipe pour plus d'informations sur les offres et obtenir un accompagnement personnalise.
          </div>
          <div class="mt-2">
            <v-btn
              variant="flat"
              color="primary"
              size="small"
              rounded="lg"
              class="text-none mr-2"
              href="mailto:contact@greenpilot.fr"
            >
              <v-icon
                start
                size="16"
              >
                mdi-email
              </v-icon>
              contact@greenpilot.fr
            </v-btn>
          </div>
        </div>
      </v-alert>

      <!-- Moyens de paiement -->
      <v-card
        v-if="subscriptionStore.subscribed"
        class="mt-4 sub-card"
        :style="{ animationDelay: '320ms' }"
      >
        <v-card-text class="pa-5">
          <div class="d-flex align-center mb-4">
            <v-avatar
              color="primary"
              variant="tonal"
              size="44"
              rounded="lg"
              class="mr-3"
            >
              <v-icon size="22">
                mdi-credit-card-outline
              </v-icon>
            </v-avatar>
            <div class="flex-grow-1">
              <div class="text-subtitle-1 font-weight-bold">
                Moyens de paiement
              </div>
              <div class="text-caption text-medium-emphasis">
                Cartes enregistrees sur votre compte
              </div>
            </div>
            <v-btn
              variant="outlined"
              color="primary"
              size="small"
              rounded="lg"
              class="text-none"
              :loading="portalLoading"
              @click="handlePortal"
            >
              <v-icon
                start
                size="16"
              >
                mdi-pencil
              </v-icon>
              Gerer
            </v-btn>
          </div>

          <v-skeleton-loader
            v-if="paymentMethodsLoading"
            type="list-item-two-line"
          />

          <template v-else-if="paymentMethods.length">
            <div
              v-for="pm in paymentMethods"
              :key="pm.id"
              class="d-flex align-center pa-3 rounded-lg mb-2"
              :style="{ background: pm.is_default ? 'rgba(46,125,50,0.06)' : 'rgba(0,0,0,0.02)' }"
            >
              <v-avatar
                :color="pm.is_default ? 'success' : 'grey'"
                variant="tonal"
                size="40"
                rounded="lg"
                class="mr-3"
              >
                <v-icon size="20">
                  {{ cardIcon(pm.brand) }}
                </v-icon>
              </v-avatar>
              <div class="flex-grow-1">
                <div class="text-body-2 font-weight-bold">
                  {{ cardBrandName(pm.brand) }} **** {{ pm.last4 }}
                </div>
                <div class="text-caption text-medium-emphasis">
                  Expire {{ String(pm.exp_month).padStart(2, '0') }}/{{ pm.exp_year }}
                </div>
              </div>
              <v-chip
                v-if="pm.is_default"
                color="success"
                variant="tonal"
                size="small"
              >
                Par defaut
              </v-chip>
            </div>
          </template>

          <div
            v-else
            class="text-center text-medium-emphasis text-body-2 py-4"
          >
            Aucun moyen de paiement enregistre
          </div>
        </v-card-text>
      </v-card>

      <!-- Factures -->
      <v-card
        v-if="subscriptionStore.subscribed"
        class="mt-4 sub-card"
        :style="{ animationDelay: '400ms' }"
      >
        <v-card-text class="pa-5">
          <div class="d-flex align-center mb-4">
            <v-avatar
              color="info"
              variant="tonal"
              size="44"
              rounded="lg"
              class="mr-3"
            >
              <v-icon size="22">
                mdi-receipt-text-outline
              </v-icon>
            </v-avatar>
            <div>
              <div class="text-subtitle-1 font-weight-bold">
                Historique des factures
              </div>
              <div class="text-caption text-medium-emphasis">
                Toutes vos factures d'abonnement
              </div>
            </div>
          </div>

          <v-skeleton-loader
            v-if="invoicesLoading"
            type="table-row@3"
          />

          <v-table
            v-else-if="invoices.length"
            density="comfortable"
            class="rounded-lg"
          >
            <thead>
              <tr>
                <th>Date</th>
                <th>Numero</th>
                <th>Montant</th>
                <th>Statut</th>
                <th class="text-right">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="inv in invoices"
                :key="inv.id"
              >
                <td class="text-body-2">
                  {{ formatDate(inv.date) }}
                </td>
                <td class="text-body-2 text-medium-emphasis">
                  {{ inv.number || '-' }}
                </td>
                <td class="text-body-2 font-weight-bold">
                  {{ inv.total }}
                </td>
                <td>
                  <v-chip
                    :color="invoiceStatusColor(inv.status)"
                    variant="tonal"
                    size="small"
                  >
                    {{ invoiceStatusLabel(inv.status) }}
                  </v-chip>
                </td>
                <td class="text-right">
                  <v-btn
                    icon="mdi-file-pdf-box"
                    variant="text"
                    size="small"
                    color="primary"
                    :loading="downloadingInvoice === inv.id"
                    title="Telecharger PDF"
                    @click="handleDownloadInvoice(inv)"
                  />
                  <v-btn
                    v-if="inv.hosted_invoice_url"
                    icon="mdi-open-in-new"
                    variant="text"
                    size="small"
                    color="grey"
                    :href="inv.hosted_invoice_url"
                    target="_blank"
                    title="Voir sur Stripe"
                  />
                </td>
              </tr>
            </tbody>
          </v-table>

          <div
            v-else
            class="text-center text-medium-emphasis text-body-2 py-4"
          >
            Aucune facture disponible
          </div>
        </v-card-text>
      </v-card>
    </template>

    <!-- Preview / Confirmation Dialog -->
    <v-dialog
      v-model="previewDialog"
      max-width="520"
      :fullscreen="mobile"
      persistent
    >
      <v-card rounded="lg">
        <v-card-title class="d-flex align-center pa-5 pb-3">
          <v-avatar
            :color="previewData?.direction === 'upgrade' ? 'success' : 'warning'"
            variant="tonal"
            size="44"
            rounded="lg"
            class="mr-3"
          >
            <v-icon size="24">
              {{ previewData?.direction === 'upgrade' ? 'mdi-arrow-up-bold-circle' : 'mdi-arrow-down-bold-circle' }}
            </v-icon>
          </v-avatar>
          <div>
            <div class="text-subtitle-1 font-weight-bold">
              {{ previewData?.direction === 'upgrade' ? 'Upgrade' : 'Downgrade' }} de plan
            </div>
            <div class="text-caption text-medium-emphasis">
              {{ subscriptionStore.plan === 'standard' ? 'Standard' : 'Premium' }} → {{ previewData?.new_plan === 'premium' ? 'Premium' : 'Standard' }}
            </div>
          </div>
        </v-card-title>

        <v-card-text class="px-5 pb-2">
          <!-- New price & next billing date -->
          <div
            class="rounded-lg pa-3 mb-3"
            style="background: rgba(0,0,0,0.03)"
          >
            <div class="d-flex align-center justify-space-between mb-1">
              <div class="text-body-2 text-medium-emphasis">
                Nouveau prix mensuel
              </div>
              <div class="text-subtitle-2 font-weight-bold">
                {{ previewData?.new_price }}
              </div>
            </div>
            <div class="d-flex align-center justify-space-between">
              <div class="text-body-2 text-medium-emphasis">
                Prochain prelevement
              </div>
              <div class="text-body-2 font-weight-bold">
                {{ formatDate(previewData?.next_billing_date) }}
              </div>
            </div>
          </div>

          <!-- Proration details -->
          <div class="text-subtitle-2 font-weight-bold mb-2">
            Prorata
          </div>
          <div
            class="rounded-lg pa-3 mb-3"
            style="background: rgba(0,0,0,0.03)"
          >
            <div class="d-flex justify-space-between mb-1">
              <span class="text-body-2 text-medium-emphasis">Credit (periode en cours)</span>
              <span class="text-body-2 text-success">{{ previewData?.proration?.credit }}</span>
            </div>
            <div class="d-flex justify-space-between mb-1">
              <span class="text-body-2 text-medium-emphasis">Montant facture</span>
              <span class="text-body-2">{{ previewData?.proration?.charge }}</span>
            </div>
            <v-divider class="my-2" />
            <div class="d-flex justify-space-between">
              <span class="text-body-2 font-weight-bold">Total ajuste</span>
              <span class="text-body-2 font-weight-bold">{{ previewData?.proration?.total }}</span>
            </div>
          </div>

          <!-- New limits -->
          <div class="text-subtitle-2 font-weight-bold mb-2">
            Nouvelles limites
          </div>
          <div class="d-flex ga-3 mb-2">
            <v-chip
              variant="tonal"
              :color="previewData?.direction === 'upgrade' ? 'success' : 'warning'"
              size="small"
            >
              <v-icon
                start
                size="14"
              >
                mdi-garage
              </v-icon>
              {{ previewData?.new_limits?.max_garages === 999 ? 'Illimites' : previewData?.new_limits?.max_garages }} garages
            </v-chip>
            <v-chip
              variant="tonal"
              :color="previewData?.direction === 'upgrade' ? 'success' : 'warning'"
              size="small"
            >
              <v-icon
                start
                size="14"
              >
                mdi-account-group
              </v-icon>
              {{ previewData?.new_limits?.max_users === 999 ? 'Illimites' : previewData?.new_limits?.max_users }} utilisateurs
            </v-chip>
          </div>
        </v-card-text>

        <v-card-actions class="pa-5 pt-2">
          <v-btn
            variant="text"
            class="text-none"
            @click="previewDialog = false"
          >
            Annuler
          </v-btn>
          <v-spacer />
          <v-btn
            :color="previewData?.direction === 'upgrade' ? 'success' : 'warning'"
            variant="flat"
            class="text-none"
            rounded="lg"
            :loading="changePlanLoading"
            @click="confirmChangePlan"
          >
            <v-icon start>
              mdi-check
            </v-icon>
            Confirmer le changement
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Limits exceeded error Dialog -->
    <v-dialog
      v-model="limitsErrorDialog"
      max-width="480"
      :fullscreen="mobile"
    >
      <v-card rounded="lg">
        <v-card-title class="d-flex align-center pa-5 pb-3">
          <v-avatar
            color="error"
            variant="tonal"
            size="44"
            rounded="lg"
            class="mr-3"
          >
            <v-icon size="24">
              mdi-alert-circle
            </v-icon>
          </v-avatar>
          <div>
            <div class="text-subtitle-1 font-weight-bold">
              Changement impossible
            </div>
            <div class="text-caption text-medium-emphasis">
              Limites du plan Standard depassees
            </div>
          </div>
        </v-card-title>

        <v-card-text class="px-5 pb-2">
          <v-alert
            type="error"
            variant="tonal"
            density="compact"
            class="mb-3"
          >
            Votre utilisation actuelle depasse les limites du plan Standard. Veuillez reduire votre utilisation avant de changer de plan.
          </v-alert>

          <div
            class="rounded-lg pa-3"
            style="background: rgba(0,0,0,0.03)"
          >
            <div
              v-if="limitsErrorData"
              class="d-flex flex-column ga-2"
            >
              <div class="d-flex justify-space-between align-center">
                <span class="text-body-2">
                  <v-icon
                    size="16"
                    class="mr-1"
                  >mdi-garage</v-icon>
                  Garages
                </span>
                <span class="text-body-2">
                  <strong :class="limitsErrorData.current_garages > limitsErrorData.max_garages ? 'text-error' : ''">
                    {{ limitsErrorData.current_garages }}
                  </strong>
                  / {{ limitsErrorData.max_garages }} max
                </span>
              </div>
              <div class="d-flex justify-space-between align-center">
                <span class="text-body-2">
                  <v-icon
                    size="16"
                    class="mr-1"
                  >mdi-account-group</v-icon>
                  Utilisateurs
                </span>
                <span class="text-body-2">
                  <strong :class="limitsErrorData.current_users > limitsErrorData.max_users ? 'text-error' : ''">
                    {{ limitsErrorData.current_users }}
                  </strong>
                  / {{ limitsErrorData.max_users }} max
                </span>
              </div>
            </div>
          </div>
        </v-card-text>

        <v-card-actions class="pa-5 pt-3">
          <v-spacer />
          <v-btn
            variant="flat"
            color="primary"
            class="text-none"
            rounded="lg"
            @click="limitsErrorDialog = false"
          >
            Compris
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useDisplay } from 'vuetify'
import { useSubscriptionStore } from '../../stores/subscription'
import { useUiStore } from '../../stores/ui'
import api from '../../services/api'

const { mobile } = useDisplay()
const subscriptionStore = useSubscriptionStore()
const uiStore = useUiStore()

const portalLoading = ref(false)
const changePlanLoading = ref(false)
const previewLoading = ref(false)
const previewDialog = ref(false)
const previewData = ref(null)
const limitsErrorDialog = ref(false)
const limitsErrorData = ref(null)
const targetPlan = ref(null)
const invoices = ref([])
const invoicesLoading = ref(false)
const paymentMethods = ref([])
const paymentMethodsLoading = ref(false)
const downloadingInvoice = ref(null)

const statusColor = computed(() => {
  if (subscriptionStore.subscribed) return 'success'
  if (subscriptionStore.onTrial) return 'info'
  return 'error'
})

const statusIcon = computed(() => {
  if (subscriptionStore.subscribed) return 'mdi-check-decagram'
  if (subscriptionStore.onTrial) return 'mdi-clock-outline'
  return 'mdi-alert-circle-outline'
})

const statusLabel = computed(() => {
  if (subscriptionStore.subscribed) return 'Abonne'
  if (subscriptionStore.onTrial) return 'Essai gratuit'
  return 'Expire'
})

const trialProgress = computed(() => {
  const total = 14
  const remaining = subscriptionStore.trialDaysRemaining
  return Math.round((remaining / total) * 100)
})

const cardBrand = computed(() => {
  const type = subscriptionStore.pmType
  if (!type) return ''
  return cardBrandName(type)
})

function cardBrandName(brand) {
  if (!brand) return ''
  const brands = { visa: 'Visa', mastercard: 'Mastercard', amex: 'Amex', discover: 'Discover' }
  return brands[brand] || brand
}

function cardIcon(brand) {
  const icons = { visa: 'mdi-credit-card', mastercard: 'mdi-credit-card', amex: 'mdi-credit-card', discover: 'mdi-credit-card' }
  return icons[brand] || 'mdi-credit-card-outline'
}

function invoiceStatusColor(status) {
  const colors = { paid: 'success', open: 'warning', draft: 'grey', uncollectible: 'error', void: 'grey' }
  return colors[status] || 'grey'
}

function invoiceStatusLabel(status) {
  const labels = { paid: 'Payee', open: 'En attente', draft: 'Brouillon', uncollectible: 'Irrecuperable', void: 'Annulee' }
  return labels[status] || status
}

const standardFeatures = [
  '1 garage',
  '5 utilisateurs',
  'Synchronisation Trackdechets',
  'Score conformite ICPE',
  'Support prioritaire',
]

const premiumFeatures = [
  'Garages illimites',
  'Utilisateurs illimites',
  'Tout du plan Standard',
  'API personnalisee',
  'Account manager dedie',
]

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })
}

async function handlePortal() {
  portalLoading.value = true
  try {
    await subscriptionStore.openPortal()
  } catch (e) {
    uiStore.showError(e.response?.data?.message || 'Erreur')
    portalLoading.value = false
  }
}

async function handleChangePlan(plan) {
  previewLoading.value = true
  changePlanLoading.value = true
  targetPlan.value = plan
  try {
    const data = await subscriptionStore.previewChange(plan)
    previewData.value = data
    previewDialog.value = true
  } catch (e) {
    const errData = e.response?.data
    if (e.response?.status === 422 && errData?.limits_exceeded) {
      limitsErrorData.value = errData
      limitsErrorDialog.value = true
    } else {
      uiStore.showError(errData?.message || 'Erreur')
    }
  } finally {
    previewLoading.value = false
    changePlanLoading.value = false
  }
}

async function confirmChangePlan() {
  changePlanLoading.value = true
  try {
    const result = await subscriptionStore.changePlan(targetPlan.value)
    previewDialog.value = false
    uiStore.showSuccess(result.message || 'Plan mis a jour')
  } catch (e) {
    uiStore.showError(e.response?.data?.message || 'Erreur lors du changement de plan')
  } finally {
    changePlanLoading.value = false
  }
}

async function handleDownloadInvoice(inv) {
  downloadingInvoice.value = inv.id
  try {
    const response = await api.get(`/subscription/invoices/${inv.id}/pdf`, {
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.download = `facture-${inv.number || inv.id}.pdf`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch {
    uiStore.showError('Impossible de telecharger la facture.')
  } finally {
    downloadingInvoice.value = null
  }
}

onMounted(async () => {
  await subscriptionStore.fetchStatus()
  if (subscriptionStore.subscribed) {
    loadBillingData()
  }
})

async function loadBillingData() {
  invoicesLoading.value = true
  paymentMethodsLoading.value = true
  try {
    const [inv, pm] = await Promise.all([
      subscriptionStore.fetchInvoices(),
      subscriptionStore.fetchPaymentMethods(),
    ])
    invoices.value = inv
    paymentMethods.value = pm
  } catch {
    // silent
  } finally {
    invoicesLoading.value = false
    paymentMethodsLoading.value = false
  }
}
</script>

<style scoped>
.sub-card {
  animation: fadeInUp 0.4s ease-out both;
}

.plan-card {
  position: relative;
  overflow: visible;
  transition: all 0.2s ease;
  height: 100%;
}

.plan-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.plan-current {
  background-color: #2E7D32 !important;
  border-color: #2E7D32 !important;
  color: #fff !important;
  box-shadow: 0 8px 32px rgba(46, 125, 50, 0.35);
}

.plan-current :deep(.v-card-text) {
  color: #fff !important;
}

.plan-current :deep(.v-avatar) {
  background-color: rgba(255, 255, 255, 0.2) !important;
}

.plan-current :deep(.v-avatar .v-icon) {
  color: #fff !important;
}

.plan-current :deep(.plan-feat-icon) {
  color: #fff !important;
}

.plan-current :deep(.v-divider) {
  border-color: rgba(255, 255, 255, 0.3) !important;
}

.plan-current .plan-features {
  color: rgba(255, 255, 255, 0.9) !important;
}

.plan-current .text-medium-emphasis {
  color: rgba(255, 255, 255, 0.7) !important;
}

.current-badge {
  position: absolute;
  top: -10px;
  right: 12px;
  z-index: 1;
  color: #2E7D32 !important;
  font-weight: 700;
}

.popular-badge {
  position: absolute;
  top: -10px;
  right: 12px;
  z-index: 1;
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
