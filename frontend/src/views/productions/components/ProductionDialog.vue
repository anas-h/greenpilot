<template>
  <v-dialog
    :model-value="modelValue"
    max-width="600"
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
          mdi-plus-circle
        </v-icon>
        {{ isEdit ? 'Modifier la production' : 'Nouvelle production' }}
        <v-spacer />
        <v-btn
          icon="mdi-close"
          variant="text"
          title="Fermer"
          @click="tryClose"
        />
      </v-card-title>

      <v-divider />

      <v-card-text class="pa-4">
        <v-form
          ref="formRef"
          @submit.prevent="save"
        >
          <v-row dense>
            <v-col cols="12">
              <v-select
                v-model="form.type_dechet_id"
                :items="typesDechets"
                item-title="denomination"
                item-value="id"
                label="Type de dechet *"
                :rules="[rules.required]"
                @update:model-value="onTypeChanged"
              >
                <template #item="{ item, props: itemProps }">
                  <v-list-item v-bind="itemProps">
                    <template #prepend>
                      <v-icon
                        v-if="item.raw.dangereux"
                        color="error"
                        size="small"
                      >
                        mdi-alert
                      </v-icon>
                    </template>
                  </v-list-item>
                </template>
              </v-select>
            </v-col>

            <v-col cols="12">
              <v-select
                v-model="form.conteneur_id"
                :items="filteredConteneurs"
                item-title="nom"
                item-value="id"
                label="Conteneur"
                clearable
              >
                <template #item="{ item, props: itemProps }">
                  <v-list-item v-bind="itemProps">
                    <template #append>
                      <span class="text-caption text-grey">
                        {{ Math.round(item.raw.pourcentage || 0) }}% plein
                      </span>
                    </template>
                  </v-list-item>
                </template>
              </v-select>
            </v-col>

            <v-col cols="6">
              <v-text-field
                v-model.number="form.quantite"
                label="Quantite *"
                type="number"
                step="0.001"
                min="0.001"
                :rules="[rules.required, rules.positive]"
              />
            </v-col>

            <v-col cols="6">
              <v-select
                v-model="form.unite"
                :items="unites"
                label="Unite *"
                :rules="[rules.required]"
              />
            </v-col>

            <v-col cols="12">
              <v-text-field
                v-model="form.date_production"
                label="Date de production *"
                type="date"
                :rules="[rules.required]"
              />
            </v-col>

            <v-col cols="12">
              <v-text-field
                v-model="form.vehicule_info"
                label="Information vehicule"
                placeholder="Ex: immatriculation, marque, modele"
              />
            </v-col>

            <v-col cols="12">
              <v-text-field
                v-model="form.reference_externe"
                label="Reference externe"
                placeholder="N° OR, bon de travail..."
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
import { required, positiveNumber } from '../../../services/validators'

const { mobile } = useDisplay()

const props = defineProps({
  modelValue: Boolean,
  production: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'saved'])

const uiStore = useUiStore()
const formRef = ref(null)
const saving = ref(false)
const typesDechets = ref([])
const conteneurs = ref([])

const unites = ['kg', 'litres', 'unites', 'tonnes', 'm3']

const isEdit = computed(() => !!props.production?.id)

const filteredConteneurs = computed(() => {
  if (!form.value.type_dechet_id) return conteneurs.value
  return conteneurs.value.filter(c =>
    c.type_dechet_id === form.value.type_dechet_id || c.mixte
  )
})

const defaultForm = () => ({
  type_dechet_id: null,
  conteneur_id: null,
  quantite: null,
  unite: 'kg',
  date_production: new Date().toISOString().substring(0, 10),
  source_type: 'manuelle',
  vehicule_info: '',
  reference_externe: '',
  notes: '',
})

const form = ref(defaultForm())
const { isDirty, snapshot } = useFormDirty(form)
const showDirtyWarning = ref(false)

const rules = { required, positive: positiveNumber }

watch(() => props.production, (val) => {
  if (val) {
    form.value = {
      ...defaultForm(),
      ...val,
      date_production: val.date_production ? val.date_production.substring(0, 10) : new Date().toISOString().substring(0, 10),
    }
  } else {
    form.value = defaultForm()
  }
  nextTick(() => snapshot())
}, { immediate: true })

function onTypeChanged(typeId) {
  // Auto-set unite from type
  const type = typesDechets.value.find(t => t.id === typeId)
  if (type && type.unite_mesure) {
    form.value.unite = type.unite_mesure
  }
  // Reset conteneur if not compatible
  if (form.value.conteneur_id) {
    const cont = conteneurs.value.find(c => c.id === form.value.conteneur_id)
    if (cont && cont.type_dechet_id !== typeId && !cont.mixte) {
      form.value.conteneur_id = null
    }
  }
}

async function save() {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  saving.value = true
  try {
    if (isEdit.value) {
      await api.put(`/productions/${props.production.id}`, form.value)
      uiStore.showSuccess('Production mise a jour.')
    } else {
      await api.post('/productions', form.value)
      uiStore.showSuccess('Production enregistree.')
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
    const [typesRes, contRes] = await Promise.all([
      api.get('/types-dechets'),
      api.get('/conteneurs'),
    ])
    typesDechets.value = typesRes.data.data || typesRes.data || []
    conteneurs.value = (contRes.data.data || contRes.data || []).map(c => ({
      ...c,
      pourcentage: c.capacite > 0 ? (c.niveau_actuel / c.capacite) * 100 : 0,
    }))
  } catch {
    uiStore.showError('Erreur lors du chargement des donnees du formulaire.')
  }
}

onMounted(fetchData)
</script>
