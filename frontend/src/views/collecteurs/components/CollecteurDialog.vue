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
          mdi-truck
        </v-icon>
        {{ isEdit ? 'Modifier le collecteur' : 'Ajouter un collecteur' }}
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
          <h3 class="text-subtitle-1 font-weight-bold mb-3">
            Informations generales
          </h3>
          <v-row dense>
            <v-col
              cols="12"
              md="8"
            >
              <v-autocomplete
                v-model="selectedCompany"
                v-model:search="searchQuery"
                :items="suggestions"
                :loading="searching"
                item-title="label"
                item-value="siret"
                label="Raison sociale *"
                placeholder="Rechercher sur Trackdechets..."
                no-filter
                clearable
                return-object
                :no-data-text="searchQuery && searchQuery.length >= 2 ? 'Aucun resultat Trackdechets' : 'Tapez au moins 2 caracteres'"
                :rules="[rules.required]"
                @update:model-value="onCompanySelected"
              >
                <template #item="{ item, props: itemProps }">
                  <v-list-item v-bind="itemProps">
                    <template #subtitle>
                      SIRET: {{ item.raw.siret }} — {{ item.raw.city }}
                    </template>
                  </v-list-item>
                </template>
              </v-autocomplete>
            </v-col>
            <v-col
              cols="12"
              md="4"
            >
              <v-text-field
                v-model="form.siret"
                label="SIRET *"
                :rules="[rules.required, rules.siretLength]"
                counter="14"
              />
            </v-col>
            <v-col cols="12">
              <v-text-field
                v-model="form.adresse"
                label="Adresse *"
                :rules="[rules.required]"
              />
            </v-col>
            <v-col
              cols="12"
              md="4"
            >
              <v-text-field
                v-model="form.code_postal"
                label="Code postal *"
                :rules="[rules.required]"
              />
            </v-col>
            <v-col
              cols="12"
              md="8"
            >
              <v-text-field
                v-model="form.ville"
                label="Ville *"
                :rules="[rules.required]"
              />
            </v-col>
          </v-row>

          <h3 class="text-subtitle-1 font-weight-bold mt-4 mb-3">
            Autorisations
          </h3>
          <v-row dense>
            <v-col
              cols="12"
              md="6"
            >
              <v-text-field
                v-model="form.numero_autorisation"
                label="Numero d'autorisation *"
                :rules="[rules.required]"
              />
            </v-col>
            <v-col
              cols="12"
              md="6"
            >
              <v-text-field
                v-model="form.date_validite_autorisation"
                label="Date de validite *"
                type="date"
                :rules="[rules.required]"
              />
            </v-col>
            <v-col
              cols="12"
              md="4"
            >
              <v-checkbox
                v-model="form.autorisation_adr"
                label="Autorisation ADR"
                color="primary"
                hide-details
              />
            </v-col>
            <v-col
              cols="12"
              md="4"
            >
              <v-text-field
                v-model="form.numero_adr"
                label="Numero ADR"
                :disabled="!form.autorisation_adr"
              />
            </v-col>
            <v-col
              cols="12"
              md="4"
            >
              <v-text-field
                v-model="form.eco_organisme"
                label="Eco-organisme"
              />
            </v-col>
          </v-row>

          <h3 class="text-subtitle-1 font-weight-bold mt-4 mb-3">
            Contact
          </h3>
          <v-row dense>
            <v-col
              cols="12"
              md="4"
            >
              <v-text-field
                v-model="form.contact_nom"
                label="Nom du contact"
              />
            </v-col>
            <v-col
              cols="12"
              md="4"
            >
              <v-text-field
                v-model="form.contact_telephone"
                label="Telephone"
              />
            </v-col>
            <v-col
              cols="12"
              md="4"
            >
              <v-text-field
                v-model="form.contact_email"
                label="Email"
                type="email"
              />
            </v-col>
            <v-col
              cols="12"
              md="6"
            >
              <v-text-field
                v-model="form.site_web"
                label="Site web"
              />
            </v-col>
            <v-col
              cols="12"
              md="6"
            >
              <v-text-field
                v-model="form.notes"
                label="Notes"
              />
            </v-col>
          </v-row>

          <h3 class="text-subtitle-1 font-weight-bold mt-4 mb-3">
            Tarifs par type de dechet
            <v-btn
              variant="text"
              color="primary"
              size="small"
              prepend-icon="mdi-plus"
              @click="addTarif"
            >
              Ajouter
            </v-btn>
          </h3>

          <div
            v-if="form.tarifs.length > 0"
            style="overflow-x: auto"
          >
            <v-table density="compact">
              <thead>
                <tr>
                  <th>Type de dechet</th>
                  <th>Prix unitaire</th>
                  <th>Prix forfaitaire</th>
                  <th>Rachat</th>
                  <th />
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(tarif, idx) in form.tarifs"
                  :key="idx"
                >
                  <td style="min-width: 200px;">
                    <v-select
                      v-model="tarif.type_dechet_id"
                      :items="typesDechets"
                      item-title="denomination"
                      item-value="id"
                      density="compact"
                      variant="underlined"
                      hide-details
                    />
                  </td>
                  <td style="width: 140px;">
                    <v-text-field
                      v-model.number="tarif.prix_unitaire"
                      type="number"
                      step="0.01"
                      density="compact"
                      variant="underlined"
                      suffix="EUR"
                      hide-details
                    />
                  </td>
                  <td style="width: 140px;">
                    <v-text-field
                      v-model.number="tarif.prix_forfaitaire"
                      type="number"
                      step="0.01"
                      density="compact"
                      variant="underlined"
                      suffix="EUR"
                      hide-details
                    />
                  </td>
                  <td style="width: 80px;">
                    <v-checkbox
                      v-model="tarif.est_rachat"
                      density="compact"
                      hide-details
                      color="success"
                    />
                  </td>
                  <td style="width: 40px;">
                    <v-btn
                      icon="mdi-delete"
                      variant="text"
                      size="x-small"
                      color="error"
                      title="Supprimer le tarif"
                      @click="removeTarif(idx)"
                    />
                  </td>
                </tr>
              </tbody>
            </v-table>
          </div>
          <p
            v-else
            class="text-body-2 text-grey text-center pa-4"
          >
            Aucun tarif configure. Cliquez sur "Ajouter" pour definir les prix.
          </p>
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
import { required, siretLength } from '../../../services/validators'

