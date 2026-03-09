<template>
  <v-card
    variant="flat"
    color="red-lighten-5"
    class="bsd-refused-actions"
  >
    <v-card-text>
      <!-- Refused header -->
      <div class="d-flex align-center mb-3">
        <v-icon
          color="red"
          class="mr-2"
        >
          mdi-close-circle
        </v-icon>
        <span class="text-h6 text-red font-weight-bold">BSD Refuse</span>
      </div>

      <!-- Motif de refus -->
      <v-alert
        v-if="bsd.motif_refus"
        type="error"
        variant="tonal"
        density="compact"
        class="mb-4"
        icon="mdi-alert-circle"
      >
        <div class="text-subtitle-2 mb-1">
          Motif du refus
        </div>
        <div class="text-body-2">
          {{ bsd.motif_refus }}
        </div>
      </v-alert>

      <v-alert
        v-if="bsd.date_refus"
        type="info"
        variant="tonal"
        density="compact"
        class="mb-4"
        icon="mdi-calendar"
      >
        Date du refus : {{ formatDateTime(bsd.date_refus) }}
      </v-alert>

      <!-- Contact info sections -->
      <v-row class="mb-4">
        <!-- Collecteur contact -->
        <v-col
          cols="12"
          md="6"
        >
          <v-card
            variant="outlined"
            class="h-100"
          >
            <v-card-subtitle class="font-weight-bold pt-3">
              <v-icon
                start
                size="small"
              >
                mdi-truck
              </v-icon>
              Transporteur / Collecteur
            </v-card-subtitle>
            <v-card-text>
              <div v-if="bsd.transporteur_raison_sociale">
                <div class="text-body-2 font-weight-medium">
                  {{ bsd.transporteur_raison_sociale }}
                </div>
                <div class="text-caption">
                  SIRET: {{ bsd.transporteur_siret }}
                </div>
                <div
                  v-if="bsd.transporteur_adresse"
                  class="text-caption"
                >
                  {{ bsd.transporteur_adresse }}
                </div>
                <div
                  v-if="bsd.collecteur"
                  class="mt-2"
                >
                  <div
                    v-if="bsd.collecteur.telephone"
                    class="text-caption d-flex align-center"
                  >
                    <v-icon
                      size="x-small"
                      class="mr-1"
                    >
                      mdi-phone
                    </v-icon>
                    <a :href="'tel:' + bsd.collecteur.telephone">{{ bsd.collecteur.telephone }}</a>
                  </div>
                  <div
                    v-if="bsd.collecteur.email"
                    class="text-caption d-flex align-center"
                  >
                    <v-icon
                      size="x-small"
                      class="mr-1"
                    >
                      mdi-email
                    </v-icon>
                    <a :href="'mailto:' + bsd.collecteur.email">{{ bsd.collecteur.email }}</a>
                  </div>
                  <div
                    v-if="bsd.collecteur.contact_nom"
                    class="text-caption d-flex align-center"
                  >
                    <v-icon
                      size="x-small"
                      class="mr-1"
                    >
                      mdi-account
                    </v-icon>
                    {{ bsd.collecteur.contact_nom }}
                  </div>
                </div>
              </div>
              <span
                v-else
                class="text-caption text-medium-emphasis"
              >Non renseigne</span>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Destination contact -->
        <v-col
          cols="12"
          md="6"
        >
          <v-card
            variant="outlined"
            class="h-100"
          >
            <v-card-subtitle class="font-weight-bold pt-3">
              <v-icon
                start
                size="small"
              >
                mdi-domain
              </v-icon>
              Destination (site de refus)
            </v-card-subtitle>
            <v-card-text>
              <div v-if="bsd.destination_raison_sociale">
                <div class="text-body-2 font-weight-medium">
                  {{ bsd.destination_raison_sociale }}
                </div>
                <div class="text-caption">
                  SIRET: {{ bsd.destination_siret }}
                </div>
                <div
                  v-if="bsd.destination_adresse"
                  class="text-caption"
                >
                  {{ bsd.destination_adresse }}
                </div>
                <div
                  v-if="bsd.destination_telephone"
                  class="text-caption d-flex align-center mt-1"
                >
                  <v-icon
                    size="x-small"
                    class="mr-1"
                  >
                    mdi-phone
                  </v-icon>
                  <a :href="'tel:' + bsd.destination_telephone">{{ bsd.destination_telephone }}</a>
                </div>
                <div
                  v-if="bsd.destination_email"
                  class="text-caption d-flex align-center"
                >
                  <v-icon
                    size="x-small"
                    class="mr-1"
                  >
                    mdi-email
                  </v-icon>
                  <a :href="'mailto:' + bsd.destination_email">{{ bsd.destination_email }}</a>
                </div>
              </div>
              <span
                v-else
                class="text-caption text-medium-emphasis"
              >Non renseigne</span>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Actions -->
      <v-divider class="mb-4" />

      <div class="text-subtitle-2 mb-2">
        Actions requises
      </div>
      <div class="text-body-2 text-medium-emphasis mb-4">
        Un BSD refuse necessite d'etre retravaille. Dupliquez ce bordereau pour creer un nouveau BSD
        avec les informations corrigees, puis organisez un nouvel enlevement.
      </div>

      <div class="d-flex flex-wrap ga-3">
        <v-btn
          color="primary"
          prepend-icon="mdi-content-copy"
          :loading="duplicating"
          @click="onDupliquer"
        >
          Dupliquer le BSD
        </v-btn>
        <v-btn
          v-if="bsd.collecteur?.telephone"
          variant="outlined"
          prepend-icon="mdi-phone"
          :href="'tel:' + bsd.collecteur.telephone"
        >
          Appeler le collecteur
        </v-btn>
        <v-btn
          v-if="bsd.collecteur?.email"
          variant="outlined"
          prepend-icon="mdi-email"
          :href="'mailto:' + bsd.collecteur.email + '?subject=BSD Refuse - ' + (bsd.numero_bordereau || bsd.id)"
        >
          Envoyer un email
        </v-btn>
      </div>

      <!-- Linked duplicate info -->
      <v-alert
        v-if="bsd.duplicated_bsd_id"
        type="info"
        variant="tonal"
        density="compact"
        class="mt-4"
        icon="mdi-link"
      >
        Un nouveau BSD (#{{ bsd.duplicated_bsd_id }}) a deja ete cree a partir de ce bordereau refuse.
      </v-alert>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  bsd: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['dupliquer'])
const duplicating = ref(false)

function formatDateTime(date) {
  if (!date) return '-'
  return new Date(date).toLocaleString('fr-FR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

async function onDupliquer() {
  duplicating.value = true
  try {
    emit('dupliquer')
  } finally {
    // Parent will handle the loading state
    setTimeout(() => {
      duplicating.value = false
    }, 2000)
  }
}
</script>

<style scoped>
.bsd-refused-actions a {
  color: inherit;
  text-decoration: none;
}

.bsd-refused-actions a:hover {
  text-decoration: underline;
}
</style>
