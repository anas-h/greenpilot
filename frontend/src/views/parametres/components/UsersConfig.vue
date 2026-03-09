<template>
  <div>
    <div class="d-flex flex-wrap align-center mb-4">
      <h3 class="text-h6">
        Utilisateurs
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
    <div style="overflow-x: auto">
    <v-data-table
      :headers="headers"
      :items="users"
      :loading="loading"
      density="comfortable"
    >
      <template #item.role="{ item }">
        <v-chip
          size="small"
          :color="roleColor(item.role)"
        >
          {{ item.role }}
        </v-chip>
      </template>
      <template #item.actif="{ item }">
        <v-icon :color="item.actif ? 'success' : 'grey'">
          {{ item.actif ? 'mdi-check-circle' : 'mdi-close-circle' }}
        </v-icon>
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
          @click="deleteUser(item.id)"
        >
          <v-icon size="small">
            mdi-delete
          </v-icon>
        </v-btn>
      </template>
    </v-data-table>
    </div>
    <v-dialog
      v-model="dialog"
      max-width="500"
      :fullscreen="mobile"
    >
      <v-card>
        <v-card-title>{{ editingUser ? 'Modifier' : 'Ajouter' }} un utilisateur</v-card-title>
        <v-card-text>
          <v-text-field
            v-model="userForm.prenom"
            label="Prenom"
          />
          <v-text-field
            v-model="userForm.nom"
            label="Nom"
          />
          <v-text-field
            v-model="userForm.email"
            label="Email"
            type="email"
          />
          <v-text-field
            v-model="userForm.telephone"
            label="Telephone"
          />
          <v-select
            v-model="userForm.role"
            label="Role"
            :items="['admin', 'chef_atelier', 'mecanicien', 'comptable']"
          />
          <v-select
            v-model="userForm.garages_ids"
            label="Garages accessibles"
            :items="garageItems"
            multiple
            chips
            closable-chips
            hint="Selectionnez les garages auxquels cet utilisateur aura acces"
            persistent-hint
          />
          <v-text-field
            v-if="!editingUser"
            v-model="userForm.password"
            label="Mot de passe"
            type="password"
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
            @click="saveUser"
          >
            Enregistrer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <ConfirmDialog
      v-model="confirmDialogOpen"
      title="Confirmer la suppression"
      message="Supprimer cet utilisateur ?"
      confirm-text="Supprimer"
      @confirm="doDelete"
    />
  </div>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useDisplay } from 'vuetify'
import { useUiStore } from '../../../stores/ui'
import { useGarageStore } from '../../../stores/garage'
import api from '../../../services/api'
import ConfirmDialog from '../../../components/ConfirmDialog.vue'

const { mobile } = useDisplay()
const uiStore = useUiStore()
const garageStore = useGarageStore()
const users = ref([])
const loading = ref(false)
const dialog = ref(false)
const saving = ref(false)
const editingUser = ref(null)
const confirmDialogOpen = ref(false)
const pendingDeleteId = ref(null)
const userForm = reactive({ nom: '', prenom: '', email: '', telephone: '', role: 'mecanicien', password: '', garages_ids: [] })

const headers = [
  { title: 'Prenom', key: 'prenom' },
  { title: 'Nom', key: 'nom' },
  { title: 'Email', key: 'email' },
  { title: 'Role', key: 'role' },
  { title: 'Actif', key: 'actif' },
  { title: 'Actions', key: 'actions', sortable: false },
]

function roleColor(role) {
  return { admin: 'primary', chef_atelier: 'info', mecanicien: 'success', comptable: 'warning' }[role] || 'grey'
}

function openDialog(user = null) {
  editingUser.value = user
  if (user) {
    const garageIds = (user.garages || []).map(g => g.id)
    Object.assign(userForm, { nom: user.nom, prenom: user.prenom, email: user.email, telephone: user.telephone, role: user.role, password: '', garages_ids: garageIds })
  } else {
    Object.assign(userForm, { nom: '', prenom: '', email: '', telephone: '', role: 'mecanicien', password: '', garages_ids: [] })
  }
  dialog.value = true
}

async function fetchUsers() {
  loading.value = true
  try {
    const { data } = await api.get('/users')
    users.value = data.data || data
  } catch {
    uiStore.showError('Erreur lors du chargement des utilisateurs.')
  }
  loading.value = false
}

async function saveUser() {
  saving.value = true
  try {
    if (editingUser.value) {
      await api.put(`/users/${editingUser.value.id}`, userForm)
    } else {
      await api.post('/users', userForm)
    }
    uiStore.showSuccess('Utilisateur enregistre')
    dialog.value = false
    fetchUsers()
  } catch (e) {
    uiStore.showError(e.response?.data?.message || 'Erreur')
  }
  saving.value = false
}

function deleteUser(id) {
  pendingDeleteId.value = id
  confirmDialogOpen.value = true
}

async function doDelete() {
  confirmDialogOpen.value = false
  try {
    await api.delete(`/users/${pendingDeleteId.value}`)
    uiStore.showSuccess('Utilisateur supprime')
    fetchUsers()
  } catch (e) {
    uiStore.showError(e.response?.data?.message || 'Erreur')
  } finally {
    pendingDeleteId.value = null
  }
}

const garageItems = ref([])

async function loadGarages() {
  // Garages already available in garageStore
  garageItems.value = garageStore.garages.map(g => ({ title: g.nom, value: g.id }))
}

onMounted(() => {
  fetchUsers()
  loadGarages()
})
</script>
