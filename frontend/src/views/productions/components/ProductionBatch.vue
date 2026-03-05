<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      <v-icon
        color="primary"
        class="mr-2"
      >
        mdi-table-plus
      </v-icon>
      Saisie groupee
      <v-spacer />
      <v-btn
        variant="text"
        color="primary"
        size="small"
        prepend-icon="mdi-plus"
        @click="addRow"
      >
        Ajouter une ligne
      </v-btn>
    </v-card-title>

    <v-card-text>
      <v-alert
        v-if="rows.length === 0"
        type="info"
        variant="tonal"
        class="mb-4"
      >
        Ajoutez des lignes pour saisir plusieurs productions en une seule fois.
      </v-alert>

      <div
        v-if="rows.length > 0"
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
                Quantite *
              </th>
              <th style="width: 100px;">
                Unite *
              </th>
              <th style="width: 150px;">
                Date *
              </th>
              <th style="width: 50px;" />
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(row, idx) in rows"
              :key="idx"
              :class="{ 'bg-red-lighten-5': row.error }"
            >
              <td>
                <v-select
                  v-model="row.type_dechet_id"
                  :items="typesDechets"
                  item-title="denomination"
                  item-value="id"
                  density="compact"
                  variant="underlined"
                  hide-details
                  @update:model-value="onTypeChanged(idx)"
                />
              </td>
              <td>
                <v-select
                  v-model="row.conteneur_id"
                  :items="getFilteredConteneurs(row.type_dechet_id)"
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
                  v-model.number="row.quantite"
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
                  v-model="row.unite"
                  :items="unites"
                  density="compact"
                  variant="underlined"
                  hide-details
                />
              </td>
              <td>
                <v-text-field
                  v-model="row.date_production"
                  type="date"
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
                  @click="removeRow(idx)"
                />
              </td>
            </tr>
          </tbody>
        </v-table>
      </div>
    </v-card-text>

    <v-divider v-if="rows.length > 0" />

    <v-card-actions
      v-if="rows.length > 0"
      class="pa-4"
    >
      <span class="text-body-2 text-grey">{{ rows.length }} ligne(s)</span>
      <v-spacer />
      <v-btn
        variant="text"
        @click="clearAll"
      >
        Tout effacer
      </v-btn>
      <v-btn
        color="primary"
        variant="flat"
        :loading="saving"
        :disabled="!isValid"
        prepend-icon="mdi-content-save-all"
        @click="saveAll"
      >
        Enregistrer tout ({{ rows.length }})
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../../services/api'
import { useUiStore } from '../../../stores/ui'

const emit = defineEmits(['saved'])

const uiStore = useUiStore()
const saving = ref(false)
const typesDechets = ref([])
const conteneurs = ref([])

const unites = ['kg', 'litres', 'unites', 'tonnes', 'm3']

const rows = ref([])

const isValid = computed(() => {
  return rows.value.length > 0 && rows.value.every(r =>
    r.type_dechet_id && r.quantite > 0 && r.unite && r.date_production
  )
})

function newRow() {
  return {
    type_dechet_id: null,
    conteneur_id: null,
    quantite: null,
    unite: 'kg',
    date_production: new Date().toISOString().substring(0, 10),
    source_type: 'manuelle',
    error: false,
  }
}

function addRow() {
  rows.value.push(newRow())
}

function removeRow(idx) {
  rows.value.splice(idx, 1)
}

function clearAll() {
  rows.value = []
}

function onTypeChanged(idx) {
  const row = rows.value[idx]
  const type = typesDechets.value.find(t => t.id === row.type_dechet_id)
  if (type && type.unite_mesure) {
    row.unite = type.unite_mesure
  }
  // Reset conteneur if not compatible
  if (row.conteneur_id) {
    const cont = conteneurs.value.find(c => c.id === row.conteneur_id)
    if (cont && cont.type_dechet_id !== row.type_dechet_id && !cont.mixte) {
      row.conteneur_id = null
    }
  }
}

function getFilteredConteneurs(typeDechetId) {
  if (!typeDechetId) return conteneurs.value
  return conteneurs.value.filter(c => c.type_dechet_id === typeDechetId || c.mixte)
}

async function saveAll() {
  if (!isValid.value) return

  saving.value = true
  try {
    const productions = rows.value.map(r => ({
      type_dechet_id: r.type_dechet_id,
      conteneur_id: r.conteneur_id,
      quantite: r.quantite,
      unite: r.unite,
      date_production: r.date_production,
      source_type: r.source_type,
    }))

    const { data } = await api.post('/productions/batch', { productions })
    uiStore.showSuccess(`${data.count || productions.length} production(s) enregistree(s).`)
    rows.value = []
    emit('saved')
  } catch (e) {
    const msg = e.response?.data?.message || 'Erreur lors de l\'enregistrement.'
    uiStore.showError(msg)
  } finally {
    saving.value = false
  }
}

async function fetchData() {
  try {
    const [typesRes, contRes] = await Promise.all([
      api.get('/types-dechets'),
      api.get('/conteneurs'),
    ])
    typesDechets.value = typesRes.data.data || typesRes.data || []
    conteneurs.value = contRes.data.data || contRes.data || []
  } catch {
    // Silently fail
  }
}

onMounted(fetchData)
</script>
