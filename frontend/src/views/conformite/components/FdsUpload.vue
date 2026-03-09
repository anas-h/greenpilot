<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      <span>{{ editFds ? 'Modifier la FDS' : 'Ajouter une Fiche de Donnees de Securite' }}</span>
      <v-spacer />
      <v-btn
        icon="mdi-close"
        variant="text"
        title="Fermer"
        @click="$emit('cancel')"
      />
    </v-card-title>

    <v-divider />

    <v-card-text>
      <v-form
        ref="formRef"
        v-model="formValid"
        @submit.prevent="onSubmit"
      >
        <v-row>
          <!-- Type dechet -->
          <v-col cols="12">
            <v-select
              v-model="form.type_dechet_id"
              :items="typesDechets"
              item-title="label"
              item-value="id"
              label="Type de dechet *"
              :rules="[v => !!v || 'Le type de dechet est requis']"
              variant="outlined"
              :disabled="!!editFds"
            >
              <template #item="{ item, props: itemProps }">
                <v-list-item v-bind="itemProps">
                  <template #prepend>
                    <v-icon
                      :color="item.raw.dangereux ? 'red' : 'green'"
                      size="small"
                    >
                      {{ item.raw.dangereux ? 'mdi-alert-circle' : 'mdi-recycle' }}
                    </v-icon>
                  </template>
                </v-list-item>
              </template>
            </v-select>
          </v-col>

          <!-- Nom produit -->
          <v-col
            cols="12"
            sm="6"
          >
            <v-text-field
              v-model="form.nom_produit"
              label="Nom du produit *"
              :rules="[v => !!v || 'Le nom du produit est requis']"
              variant="outlined"
            />
          </v-col>

          <!-- Fournisseur -->
          <v-col
            cols="12"
            sm="6"
          >
            <v-text-field
              v-model="form.fournisseur"
              label="Fournisseur"
              variant="outlined"
            />
          </v-col>

          <!-- Numero FDS -->
          <v-col
            cols="12"
            sm="4"
          >
            <v-text-field
              v-model="form.numero_fds"
              label="Numero FDS"
              variant="outlined"
            />
          </v-col>

          <!-- Version -->
          <v-col
            cols="12"
            sm="4"
          >
            <v-text-field
              v-model="form.version"
              label="Version"
              variant="outlined"
              placeholder="ex: 3.2"
            />
          </v-col>

          <!-- Date edition -->
          <v-col
            cols="12"
            sm="4"
          >
            <v-text-field
              v-model="form.date_edition"
              label="Date d'edition"
              type="date"
              variant="outlined"
            />
          </v-col>

          <!-- Date expiration -->
          <v-col
            cols="12"
            sm="6"
          >
            <v-text-field
              v-model="form.date_expiration"
              label="Date d'expiration"
              type="date"
              variant="outlined"
              :rules="expirationRules"
            />
          </v-col>

          <!-- File upload -->
          <v-col
            cols="12"
            sm="6"
          >
            <v-file-input
              v-model="selectedFile"
              :label="editFds ? 'Remplacer le fichier PDF' : 'Fichier PDF *'"
              accept="application/pdf"
              prepend-icon="mdi-file-pdf-box"
              :rules="editFds ? [] : [v => !!v || 'Le fichier PDF est requis']"
              variant="outlined"
              show-size
              :hint="editFds ? 'Laissez vide pour conserver le fichier actuel' : 'Maximum 10 Mo'"
              persistent-hint
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

        <!-- Current file info (when editing) -->
        <v-alert
          v-if="editFds?.fichier_nom"
          type="info"
          variant="tonal"
          density="compact"
          class="mb-4"
        >
          Fichier actuel: {{ editFds.fichier_nom }}
          ({{ formatFileSize(editFds.fichier_taille) }})
        </v-alert>

        <!-- Error display -->
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
      </v-form>
    </v-card-text>

    <v-divider />

    <v-card-actions>
      <v-spacer />
      <v-btn
        variant="text"
        @click="$emit('cancel')"
      >
        Annuler
      </v-btn>
      <v-btn
        color="primary"
        :loading="saving"
        :disabled="!formValid"
        @click="onSubmit"
      >
        {{ editFds ? 'Mettre a jour' : 'Enregistrer' }}
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import api from '@/services/api'
import { useUiStore } from '@/stores/ui'

