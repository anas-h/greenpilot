<template>
  <div>
    <v-row
      class="mb-4"
      align="center"
    >
      <v-col>
        <h2 class="text-h6">
          Tokens API
        </h2>
        <p class="text-body-2 text-grey">
          Gerez les tokens d'acces pour l'API publique GreenPilot.
        </p>
      </v-col>
      <v-col cols="auto">
        <v-btn
          color="primary"
          prepend-icon="mdi-plus"
          @click="openCreateDialog"
        >
          Nouveau token
        </v-btn>
      </v-col>
    </v-row>

    <!-- Token List -->
    <div style="overflow-x: auto">
      <v-data-table
        :headers="headers"
        :items="tokens"
        :loading="loading"
        density="compact"
      >
        <template #item.created_at="{ item }">
          {{ new Date(item.created_at).toLocaleDateString('fr-FR') }}
        </template>
        <template #item.last_used_at="{ item }">
          {{ item.last_used_at ? new Date(item.last_used_at).toLocaleDateString('fr-FR') : 'Jamais' }}
        </template>
        <template #item.expires_at="{ item }">
          <span
            v-if="item.expires_at"
            :class="isExpired(item.expires_at) ? 'text-error' : ''"
          >
            {{ new Date(item.expires_at).toLocaleDateString('fr-FR') }}
          </span>
          <span
            v-else
            class="text-grey"
          >Jamais</span>
        </template>
        <template #item.actions="{ item }">
          <v-btn
            icon="mdi-delete"
            size="x-small"
            variant="text"
            color="error"
            title="Revoquer"
            @click="confirmRevoke(item)"
          />
        </template>
      </v-data-table>
    </div>

    <!-- Usage Instructions -->
    <v-expansion-panels
      class="mt-6"
      variant="accordion"
    >
      <v-expansion-panel title="Instructions d'utilisation de l'API">
        <v-expansion-panel-text>
          <div class="text-body-2">
            <h3 class="text-subtitle-1 font-weight-bold mb-2">
              Authentification
            </h3>
            <p class="mb-2">
              Ajoutez le token dans le header <code>Authorization</code> de vos requetes :
            </p>
            <v-code class="pa-3 mb-4 d-block">
              Authorization: Bearer VOTRE_TOKEN
            </v-code>

            <h3 class="text-subtitle-1 font-weight-bold mb-2">
              Endpoints disponibles
            </h3>
            <div style="overflow-x: auto">
            <v-table
              density="compact"
              class="mb-4"
            >
              <thead>
                <tr>
                  <th>Methode</th>
                  <th>URL</th>
                  <th>Description</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>
                    <v-chip
                      size="x-small"
                      color="success"
                    >
                      GET
                    </v-chip>
                  </td>
                  <td><code>/api/public/v1/types-dechets</code></td>
                  <td>Liste des types de dechets</td>
                </tr>
                <tr>
                  <td>
                    <v-chip
                      size="x-small"
                      color="success"
                    >
                      GET
                    </v-chip>
                  </td>
                  <td><code>/api/public/v1/conteneurs</code></td>
                  <td>Liste des conteneurs et niveaux de remplissage</td>
                </tr>
                <tr>
                  <td>
                    <v-chip
                      size="x-small"
                      color="info"
                    >
                      POST
                    </v-chip>
                  </td>
                  <td><code>/api/public/v1/productions</code></td>
                  <td>Creer une production</td>
                </tr>
                <tr>
                  <td>
                    <v-chip
                      size="x-small"
                      color="info"
                    >
                      POST
                    </v-chip>
                  </td>
                  <td><code>/api/public/v1/productions/batch</code></td>
                  <td>Creer plusieurs productions</td>
                </tr>
                <tr>
                  <td>
                    <v-chip
                      size="x-small"
                      color="info"
                    >
                      POST
                    </v-chip>
                  </td>
                  <td><code>/api/public/v1/productions/suggest</code></td>
                  <td>Suggestions de mapping</td>
                </tr>
                <tr>
                  <td>
                    <v-chip
                      size="x-small"
                      color="success"
                    >
                      GET
                    </v-chip>
                  </td>
                  <td><code>/api/public/v1/enlevements</code></td>
                  <td>Liste des enlevements</td>
                </tr>
                <tr>
                  <td>
                    <v-chip
                      size="x-small"
                      color="success"
                    >
                      GET
                    </v-chip>
                  </td>
                  <td><code>/api/public/v1/registre</code></td>
                  <td>Export du registre des dechets</td>
                </tr>
              </tbody>
            </v-table>
            </div>

            <h3 class="text-subtitle-1 font-weight-bold mb-2">
              Limites
            </h3>
            <p>1 000 requetes par heure par token. Les headers <code>X-RateLimit-Limit</code> et <code>X-RateLimit-Remaining</code> sont inclus dans chaque reponse.</p>
          </div>
        </v-expansion-panel-text>
      </v-expansion-panel>
    </v-expansion-panels>

    <!-- Create Dialog -->
    <v-dialog
      v-model="createDialog"
      max-width="500"
    >
      <v-card>
        <v-card-title>Nouveau token API</v-card-title>
        <v-card-text>
          <v-form ref="createFormRef">
            <v-text-field
              v-model="createForm.name"
              label="Nom du token *"
              placeholder="Ex: Integration DMS, ERP Sync..."
              :rules="[v => !!v || 'Le nom est requis']"
            />
            <v-select
              v-model="createForm.expires_in"
              :items="expirationOptions"
              item-title="label"
              item-value="value"
              label="Expiration"
            />
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="createDialog = false">
            Annuler
          </v-btn>
          <v-btn
            color="primary"
            :loading="creating"
            @click="createToken"
          >
            Creer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Token Created Dialog (show token once) -->
    <v-dialog
      v-model="tokenCreatedDialog"
      max-width="600"
      persistent
    >
      <v-card>
        <v-card-title class="text-success">
          Token cree avec succes
        </v-card-title>
        <v-card-text>
          <v-alert
            type="warning"
            variant="tonal"
            class="mb-4"
          >
            Copiez ce token maintenant. Il ne sera plus affiche par la suite.
          </v-alert>
          <v-text-field
            :model-value="newTokenValue"
            readonly
            variant="outlined"
            append-inner-icon="mdi-content-copy"
            @click:append-inner="copyToken"
          />
          <p
            v-if="copied"
            class="text-success text-caption mt-1"
          >
            Copie dans le presse-papiers !
          </p>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            color="primary"
            @click="tokenCreatedDialog = false"
          >
            J'ai copie le token
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Revoke Confirmation -->
    <v-dialog
      v-model="revokeDialog"
      max-width="400"
    >
      <v-card>
        <v-card-title>Revoquer le token</v-card-title>
        <v-card-text>
          Etes-vous sur de vouloir revoquer le token
          <strong>{{ revokeTarget?.name }}</strong> ?
          Cette action est irreversible.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="revokeDialog = false">
            Annuler
          </v-btn>
          <v-btn
            color="error"
            :loading="revoking"
            @click="revokeToken"
          >
            Revoquer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../../services/api'

