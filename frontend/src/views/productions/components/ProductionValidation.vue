<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      <v-icon
        color="warning"
        class="mr-2"
      >
        mdi-check-decagram
      </v-icon>
      Validation des productions
      <v-spacer />
      <v-chip
        color="warning"
        variant="flat"
        size="small"
      >
        {{ productions.length }} a valider
      </v-chip>
    </v-card-title>

    <v-card-text>
      <v-alert
        v-if="productions.length === 0 && !loading"
        type="success"
        variant="tonal"
      >
        Toutes les productions ont ete validees.
      </v-alert>

      <v-progress-linear
        v-if="loading"
        indeterminate
        color="primary"
        class="mb-4"
      />

      <v-list
        v-if="productions.length > 0"
        lines="two"
      >
        <template
          v-for="production in productions"
          :key="production.id"
        >
          <v-list-item>
            <template #prepend>
              <v-checkbox
                v-model="selected"
                :value="production.id"
                color="primary"
                hide-details
              />
            </template>

            <v-list-item-title>
              <div class="d-flex align-center ga-2">
                <v-icon
                  v-if="production.type_dechet?.dangereux"
                  size="small"
                  color="error"
                >
                  mdi-alert
                </v-icon>
                <span class="font-weight-medium">{{ production.type_dechet?.denomination || 'Type inconnu' }}</span>
                <v-chip
                  size="x-small"
                  variant="outlined"
                  color="grey"
                >
                  {{ production.source_type || 'manuelle' }}
                </v-chip>
              </div>
            </v-list-item-title>

            <v-list-item-subtitle>
              {{ production.quantite }} {{ production.unite }}
              | {{ formatDate(production.date_production) }}
              <span v-if="production.vehicule_info"> | {{ production.vehicule_info }}</span>
              <span v-if="production.conteneur"> | {{ production.conteneur.nom }}</span>
            </v-list-item-subtitle>

            <template #append>
              <v-btn
                icon="mdi-check"
                variant="text"
                color="success"
                size="small"
                @click="validateSingle(production.id)"
              />
            </template>
          </v-list-item>
          <v-divider />
        </template>
      </v-list>
    </v-card-text>

    <v-divider v-if="productions.length > 0" />

    <v-card-actions
      v-if="productions.length > 0"
      class="pa-4"
    >
      <v-btn
        variant="text"
        size="small"
        @click="selectAll"
      >
        {{ selected.length === productions.length ? 'Tout deselectionner' : 'Tout selectionner' }}
      </v-btn>
      <v-spacer />
      <v-btn
        color="success"
        variant="flat"
        :loading="validating"
        :disabled="selected.length === 0"
        prepend-icon="mdi-check-all"
        @click="validateSelected"
      >
        Valider la selection ({{ selected.length }})
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../../services/api'
import { useUiStore } from '../../../stores/ui'

const uiStore = useUiStore()
const productions = ref([])
const selected = ref([])
const loading = ref(false)
const validating = ref(false)

async function fetchUnvalidated() {
  loading.value = true
  try {
    const { data } = await api.get('/productions', { params: { valide: false, per_page: 100 } })
    productions.value = data.data || []
  } catch (e) {
    uiStore.showError('Erreur lors du chargement des productions.')
  } finally {
    loading.value = false
  }
}

function selectAll() {
  if (selected.value.length === productions.value.length) {
    selected.value = []
  } else {
    selected.value = productions.value.map(p => p.id)
  }
}

async function validateSingle(id) {
  validating.value = true
  try {
    await api.post('/productions/validate', { ids: [id] })
    uiStore.showSuccess('Production validee.')
    productions.value = productions.value.filter(p => p.id !== id)
    selected.value = selected.value.filter(s => s !== id)
  } catch (e) {
    uiStore.showError('Erreur lors de la validation.')
  } finally {
    validating.value = false
  }
}

async function validateSelected() {
  if (selected.value.length === 0) return
  validating.value = true
  try {
    await api.post('/productions/validate', { ids: selected.value })
    uiStore.showSuccess(`${selected.value.length} production(s) validee(s).`)
    productions.value = productions.value.filter(p => !selected.value.includes(p.id))
    selected.value = []
  } catch (e) {
    uiStore.showError('Erreur lors de la validation.')
  } finally {
    validating.value = false
  }
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR')
}

onMounted(fetchUnvalidated)
</script>
