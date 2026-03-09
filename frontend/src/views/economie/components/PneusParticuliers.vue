<template>
  <div>
    <v-row
      class="mb-4"
      align="center"
    >
      <v-col>
        <h2 class="text-h6">
          Pneus particuliers
        </h2>
      </v-col>
      <v-col cols="auto">
        <v-btn
          color="primary"
          prepend-icon="mdi-plus"
          @click="openDialog"
        >
          Nouvelle prise en charge
        </v-btn>
      </v-col>
    </v-row>

    <!-- Annual Counter -->
    <v-row class="mb-4">
      <v-col
        cols="12"
        sm="4"
      >
        <v-card
          class="pa-4"
          color="primary"
          variant="tonal"
        >
          <p class="text-body-2">
            Compteur annuel {{ compteur.annee }}
          </p>
          <p class="text-h4 font-weight-bold">
            {{ compteur.total_pneus }}
          </p>
          <p class="text-caption">
            pneus pris en charge ({{ compteur.total_prises_en_charge }} prises en charge)
          </p>
        </v-card>
      </v-col>
      <v-col
        cols="12"
        sm="4"
      >
        <v-select
          v-model="selectedAnnee"
          :items="annees"
          label="Annee"
          density="compact"
          @update:model-value="loadAll"
        />
      </v-col>
    </v-row>

    <!-- Data Table -->
    <div style="overflow-x: auto">
      <v-data-table
        :headers="headers"
        :items="prises"
        :loading="loading"
        density="compact"
      >
      <template #item.types_pneus="{ item }">
        <span
          v-for="(tp, i) in item.types_pneus"
          :key="i"
        >
          {{ tp.quantite }}x {{ tp.type }}<span v-if="i < item.types_pneus.length - 1">, </span>
        </span>
      </template>
      <template #item.date_prise_en_charge="{ item }">
        {{ new Date(item.date_prise_en_charge).toLocaleDateString('fr-FR') }}
      </template>
    </v-data-table>
    </div>

    <!-- Dialog -->
    <v-dialog
      v-model="dialog"
      max-width="600"
      :fullscreen="mobile"
    >
      <v-card>
        <v-card-title>Nouvelle prise en charge pneus</v-card-title>
        <v-card-text>
          <v-form ref="formRef">
            <v-row>
              <v-col
                cols="12"
                sm="6"
              >
                <v-text-field
                  v-model="form.nom_particulier"
                  label="Nom *"
                  :rules="[v => !!v || 'Requis']"
                />
              </v-col>
              <v-col
                cols="12"
                sm="6"
              >
                <v-text-field
                  v-model="form.prenom_particulier"
                  label="Prenom"
                />
              </v-col>
              <v-col
                cols="12"
                sm="6"
              >
                <v-text-field
                  v-model="form.telephone"
                  label="Telephone"
                />
              </v-col>
              <v-col
                cols="12"
                sm="6"
              >
                <v-text-field
                  v-model="form.date_prise_en_charge"
                  label="Date"
                  type="date"
                />
              </v-col>
            </v-row>

            <h3 class="text-subtitle-1 font-weight-bold mt-4 mb-2">
              Detail des pneus (max 8 au total)
            </h3>

            <v-row
              v-for="(tp, index) in form.types_pneus"
              :key="index"
              align="center"
            >
              <v-col
                cols="12"
                sm="5"
              >
                <v-select
                  v-model="tp.type"
                  :items="typesPneus"
                  item-title="label"
                  item-value="value"
                  label="Type"
                  density="compact"
                />
              </v-col>
              <v-col
                cols="12"
                sm="4"
              >
                <v-text-field
                  v-model.number="tp.quantite"
                  label="Quantite"
                  type="number"
                  min="1"
                  max="8"
                  density="compact"
                />
              </v-col>
              <v-col
                cols="12"
                sm="3"
              >
                <v-btn
                  v-if="form.types_pneus.length > 1"
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  color="error"
                  @click="removePneuType(index)"
                />
              </v-col>
            </v-row>

            <v-btn
              v-if="totalPneus < 8"
              variant="text"
              color="primary"
              prepend-icon="mdi-plus"
              size="small"
              class="mt-2"
              @click="addPneuType"
            >
              Ajouter un type
            </v-btn>

            <p class="text-body-2 mt-2">
              Total: <strong>{{ totalPneus }}</strong> / 8 pneus
            </p>

            <v-textarea
              v-model="form.notes"
              label="Notes"
              rows="2"
              class="mt-4"
            />
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
            :disabled="totalPneus < 1 || totalPneus > 8"
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
import { ref, computed, onMounted } from 'vue'
import { useDisplay } from 'vuetify'
import api from '../../../services/api'

const { mobile } = useDisplay()
const loading = ref(false)
const saving = ref(false)
const dialog = ref(false)
const formRef = ref(null)
const prises = ref([])

const currentYear = new Date().getFullYear()
const annees = Array.from({ length: 10 }, (_, i) => currentYear - i)
const selectedAnnee = ref(currentYear)

const compteur = ref({
  annee: currentYear,
  total_prises_en_charge: 0,
  total_pneus: 0,
})

const typesPneus = [
  { label: 'VL (vehicule leger)', value: 'vl' },
  { label: 'Utilitaire', value: 'utilitaire' },
  { label: 'Moto', value: 'moto' },
  { label: 'Poids lourd', value: 'poids_lourd' },
]

const form = ref({
  nom_particulier: '',
  prenom_particulier: '',
  telephone: '',
  date_prise_en_charge: new Date().toISOString().slice(0, 10),
  types_pneus: [{ type: 'vl', quantite: 1 }],
  notes: '',
})

const totalPneus = computed(() =>
  form.value.types_pneus.reduce((sum, tp) => sum + (tp.quantite || 0), 0)
)

const headers = [
  { title: 'Date', key: 'date_prise_en_charge', sortable: true },
  { title: 'Nom', key: 'nom_particulier', sortable: true },
  { title: 'Prenom', key: 'prenom_particulier', sortable: true },
  { title: 'Nb pneus', key: 'nombre_pneus', sortable: true },
  { title: 'Detail', key: 'types_pneus', sortable: false },
  { title: 'Notes', key: 'notes', sortable: false },
]

function openDialog() {
  form.value = {
    nom_particulier: '',
    prenom_particulier: '',
    telephone: '',
    date_prise_en_charge: new Date().toISOString().slice(0, 10),
    types_pneus: [{ type: 'vl', quantite: 1 }],
    notes: '',
  }
  dialog.value = true
}

function addPneuType() {
  form.value.types_pneus.push({ type: 'vl', quantite: 1 })
}

function removePneuType(index) {
  form.value.types_pneus.splice(index, 1)
}

async function loadData() {
  loading.value = true
  try {
    const { data } = await api.get('/pneus-particuliers', {
      params: { annee: selectedAnnee.value },
    })
    prises.value = data.data || data
  } catch {
    /* ignore */
  } finally {
    loading.value = false
  }
}

async function loadCompteur() {
  try {
    const { data } = await api.get('/pneus-particuliers/compteur-annuel', {
      params: { annee: selectedAnnee.value },
    })
    compteur.value = data
  } catch {
    /* ignore */
  }
}

async function loadAll() {
  await Promise.all([loadData(), loadCompteur()])
}

async function save() {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  saving.value = true
  try {
    await api.post('/pneus-particuliers', {
      ...form.value,
      nombre_pneus: totalPneus.value,
    })
    dialog.value = false
    await loadAll()
  } catch {
    /* ignore */
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadAll()
})
</script>
