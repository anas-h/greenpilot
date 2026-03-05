<template>
  <v-dialog
    :model-value="modelValue"
    max-width="800"
    :fullscreen="mobile"
    persistent
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <v-card>
      <v-card-title class="d-flex align-center pa-4">
        <v-icon
          color="primary"
          class="mr-2"
        >
          mdi-truck-delivery
        </v-icon>
        {{ isEdit ? 'Modifier l\'enlevement' : 'Nouvel enlevement' }}
        <v-spacer />
        <v-btn
          icon="mdi-close"
          variant="text"
          title="Fermer"
          @click="tryClose"
        />
      </v-card-title>

      <v-divider />

      <v-card-text
        class="pa-4"
        style="max-height: 70vh; overflow-y: auto;"
      >
        <v-form
          ref="formRef"
          @submit.prevent="save"
        >
          <v-row dense>
            <v-col
              cols="12"
              md="6"
            >
              <v-select
                v-model="form.collecteur_id"
                :items="collecteurs"
                item-title="raison_sociale"
                item-value="id"
                label="Collecteur *"
                :rules="[rules.required]"
              >
                <template #item="{ item, props: itemProps }">
                  <v-list-item v-bind="itemProps">
                    <template #append>
                      <v-icon
                        v-if="item.raw.date_validite_autorisation"
                        :color="isAutorisationValide(item.raw) ? 'success' : 'error'"
                        size="small"
                      >
                        {{ isAutorisationValide(item.raw) ? 'mdi-check-circle' : 'mdi-alert-circle' }}
                      </v-icon>
                    </template>
                  </v-list-item>
                </template>
              </v-select>
            </v-col>

            <v-col
              cols="12"
              md="3"
            >
              <v-text-field
                v-model="form.date_prevue"
                label="Date prevue *"
                type="date"
                :rules="[rules.required]"
              />
            </v-col>

            <v-col
              cols="12"
              md="3"
            >
              <v-select
                v-model="form.recurrence"
                :items="recurrenceOptions"
                label="Recurrence"
                clearable
              />
            </v-col>

            <v-col cols="12">
              <v-textarea
                v-model="form.notes"
                label="Notes"
                rows="2"
                auto-grow
              />
            </v-col>
          </v-row>

          <h3 class="text-subtitle-1 font-weight-bold mt-4 mb-3">
            Lignes de l'enlevement
            <v-btn
              variant="text"
              color="primary"
              size="small"
              prepend-icon="mdi-plus"
              @click="addLigne"
            >
              Ajouter une ligne
            </v-btn>
          </h3>

          <v-alert
            v-if="form.lignes.length === 0"
            type="warning"
            variant="tonal"
            density="compact"
            class="mb-3"
          >
            Au moins une ligne est requise.
          </v-alert>

          <div
            v-if="form.lignes.length > 0"
            style="overflow-x: auto"
          >
            <v-table density="compact">
              <thead>
                <tr>
                  <th style="min-width: 200px;">
                    Type de dechet *
                  </th>
                  <th style="min-width: 180px;">
                    Conteneur
                  </th>
                  <th style="width: 120px;">
                    Quantite prevue *
                  </th>
                  <th style="width: 100px;">
                    Unite *
                  </th>
                  <th style="width: 50px;" />
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(ligne, idx) in form.lignes"
                  :key="idx"
                >
                  <td>
                    <v-select
                      v-model="ligne.type_dechet_id"
                      :items="typesDechets"
                      item-title="denomination"
                      item-value="id"
                      density="compact"
                      variant="underlined"
                      hide-details
                      @update:model-value="onLigneTypeChanged(idx)"
                    >
                      <template #item="{ item, props: itemProps }">
                        <v-list-item v-bind="itemProps">
                          <template #prepend>
                            <v-icon
                              v-if="item.raw.dangereux"
                              size="small"
                              color="error"
                            >
                              mdi-alert
                            </v-icon>
                          </template>
                        </v-list-item>
                      </template>
                    </v-select>
                  </td>
                  <td>
                    <v-select
                      v-model="ligne.conteneur_id"
                      :items="getFilteredConteneurs(ligne.type_dechet_id)"
                      item-title="nom"
                      item-value="id"
                      density="compact"
                      variant="underlined"
                      clearable
                      hide-details
                    />
                  </td>
                  <td>
                    <v-text-field
                      v-model.number="ligne.quantite_prevue"
                      type="number"
                      step="0.001"
                      min="0.001"
                      density="compact"
                      variant="underlined"
                      hide-details
                    />
                  </td>
                  <td>
                    <v-select
                      v-model="ligne.unite"
                      :items="unites"
                      density="compact"
                      variant="underlined"
                      hide-details
                    />
                  </td>
                  <td>
                    <v-btn
                      icon="mdi-delete"
                      variant="text"
                      size="x-small"
                      color="error"
                      title="Supprimer la ligne"
                      @click="removeLigne(idx)"
                    />
                  </td>
                </tr>
              </tbody>
            </v-table>
          </div>
        </v-form>
      </v-card-text>

      <v-divider />

      <v-card-actions class="pa-4">
        <v-spacer />
        <v-btn
          variant="text"
          @click="tryClose"
        >
          Annuler
        </v-btn>
        <v-btn
          color="primary"
          variant="flat"
          :loading="saving"
          @click="save"
        >
          {{ isEdit ? 'Enregistrer' : 'Creer' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <ConfirmDialog
    v-model="showDirtyWarning"
    title="Modifications non sauvegardees"
    message="Vous avez des modifications non sauvegardees. Voulez-vous vraiment fermer ?"
    confirm-text="Fermer sans sauvegarder"
    confirm-color="warning"
    @confirm="forceClose"
  />
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { useDisplay } from 'vuetify'
import api from '../../../services/api'
import { useUiStore } from '../../../stores/ui'
import { useFormDirty } from '../../../composables/useFormDirty'
import ConfirmDialog from '../../../components/ConfirmDialog.vue'
import { required } from '../../../services/validators'

const { mobile } = useDisplay()

const props = defineProps({
  modelValue: Boolean,
  enlevement: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'saved'])

const uiStore = useUiStore()
const formRef = ref(null)
const saving = ref(false)
const collecteurs = ref([])
const typesDechets = ref([])
const conteneurs = ref([])

const unites = ['kg', 'litres', 'unites', 'tonnes', 'm3']

const recurrenceOptions = [
  { title: 'Hebdomadaire', value: 'hebdomadaire' },
  { title: 'Bimensuel', value: 'bimensuel' },
  { title: 'Mensuel', value: 'mensuel' },
  { title: 'Bimestriel', value: 'bimestriel' },
  { title: 'Trimestriel', value: 'trimestriel' },
  { title: 'Semestriel', value: 'semestriel' },
  { title: 'Annuel', value: 'annuel' },
]

const isEdit = computed(() => !!props.enlevement?.id)

const defaultForm = () => ({
  collecteur_id: null,
  date_prevue: new Date().toISOString().substring(0, 10),
  recurrence: null,
  notes: '',
  lignes: [],
})

const form = ref(defaultForm())
const { isDirty, snapshot } = useFormDirty(form)
const showDirtyWarning = ref(false)

const rules = { required }

function populateForm(val) {
  form.value = {
    collecteur_id: val.collecteur_id,
    date_prevue: val.date_prevue ? val.date_prevue.substring(0, 10) : '',
    recurrence: val.recurrence || null,
    notes: val.notes || '',
    lignes: (val.lignes || []).map(l => ({
      type_dechet_id: l.type_dechet_id,
      conteneur_id: l.conteneur_id,
      quantite_prevue: l.quantite_prevue,
      unite: l.unite,
    })),
  }
}

watch(() => props.modelValue, async (open) => {
  if (open && props.enlevement?.id) {
    try {
      const { data } = await api.get(`/enlevements/${props.enlevement.id}`)
      populateForm(data.data || data)
    } catch {
      populateForm(props.enlevement)
    }
    nextTick(() => snapshot())
  } else if (open) {
    form.value = defaultForm()
    nextTick(() => snapshot())
  }
})

watch(() => props.enlevement, (val) => {
  if (val) {
    populateForm(val)
  } else {
    form.value = defaultForm()
  }
})

function isAutorisationValide(collecteur) {
  if (!collecteur.date_validite_autorisation) return false
  return new Date(collecteur.date_validite_autorisation) >= new Date()
}

function addLigne() {
  form.value.lignes.push({
    type_dechet_id: null,
    conteneur_id: null,
    quantite_prevue: null,
    unite: 'kg',
  })
}

function removeLigne(idx) {
  form.value.lignes.splice(idx, 1)
}

function onLigneTypeChanged(idx) {
  const ligne = form.value.lignes[idx]
  const type = typesDechets.value.find(t => t.id === ligne.type_dechet_id)
  if (type && type.unite_mesure) {
    ligne.unite = type.unite_mesure
  }
  // Reset conteneur if not compatible
  if (ligne.conteneur_id) {
    const cont = conteneurs.value.find(c => c.id === ligne.conteneur_id)
    if (cont && cont.type_dechet_id !== ligne.type_dechet_id && !cont.mixte) {
      ligne.conteneur_id = null
    }
  }
}

function getFilteredConteneurs(typeDechetId) {
  if (!typeDechetId) return conteneurs.value
  return conteneurs.value.filter(c => c.type_dechet_id === typeDechetId || c.mixte)
}

async function save() {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  if (form.value.lignes.length === 0) {
    uiStore.showWarning('Au moins une ligne est requise.')
    return
  }

  saving.value = true
  try {
    if (isEdit.value) {
      await api.put(`/enlevements/${props.enlevement.id}`, form.value)
      uiStore.showSuccess('Enlevement mis a jour.')
    } else {
      await api.post('/enlevements', form.value)
      uiStore.showSuccess('Enlevement cree.')
    }
    emit('saved')
    close()
  } catch (e) {
    const msg = e.response?.data?.message || 'Erreur lors de l\'enregistrement.'
    uiStore.showError(msg)
  } finally {
    saving.value = false
  }
}

function tryClose() {
  if (isDirty.value) {
    showDirtyWarning.value = true
  } else {
    close()
  }
}

function forceClose() {
  showDirtyWarning.value = false
  close()
}

function close() {
  emit('update:modelValue', false)
  form.value = defaultForm()
}

async function fetchData() {
  try {
    const [collecteursRes, typesRes, contRes] = await Promise.all([
      api.get('/collecteurs'),
      api.get('/types-dechets'),
      api.get('/conteneurs'),
    ])
    collecteurs.value = collecteursRes.data.data || []
    typesDechets.value = typesRes.data.data || typesRes.data || []
    conteneurs.value = contRes.data.data || contRes.data || []
  } catch {
    uiStore.showError('Erreur lors du chargement des donnees du formulaire.')
  }
}

onMounted(fetchData)
</script>
