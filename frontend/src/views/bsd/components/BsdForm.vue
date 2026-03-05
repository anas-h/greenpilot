<template>
  <v-form
    ref="formRef"
    v-model="formValid"
    @submit.prevent="onSubmit"
  >
    <v-row>
      <!-- Type BSD -->
      <v-col
        cols="12"
        md="4"
      >
        <v-select
          v-model="form.type_bsd"
          :items="typeBsdOptions"
          label="Type de BSD *"
          :rules="[v => !!v || 'Le type est requis']"
          variant="outlined"
        />
      </v-col>

      <!-- Type dechet -->
      <v-col
        cols="12"
        md="4"
      >
        <v-select
          v-model="form.type_dechet_id"
          :items="typesDechets"
          item-title="label"
          item-value="id"
          label="Type de dechet *"
          :rules="[v => !!v || 'Le type de dechet est requis']"
          variant="outlined"
          @update:model-value="onTypeDechetChange"
        />
      </v-col>

      <!-- Code dechet -->
      <v-col
        cols="12"
        md="4"
      >
        <v-text-field
          v-model="form.code_dechet"
          label="Code dechet *"
          :rules="[v => !!v || 'Le code dechet est requis']"
          variant="outlined"
          placeholder="ex: 13 02 05*"
        />
      </v-col>

      <!-- Denomination -->
      <v-col cols="12">
        <v-text-field
          v-model="form.denomination_dechet"
          label="Denomination du dechet *"
          :rules="[v => !!v || 'La denomination est requise']"
          variant="outlined"
        />
      </v-col>

      <!-- Quantite -->
      <v-col
        cols="12"
        md="4"
      >
        <v-text-field
          v-model.number="form.quantite"
          label="Quantite"
          type="number"
          min="0"
          step="0.01"
          variant="outlined"
        />
      </v-col>

      <!-- Unite -->
      <v-col
        cols="12"
        md="4"
      >
        <v-select
          v-model="form.unite"
          :items="['kg', 'litres']"
          label="Unite"
          variant="outlined"
        />
      </v-col>

      <!-- Consistance -->
      <v-col
        cols="12"
        md="4"
      >
        <v-select
          v-model="form.consistance"
          :items="['SOLIDE', 'LIQUIDE', 'GAZEUX', 'PATEUX']"
          label="Consistance"
          variant="outlined"
        />
      </v-col>

      <v-col cols="12">
        <v-divider class="my-2" />
        <div class="text-subtitle-1 font-weight-bold mt-2">
          Transporteur
        </div>
      </v-col>

      <!-- Collecteur selection -->
      <v-col
        cols="12"
        md="6"
      >
        <v-select
          v-model="form.collecteur_id"
          :items="collecteurs"
          item-title="label"
          item-value="id"
          label="Selectionnez un collecteur"
          clearable
          variant="outlined"
          @update:model-value="onCollecteurChange"
        />
      </v-col>

      <!-- Transporteur SIRET -->
      <v-col
        cols="12"
        md="6"
      >
        <v-text-field
          v-model="form.transporteur_siret"
          label="SIRET transporteur"
          variant="outlined"
          maxlength="14"
        />
      </v-col>

      <!-- Transporteur raison sociale -->
      <v-col
        cols="12"
        md="6"
      >
        <v-text-field
          v-model="form.transporteur_raison_sociale"
          label="Raison sociale transporteur"
          variant="outlined"
        />
      </v-col>

      <!-- Transporteur recepisse -->
      <v-col
        cols="12"
        md="6"
      >
        <v-text-field
          v-model="form.transporteur_recepisse"
          label="Recepisse transport"
          variant="outlined"
        />
      </v-col>

      <v-col cols="12">
        <v-divider class="my-2" />
        <div class="text-subtitle-1 font-weight-bold mt-2">
          Destination
        </div>
      </v-col>

      <!-- Destination SIRET -->
      <v-col
        cols="12"
        md="6"
      >
        <v-text-field
          v-model="form.destination_siret"
          label="SIRET destination"
          variant="outlined"
          maxlength="14"
        />
      </v-col>

      <!-- Destination raison sociale -->
      <v-col
        cols="12"
        md="6"
      >
        <v-text-field
          v-model="form.destination_raison_sociale"
          label="Raison sociale destination"
          variant="outlined"
        />
      </v-col>

      <!-- Destination adresse -->
      <v-col cols="12">
        <v-text-field
          v-model="form.destination_adresse"
          label="Adresse destination"
          variant="outlined"
        />
      </v-col>

      <!-- CAP -->
      <v-col
        cols="12"
        md="6"
      >
        <v-text-field
          v-model="form.cap"
          label="CAP (Certificat d'Acceptation Prealable)"
          variant="outlined"
        />
      </v-col>

      <!-- Code traitement -->
      <v-col
        cols="12"
        md="6"
      >
        <v-text-field
          v-model="form.code_traitement"
          label="Code traitement"
          variant="outlined"
          placeholder="ex: D10, R1"
        />
      </v-col>

      <!-- Observations -->
      <v-col cols="12">
        <v-textarea
          v-model="form.observations"
          label="Observations"
          rows="3"
          variant="outlined"
        />
      </v-col>
    </v-row>

    <!-- Error -->
    <v-alert
      v-if="errorMessage"
      type="error"
      variant="tonal"
      density="compact"
      class="mb-4"
      closable
      @click:close="errorMessage = null"
    >
      {{ errorMessage }}
    </v-alert>

    <!-- Actions -->
    <div class="d-flex justify-end ga-3 mt-4">
      <v-btn
        variant="text"
        @click="tryCancel"
      >
        Annuler
      </v-btn>
      <v-btn
        color="primary"
        type="submit"
        :loading="saving"
        :disabled="!formValid"
      >
        Creer le brouillon
      </v-btn>
    </div>
  </v-form>

  <ConfirmDialog
    v-model="showDirtyWarning"
    title="Modifications non sauvegardees"
    message="Vous avez des modifications non sauvegardees. Voulez-vous vraiment fermer ?"
    confirm-text="Fermer sans sauvegarder"
    confirm-color="warning"
    @confirm="forceCancel"
  />
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useBsdStore } from '@/stores/bsd'
import api from '@/services/api'
import { useUiStore } from '@/stores/ui'
import { useFormDirty } from '@/composables/useFormDirty'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

