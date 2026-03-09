<template>
  <div>
    <v-row
      class="mb-4"
      align="center"
    >
      <v-col>
        <h2 class="text-h6">
          Eco-contributions REP
        </h2>
      </v-col>
      <v-col cols="auto">
        <v-btn
          color="primary"
          prepend-icon="mdi-plus"
          @click="openDialog()"
        >
          Nouvelle eco-contribution
        </v-btn>
      </v-col>
    </v-row>

    <!-- Filters -->
    <v-row class="mb-4">
      <v-col
        cols="12"
        sm="3"
      >
        <v-select
          v-model="filters.filiere_id"
          :items="filieres"
          item-title="nom"
          item-value="id"
          label="Filiere"
          clearable
          density="compact"
          @update:model-value="loadData"
        />
      </v-col>
      <v-col
        cols="12"
        sm="3"
      >
        <v-select
          v-model="filters.annee"
          :items="annees"
          label="Annee"
          clearable
          density="compact"
          @update:model-value="loadData"
        />
      </v-col>
      <v-col
        cols="12"
        sm="3"
      >
        <v-select
          v-model="filters.trimestre"
          :items="[1, 2, 3, 4]"
          label="Trimestre"
          clearable
          density="compact"
          @update:model-value="loadData"
        />
      </v-col>
      <v-col
        cols="12"
        sm="3"
      >
        <v-select
          v-model="filters.statut"
          :items="['brouillon', 'declare', 'paye']"
          label="Statut"
          clearable
          density="compact"
          @update:model-value="loadData"
        />
      </v-col>
    </v-row>

    <!-- Data Table -->
    <v-data-table
      :headers="headers"
      :items="contributions"
      :loading="loading"
      density="compact"
    >
      <template #item.montant="{ item }">
        {{ formatCurrency(item.montant) }}
      </template>
      <template #item.statut="{ item }">
        <v-chip
          :color="statutColor(item.statut)"
          size="small"
          variant="flat"
        >
          {{ item.statut }}
        </v-chip>
      </template>
      <template #item.actions="{ item }">
        <v-btn
          v-if="item.statut === 'brouillon'"
          icon="mdi-pencil"
          size="x-small"
          variant="text"
          @click="openDialog(item)"
        />
        <v-btn
          v-if="item.statut === 'brouillon'"
          icon="mdi-check"
          size="x-small"
          variant="text"
          color="primary"
          title="Declarer"
          @click="declareContribution(item)"
        />
        <v-btn
          v-if="item.statut === 'declare'"
          icon="mdi-currency-eur"
          size="x-small"
          variant="text"
          color="success"
          title="Marquer paye"
          @click="payerContribution(item)"
        />
      </template>
    </v-data-table>

    <!-- Dialog -->
    <v-dialog
      v-model="dialog"
      max-width="600"
      :fullscreen="mobile"
    >
      <v-card>
        <v-card-title>{{ editingId ? 'Modifier' : 'Nouvelle' }} eco-contribution</v-card-title>
        <v-card-text>
          <v-form ref="formRef">
            <v-row>
              <v-col cols="12">
                <v-select
                  v-model="form.filiere_id"
                  :items="filieres"
                  item-title="nom"
                  item-value="id"
                  label="Filiere REP *"
                  :rules="[v => !!v || 'Requis']"
                  :disabled="!!editingId"
                />
              </v-col>
              <v-col cols="12" sm="6">
                <v-select
                  v-model="form.annee"
                  :items="annees"
                  label="Annee *"
                  :rules="[v => !!v || 'Requis']"
                  :disabled="!!editingId"
                />
              </v-col>
              <v-col cols="12" sm="6">
                <v-select
                  v-model="form.trimestre"
                  :items="[1, 2, 3, 4]"
                  label="Trimestre *"
                  :rules="[v => !!v || 'Requis']"
                  :disabled="!!editingId"
                />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model.number="form.montant"
                  label="Montant (EUR) *"
                  type="number"
                  min="0"
                  step="0.01"
                  :rules="[v => v >= 0 || 'Montant invalide']"
                />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model.number="form.quantite_declaree"
                  label="Quantite declaree *"
                  type="number"
                  min="0"
                  step="0.01"
                />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="form.unite"
                  label="Unite *"
                  placeholder="kg, L, unites..."
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="form.notes"
                  label="Notes"
                  rows="2"
                />
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="dialog = false">
            Annuler
          </v-btn>
          <v-btn
            color="primary"
            :loading="saving"
            @click="save"
          >
            Enregistrer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useDisplay } from 'vuetify'
import api from '../../../services/api'

const { mobile } = useDisplay()
const loading = ref(false)
const saving = ref(false)
const dialog = ref(false)
const editingId = ref(null)
const formRef = ref(null)
const contributions = ref([])
const filieres = ref([])

const currentYear = new Date().getFullYear()
const annees = Array.from({ length: 10 }, (_, i) => currentYear - i)

const filters = ref({
  filiere_id: null,
  annee: null,
  trimestre: null,
  statut: null,
})

const form = ref({
  filiere_id: null,
  annee: currentYear,
  trimestre: null,
  montant: 0,
  quantite_declaree: 0,
  unite: 'kg',
  notes: '',
})

const headers = [
  { title: 'Filiere', key: 'filiere.nom', sortable: true },
  { title: 'Annee', key: 'annee', sortable: true },
  { title: 'Trimestre', key: 'trimestre', sortable: true },
  { title: 'Montant', key: 'montant', sortable: true },
  { title: 'Quantite', key: 'quantite_declaree', sortable: true },
  { title: 'Statut', key: 'statut', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' },
]

function formatCurrency(val) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val || 0)
}

function statutColor(statut) {
  const map = { brouillon: 'grey', declare: 'primary', paye: 'success' }
  return map[statut] || 'grey'
}

function openDialog(item = null) {
  if (item) {
    editingId.value = item.id
    form.value = {
      filiere_id: item.filiere_id,
      annee: item.annee,
      trimestre: item.trimestre,
      montant: item.montant,
      quantite_declaree: item.quantite_declaree,
      unite: item.unite,
      notes: item.notes || '',
    }
  } else {
    editingId.value = null
    form.value = {
      filiere_id: null,
      annee: currentYear,
      trimestre: null,
      montant: 0,
      quantite_declaree: 0,
      unite: 'kg',
      notes: '',
    }
  }
  dialog.value = true
}

async function loadData() {
  loading.value = true
  try {
    const params = {}
    Object.entries(filters.value).forEach(([k, v]) => {
      if (v !== null && v !== '') params[k] = v
    })
    const { data } = await api.get('/eco-contributions', { params })
    contributions.value = data.data || data
  } catch {
    /* ignore */
  } finally {
    loading.value = false
  }
}

async function save() {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  saving.value = true
  try {
    if (editingId.value) {
      await api.put(`/eco-contributions/${editingId.value}`, form.value)
    } else {
      await api.post('/eco-contributions', form.value)
    }
    dialog.value = false
    await loadData()
  } catch {
    /* ignore */
  } finally {
    saving.value = false
  }
}

async function declareContribution(item) {
  try {
    await api.post(`/eco-contributions/${item.id}/declare`)
    await loadData()
  } catch {
    /* ignore */
  }
}

async function payerContribution(item) {
  try {
    await api.post(`/eco-contributions/${item.id}/payer`)
    await loadData()
  } catch {
    /* ignore */
  }
}

async function loadFilieres() {
  try {
    const { data } = await api.get('/filieres-rep')
    filieres.value = data.data || data
  } catch {
    /* ignore */
  }
}

onMounted(() => {
  loadFilieres()
  loadData()
})
</script>