const loading = ref(false)
const creating = ref(false)
const revoking = ref(false)
const copied = ref(false)
const tokens = ref([])

const createDialog = ref(false)
const tokenCreatedDialog = ref(false)
const revokeDialog = ref(false)
const createFormRef = ref(null)
const newTokenValue = ref('')
const revokeTarget = ref(null)

const createForm = ref({
  name: '',
  expires_in: null,
})

const expirationOptions = [
  { label: 'Pas d\'expiration', value: null },
  { label: '30 jours', value: 30 },
  { label: '90 jours', value: 90 },
  { label: '180 jours', value: 180 },
  { label: '1 an', value: 365 },
]

const headers = [
  { title: 'Nom', key: 'name', sortable: true },
  { title: 'Cree le', key: 'created_at', sortable: true },
  { title: 'Derniere utilisation', key: 'last_used_at', sortable: true },
  { title: 'Expiration', key: 'expires_at', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' },
]

function isExpired(dateStr) {
  return new Date(dateStr) < new Date()
}

function openCreateDialog() {
  createForm.value = { name: '', expires_in: null }
  createDialog.value = true
}

async function loadTokens() {
  loading.value = true
  try {
    const { data } = await api.get('/api-tokens')
    tokens.value = data.data || data
  } catch {
    /* ignore */
  } finally {
    loading.value = false
  }
}

async function createToken() {
  const { valid } = await createFormRef.value.validate()
  if (!valid) return

  creating.value = true
  try {
    const { data } = await api.post('/api-tokens', createForm.value)
    newTokenValue.value = data.token || data.plain_text_token || ''
    createDialog.value = false
    tokenCreatedDialog.value = true
    copied.value = false
    await loadTokens()
  } catch {
    /* ignore */
  } finally {
    creating.value = false
  }
}

async function copyToken() {
  try {
    await navigator.clipboard.writeText(newTokenValue.value)
    copied.value = true
  } catch {
    /* fallback */
    const el = document.createElement('textarea')
    el.value = newTokenValue.value
    document.body.appendChild(el)
    el.select()
    document.execCommand('copy')
    document.body.removeChild(el)
    copied.value = true
  }
}

function confirmRevoke(token) {
  revokeTarget.value = token
  revokeDialog.value = true
}

async function revokeToken() {
  if (!revokeTarget.value) return

  revoking.value = true
  try {
    await api.delete(`/api-tokens/${revokeTarget.value.id}`)
    revokeDialog.value = false
    revokeTarget.value = null
    await loadTokens()
  } catch {
    /* ignore */
  } finally {
    revoking.value = false
  }
}

onMounted(() => {
  loadTokens()
})
</script>