const { mobile } = useDisplay()

const props = defineProps({
  modelValue: Boolean,
  collecteur: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'saved'])

const uiStore = useUiStore()
const formRef = ref(null)
const saving = ref(false)
const typesDechets = ref([])
const searching = ref(false)
const searchQuery = ref('')
const suggestions = ref([])
const selectedCompany = ref(null)
let debounceTimer = null
let isPopulating = false

const isEdit = computed(() => !!props.collecteur?.id)

const defaultForm = () => ({
  raison_sociale: '',
  siret: '',
  adresse: '',
  code_postal: '',
  ville: '',
  numero_autorisation: '',
  date_validite_autorisation: '',
  autorisation_adr: false,
  numero_adr: '',
  eco_organisme: '',
  contact_nom: '',
  contact_telephone: '',
  contact_email: '',
  site_web: '',
  notes: '',
  actif: true,
  tarifs: [],
})

const form = ref(defaultForm())
const { isDirty, snapshot } = useFormDirty(form)
const showDirtyWarning = ref(false)

const rules = { required, siretLength }

// Debounced Trackdechets search
watch(searchQuery, (val) => {
  clearTimeout(debounceTimer)
  // Skip API search when populating form (edit mode)
  if (isPopulating) return
  // Keep raison_sociale in sync with free text input
  if (val && !selectedCompany.value) {
    form.value.raison_sociale = val
  }
  if (!val || val.length < 2) {
    suggestions.value = []
    return
  }
  debounceTimer = setTimeout(async () => {
    searching.value = true
    try {
      const { data } = await api.get('/trackdechets/search-companies', { params: { clue: val } })
      suggestions.value = (data || []).map(c => ({
        label: c.name,
        siret: c.siret,
        address: c.address,
        city: extractCity(c.address),
        companyTypes: c.companyTypes,
        transporterReceipt: c.transporterReceipt,
      }))
    } catch {
      suggestions.value = []
    } finally {
      searching.value = false
    }
  }, 300)
})

