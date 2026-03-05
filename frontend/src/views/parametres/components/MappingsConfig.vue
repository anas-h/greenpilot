<template>
  <div>
    <div class="d-flex align-center mb-4">
      <h3 class="text-h6">
        Mappings operations
      </h3>
      <v-spacer />
      <v-btn
        color="primary"
        prepend-icon="mdi-plus"
        @click="openDialog()"
      >
        Ajouter
      </v-btn>
    </div>
    <v-data-table
      :headers="headers"
      :items="mappings"
      :loading="loading"
      density="comfortable"
    >
      <template #item.actif="{ item }">
        <v-switch
          :model-value="item.actif"
          color="primary"
          hide-details
          density="compact"
          @update:model-value="toggleActif(item)"
        />
      </template>
      <template #item.actions="{ item }">
        <v-btn
          icon
          size="small"
          variant="text"
          title="Modifier"
          @click="openDialog(item)"
        >
          <v-icon size="small">
            mdi-pencil
          </v-icon>
        </v-btn>
        <v-btn
          icon
          size="small"
          variant="text"
          color="error"
          title="Supprimer"
          @click="deleteMapping(item.id)"
        >
          <v-icon size="small">
            mdi-delete
          </v-icon>
        </v-btn>
      </template>
    </v-data-table>
    <v-dialog
      v-model="dialog"
      max-width="500"
    >
      <v-card>
        <v-card-title>{{ editing ? 'Modifier' : 'Ajouter' }} un mapping</v-card-title>
        <v-card-text>
          <v-text-field
            v-model="form.mot_cle"
            label="Mot-cle"
          />
          <v-select
            v-model="form.type_dechet_id"
            label="Type de dechet"
            :items="typesDechets"
            item-title="denomination"
            item-value="id"
          />
          <v-text-field
            v-model="form.quantite_defaut"
            label="Quantite par defaut"
            type="number"
            step="0.1"
          />
          <v-select
            v-model="form.unite"
            label="Unite"
            :items="['kg', 'litres', 'unites']"
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="text"
            @click="dialog = false"
          >
            Annuler
          </v-btn>
          <v-btn
            color="primary"
            :loading="saving"
            @click="saveMapping"
          >
            Enregistrer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <ConfirmDialog
      v-model="confirmDialogOpen"
      title="Confirmer la suppression"
      message="Supprimer ce mapping ?"
      confirm-text="Supprimer"
      @confirm="doDelete"
    />
  </div>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useUiStore } from '../../../stores/ui'
import api from '../../../services/api'
import ConfirmDialog from '../../../components/ConfirmDialog.vue'

const uiStore = useUiStore()
const mappings = ref([])
const typesDechets = ref([])
const loading = ref(false)
const dialog = ref(false)
const saving = ref(false)
const editing = ref(null)
const confirmDialogOpen = ref(false)
const pendingDeleteId = ref(null)
const form = reactive({ mot_cle: '', type_dechet_id: null, quantite_defaut: 0, unite: 'kg' })

const headers = [
  { title: 'Mot-cle', key: 'mot_cle' },
  { title: 'Type de dechet', key: 'type_dechet.denomination' },
  { title: 'Quantite', key: 'quantite_defaut' },
  { title: 'Unite', key: 'unite' },
  { title: 'Actif', key: 'actif', width: 100 },
  { title: 'Actions', key: 'actions', sortable: false },
]

function openDialog(item = null) {
  editing.value = item
  if (item) Object.assign(form, { mot_cle: item.mot_cle, type_dechet_id: item.type_dechet_id, quantite_defaut: item.quantite_defaut, unite: item.unite })
  else Object.assign(form, { mot_cle: '', type_dechet_id: null, quantite_defaut: 0, unite: 'kg' })
  dialog.value = true
}

async function fetchData() {
  loading.value = true
  try {
    const [m, t] = await Promise.all([api.get('/mappings'), api.get('/types-dechets')])
    mappings.value = m.data.data || m.data
    typesDechets.value = t.data.data || t.data
  } catch {
    uiStore.showError('Erreur lors du chargement des mappings.')
  }
  loading.value = false
}

async function saveMapping() {
  saving.value = true
  try {
    if (editing.value) await api.put(`/mappings/${editing.value.id}`, form)
    else await api.post('/mappings', form)
    dialog.value = false
    uiStore.showSuccess('Mapping enregistre')
    fetchData()
  } catch (e) { uiStore.showError(e.response?.data?.message || 'Erreur') }
  saving.value = false
}

async function toggleActif(item) {
  try { await api.put(`/mappings/${item.id}`, { ...item, actif: !item.actif }) ; fetchData() } catch { uiStore.showError('Erreur lors de la mise a jour du mapping.') }
}

function deleteMapping(id) {
  pendingDeleteId.value = id
  confirmDialogOpen.value = true
}

async function doDelete() {
  confirmDialogOpen.value = false
  try {
    await api.delete(`/mappings/${pendingDeleteId.value}`)
    uiStore.showSuccess('Supprime')
    fetchData()
  } catch {
    uiStore.showError('Erreur lors de la suppression du mapping.')
  } finally {
    pendingDeleteId.value = null
  }
}

onMounted(fetchData)
</script>