const uiStore = useUiStore()

const props = defineProps({
  editFds: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['saved', 'cancel'])

const formRef = ref(null)
const formValid = ref(false)
const saving = ref(false)
const errorMessage = ref(null)
const selectedFile = ref(null)
const typesDechets = ref([])

const form = reactive({
  type_dechet_id: null,
  nom_produit: '',
  fournisseur: '',
  numero_fds: '',
  version: '',
  date_edition: null,
  date_expiration: null,
  observations: '',
})

const expirationRules = computed(() => {
  return [
    (v) => {
      if (v && form.date_edition && v < form.date_edition) {
        return 'La date d\'expiration doit etre posterieure a la date d\'edition'
      }
      return true
    },
  ]
})

function formatFileSize(bytes) {
  if (!bytes) return '-'
  if (bytes < 1024) return bytes + ' o'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' Ko'
  return (bytes / (1024 * 1024)).toFixed(1) + ' Mo'
}

async function fetchTypesDechets() {
  try {
    const response = await api.get('/type-dechets', {
      params: { actif: true, per_page: 200 },
    })
    const items = response.data.data || response.data
    typesDechets.value = items.map(td => ({
      id: td.id,
      label: `${td.code_dechet ? td.code_dechet + ' - ' : ''}${td.nom}${td.dangereux ? ' (DD)' : ''}`,
      dangereux: td.dangereux,
    }))
  } catch (err) {
    uiStore.showError('Erreur lors du chargement des types de dechets.')
  }
}

async function onSubmit() {
  if (!formRef.value) return
  const { valid } = await formRef.value.validate()
  if (!valid) return

  saving.value = true
  errorMessage.value = null

  try {
    const formData = new FormData()
    formData.append('type_dechet_id', form.type_dechet_id)
    formData.append('nom_produit', form.nom_produit)
    if (form.fournisseur) formData.append('fournisseur', form.fournisseur)
    if (form.numero_fds) formData.append('numero_fds', form.numero_fds)
    if (form.version) formData.append('version', form.version)
    if (form.date_edition) formData.append('date_edition', form.date_edition)
    if (form.date_expiration) formData.append('date_expiration', form.date_expiration)
    if (form.observations) formData.append('observations', form.observations)
    if (selectedFile.value) {
      formData.append('fichier', selectedFile.value)
    }

    if (props.editFds) {
      // Update existing - use POST with _method=PUT for FormData
      formData.append('_method', 'PUT')
      await api.post(`/fiches-securite/${props.editFds.id}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
    } else {
      // Create new
      await api.post('/fiches-securite', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
    }

    emit('saved')
  } catch (err) {
    if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      errorMessage.value = Object.values(errors).flat().join(', ')
    } else {
      errorMessage.value = err.response?.data?.message || 'Erreur lors de l\'enregistrement'
    }
  } finally {
    saving.value = false
  }
}

// Pre-fill form when editing
watch(() => props.editFds, (fds) => {
  if (fds) {
    form.type_dechet_id = fds.type_dechet_id
    form.nom_produit = fds.nom_produit
    form.fournisseur = fds.fournisseur || ''
    form.numero_fds = fds.numero_fds || ''
    form.version = fds.version || ''
    form.date_edition = fds.date_edition || null
    form.date_expiration = fds.date_expiration || null
    form.observations = fds.observations || ''
    selectedFile.value = null
  }
}, { immediate: true })

onMounted(() => {
  fetchTypesDechets()
})
</script>