const uiStore = useUiStore()

const emit = defineEmits(['saved', 'cancel'])

const bsdStore = useBsdStore()
const formRef = ref(null)
const formValid = ref(false)
const saving = ref(false)
const errorMessage = ref(null)
const typesDechets = ref([])
const collecteurs = ref([])

const typeBsdOptions = [
  { title: 'BSDD - Dechets dangereux', value: 'BSDD' },
  { title: 'BSDA - Amiante', value: 'BSDA' },
  { title: 'BSVHU - VHU', value: 'BSVHU' },
]

const formReactive = computed(() => ({ ...form }))
const { isDirty, snapshot } = useFormDirty(formReactive)
const showDirtyWarning = ref(false)

const form = reactive({
  type_bsd: 'BSDD',
  type_dechet_id: null,
  code_dechet: '',
  denomination_dechet: '',
  quantite: null,
  unite: 'kg',
  consistance: null,
  collecteur_id: null,
  transporteur_siret: '',
  transporteur_raison_sociale: '',
  transporteur_recepisse: '',
  destination_siret: '',
  destination_raison_sociale: '',
  destination_adresse: '',
  cap: '',
  code_traitement: '',
  observations: '',
})

async function fetchTypesDechets() {
  try {
    const response = await api.get('/types-dechets', { params: { actif: true, per_page: 200 } })
    const items = response.data.data || response.data
    typesDechets.value = items.map(td => ({
      id: td.id,
      label: `${td.code_europeen ? td.code_europeen + ' - ' : ''}${td.denomination}`,
      code_dechet: td.code_europeen,
      nom: td.denomination,
    }))
  } catch (err) {
    uiStore.showError('Erreur lors du chargement des types de dechets.')
  }
}

async function fetchCollecteurs() {
  try {
    const response = await api.get('/collecteurs', { params: { actif: true, per_page: 200 } })
    const items = response.data.data || response.data
    collecteurs.value = items.map(c => ({
      id: c.id,
      label: `${c.raison_sociale}${c.siret ? ' (' + c.siret + ')' : ''}`,
      siret: c.siret,
      raison_sociale: c.raison_sociale,
      recepisse: c.numero_autorisation,
    }))
  } catch (err) {
    uiStore.showError('Erreur lors du chargement des collecteurs.')
  }
}

function onTypeDechetChange(id) {
  const td = typesDechets.value.find(t => t.id === id)
  if (td) {
    form.code_dechet = td.code_dechet || ''
    form.denomination_dechet = td.nom || ''
  }
}

function onCollecteurChange(id) {
  const c = collecteurs.value.find(col => col.id === id)
  if (c) {
    form.transporteur_siret = c.siret || ''
    form.transporteur_raison_sociale = c.raison_sociale || ''
    form.transporteur_recepisse = c.recepisse || ''
  }
}

async function onSubmit() {
  if (!formRef.value) return
  const { valid } = await formRef.value.validate()
  if (!valid) return

  saving.value = true
  errorMessage.value = null

  try {
    const bsd = await bsdStore.createBordereau({ ...form })
    emit('saved', bsd)
  } catch (err) {
    if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      errorMessage.value = Object.values(errors).flat().join(', ')
    } else {
      errorMessage.value = err.response?.data?.message || 'Erreur lors de la creation du bordereau'
    }
  } finally {
    saving.value = false
  }
}

function tryCancel() {
  if (isDirty.value) {
    showDirtyWarning.value = true
  } else {
    emit('cancel')
  }
}

function forceCancel() {
  showDirtyWarning.value = false
  emit('cancel')
}

onMounted(() => {
  fetchTypesDechets()
  fetchCollecteurs()
  snapshot()
})
</script>
