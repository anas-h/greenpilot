<template>
  <v-dialog
    :model-value="modelValue"
    max-width="700"
    :fullscreen="mobile"
    persistent
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <v-card>
      <v-card-title class="d-flex align-center pa-4">
        <v-icon
          color="success"
          class="mr-2"
        >
          mdi-check-circle
        </v-icon>
        Completion de l'enlevement
        <v-spacer />
        <v-btn
          icon="mdi-close"
          variant="text"
          title="Fermer"
          @click="close"
        />
      </v-card-title>

      <v-divider />

      <v-card-text
        class="pa-4"
        style="max-height: 70vh; overflow-y: auto;"
      >
        <v-alert
          v-if="enlevement"
          type="info"
          variant="tonal"
          density="compact"
          class="mb-4"
        >
          <div class="font-weight-medium">
            {{ enlevement.numero }}
          </div>
          <div class="text-caption">
            Collecteur: {{ enlevement.collecteur?.raison_sociale || '-' }}
            | Date prevue: {{ formatDate(enlevement.date_prevue) }}
          </div>
        </v-alert>

        <v-form
          ref="formRef"
          @submit.prevent="complete"
        >
          <v-row
            dense
            class="mb-4"
          >
            <v-col
              cols="12"
              sm="6"
            >
              <v-text-field
                v-model="form.date_effective"
                label="Date effective *"
                type="date"
                :rules="[rules.required]"
              />
            </v-col>
            <v-col
              cols="12"
              sm="6"
            >
              <v-text-field
                v-model="form.numero_bon_enlevement"
                label="N. du bon d'enlevement"
                placeholder="Numero du bon papier"
              />
            </v-col>
          </v-row>

          <h3 class="text-subtitle-1 font-weight-bold mb-3">
            Quantites effectives
          </h3>

          <div style="overflow-x: auto">
            <v-table density="compact">
              <thead>
                <tr>
                  <th>Type de dechet</th>
                  <th>Conteneur</th>
                  <th style="text-align: right;">
                    Qte prevue
                  </th>
                  <th style="width: 150px;">
                    Qte effective *
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="ligne in lignesCompletion"
                  :key="ligne.ligne_id"
                >
                  <td>
                    <div class="d-flex align-center ga-1">
                      <v-icon
                        v-if="ligne.dangereux"
                        size="small"
                        color="error"
                      >
                        mdi-alert
                      </v-icon>
                      <span>{{ ligne.denomination }}</span>
                    </div>
                  </td>
                  <td>{{ ligne.conteneur_nom }}</td>
                  <td style="text-align: right;">
                    {{ ligne.quantite_prevue }} {{ ligne.unite }}
                  </td>
                  <td>
                    <v-text-field
                      v-model.number="ligne.quantite_effective"
                      type="number"
                      step="0.001"
                      min="0"
                      density="compact"
                      variant="underlined"
                      :suffix="ligne.unite"
                      hide-details
                    />
                  </td>
                </tr>
              </tbody>
            </v-table>
          </div>

          <v-card
            v-if="summary"
            variant="outlined"
            class="mt-4 pa-3"
          >
            <h3 class="text-subtitle-2 font-weight-bold mb-2">
              Estimation des couts
            </h3>
            <div class="d-flex justify-space-between text-body-2 mb-1">
              <span>Cout total estimatif :</span>
              <span class="text-error font-weight-medium">{{ formatMontant(summary.cout) }}</span>
            </div>
            <div class="d-flex justify-space-between text-body-2 mb-1">
              <span>Revenu total (rachats) :</span>
              <span class="text-success font-weight-medium">{{ formatMontant(summary.revenu) }}</span>
            </div>
            <v-divider class="my-2" />
            <div class="d-flex justify-space-between text-body-1 font-weight-bold">
              <span>Solde net :</span>
              <span :class="summary.revenu - summary.cout >= 0 ? 'text-success' : 'text-error'">
                {{ formatMontant(summary.revenu - summary.cout) }}
              </span>
            </div>
          </v-card>
        </v-form>
      </v-card-text>

      <v-divider />

      <v-card-actions class="pa-4">
        <v-spacer />
        <v-btn
          variant="text"
          @click="close"
        >
          Annuler
        </v-btn>
        <v-btn
          color="success"
          variant="flat"
          :loading="completing"
          @click="complete"
        >
          <v-icon start>
            mdi-check
          </v-icon>
          Completer l'enlevement
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useDisplay } from 'vuetify'
import api from '../../../services/api'
import { useUiStore } from '../../../stores/ui'

const { mobile } = useDisplay()

const props = defineProps({
  modelValue: Boolean,
  enlevement: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'completed'])

const uiStore = useUiStore()
const formRef = ref(null)
const completing = ref(false)

const form = ref({
  date_effective: new Date().toISOString().substring(0, 10),
  numero_bon_enlevement: '',
})

const lignesCompletion = ref([])

const rules = {
  required: v => (v !== null && v !== undefined && v !== '') || 'Ce champ est requis.',
}

const summary = computed(() => {
  if (lignesCompletion.value.length === 0) return null
  // This is a rough estimate - the actual computation happens server-side
  let cout = 0
  let revenu = 0
  // We don't have access to tarifs client-side easily, so just show a placeholder
  return { cout, revenu }
})

watch(() => props.enlevement, (val) => {
  if (val) {
    form.value = {
      date_effective: new Date().toISOString().substring(0, 10),
      numero_bon_enlevement: val.numero_bon_enlevement || '',
    }

    lignesCompletion.value = (val.lignes || []).map(l => ({
      ligne_id: l.id,
      type_dechet_id: l.type_dechet_id,
      denomination: l.type_dechet?.denomination || 'Type inconnu',
      dangereux: l.type_dechet?.dangereux || false,
      conteneur_nom: l.conteneur?.nom || '-',
      quantite_prevue: l.quantite_prevue,
      quantite_effective: l.quantite_prevue, // Pre-fill with planned qty
      unite: l.unite,
    }))
  } else {
    lignesCompletion.value = []
  }
}, { immediate: true })

async function complete() {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  completing.value = true
  try {
    const payload = {
      date_effective: form.value.date_effective,
      numero_bon_enlevement: form.value.numero_bon_enlevement || null,
      lignes: lignesCompletion.value.map(l => ({
        ligne_id: l.ligne_id,
        quantite_effective: l.quantite_effective,
      })),
    }

    await api.post(`/enlevements/${props.enlevement.id}/complete`, payload)
    uiStore.showSuccess('Enlevement complete avec succes.')
    emit('completed')
    close()
  } catch (e) {
    const msg = e.response?.data?.message || 'Erreur lors de la completion.'
    uiStore.showError(msg)
  } finally {
    completing.value = false
  }
}

function close() {
  emit('update:modelValue', false)
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR')
}

function formatMontant(montant) {
  if (!montant && montant !== 0) return '-'
  return Number(montant).toFixed(2).replace('.', ',') + ' EUR'
}
</script>