function extractCity(address) {
  if (!address) return ''
  const match = address.match(/(\d{5})\s+(.+)$/)
  return match ? match[2] : ''
}

function onCompanySelected(company) {
  if (!company) return
  form.value.raison_sociale = company.label || ''
  form.value.siret = company.siret || ''
  if (company.address) {
    form.value.adresse = company.address.replace(/\s*\d{5}\s+.+$/, '').trim()
    const cpMatch = company.address.match(/(\d{5})\s+/)
    form.value.code_postal = cpMatch ? cpMatch[1] : ''
    form.value.ville = extractCity(company.address)
  }
  if (company.transporterReceipt) {
    form.value.numero_autorisation = company.transporterReceipt.receiptNumber || ''
    form.value.date_validite_autorisation = company.transporterReceipt.validityLimit
      ? company.transporterReceipt.validityLimit.substring(0, 10)
      : ''
  }
}

function populateForm(val) {
  isPopulating = true
  const currentCompany = { label: val.raison_sociale, siret: val.siret }
  selectedCompany.value = currentCompany
  suggestions.value = [currentCompany]
  form.value = {
    ...defaultForm(),
    ...val,
    date_validite_autorisation: val.date_validite_autorisation ? val.date_validite_autorisation.substring(0, 10) : '',
    tarifs: (val.types_dechets || []).map(td => ({
      type_dechet_id: td.id,
      prix_unitaire: td.pivot?.prix_unitaire ?? null,
      prix_forfaitaire: td.pivot?.prix_forfaitaire ?? null,
      est_rachat: td.pivot?.est_rachat ?? false,
    })),
  }
  searchQuery.value = val.raison_sociale || ''
  nextTick(() => {
    isPopulating = false
    snapshot()
  })
}

function resetForm() {
  isPopulating = true
  selectedCompany.value = null
  suggestions.value = []
  form.value = defaultForm()
  searchQuery.value = ''
  nextTick(() => {
    isPopulating = false
    snapshot()
  })
}

// Repopulate form when dialog reopens (same collecteur reference)
watch(() => props.modelValue, (open) => {
  if (open && props.collecteur) {
    populateForm(props.collecteur)
  } else if (open) {
    resetForm()
  }
})

watch(() => props.collecteur, (val) => {
  if (val) {
    populateForm(val)
  } else {
    resetForm()
  }
}, { immediate: true })

function addTarif() {
  form.value.tarifs.push({
    type_dechet_id: null,
    prix_unitaire: null,
    prix_forfaitaire: null,
    est_rachat: false,
  })
}

function removeTarif(idx) {
  form.value.tarifs.splice(idx, 1)
}

async function save() {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  saving.value = true
  try {
    const payload = { ...form.value }
    if (isEdit.value) {
      await api.put(`/collecteurs/${props.collecteur.id}`, payload)
      uiStore.showSuccess('Collecteur mis a jour.')
    } else {
      await api.post('/collecteurs', payload)
      uiStore.showSuccess('Collecteur cree.')
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
}

async function fetchTypesDechets() {
  try {
    const { data } = await api.get('/types-dechets')
    typesDechets.value = data.data || data || []
  } catch {
    uiStore.showError('Erreur lors du chargement des types de dechets.')
  }
}

onMounted(fetchTypesDechets)
</script>
