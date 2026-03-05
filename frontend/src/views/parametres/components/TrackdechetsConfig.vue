<template>
  <div>
    <h3 class="text-h6 mb-4">
      Configuration Trackdechets
    </h3>
    <v-alert
      v-if="!config.actif"
      type="warning"
      variant="tonal"
      class="mb-4"
    >
      Integration Trackdechets non active. Configurez vos parametres ci-dessous.
    </v-alert>
    <v-form
      :disabled="loading"
      @submit.prevent="save"
    >
      <v-text-field
        v-model="config.api_token"
        label="Token API"
        type="password"
        hint="Genere depuis Mon compte > Integration API sur Trackdechets"
        persistent-hint
      />
      <v-select
        v-model="config.environnement"
        label="Environnement"
        :items="['sandbox', 'production']"
      />
      <v-switch
        v-model="config.actif"
        label="Activer l'integration"
        color="primary"
      />
      <div class="d-flex ga-2 mt-4">
        <v-btn
          type="submit"
          color="primary"
          :loading="loading"
        >
          Enregistrer
        </v-btn>
        <v-btn
          variant="outlined"
          color="info"
          :loading="testing"
          @click="testConnection"
        >
          Tester la connexion
        </v-btn>
      </div>
    </v-form>
    <v-alert
      v-if="testResult"
      :type="testResult.success ? 'success' : 'error'"
      variant="tonal"
      class="mt-4"
    >
      {{ testResult.message }}
    </v-alert>
  </div>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useUiStore } from '../../../stores/ui'
import api from '../../../services/api'

const uiStore = useUiStore()
const loading = ref(false)
const testing = ref(false)
const testResult = ref(null)
const config = reactive({ api_token: '', environnement: 'sandbox', actif: false })

async function fetchConfig() {
  try {
    const { data } = await api.get('/config-trackdechets')
    if (data.config) Object.assign(config, data.config)
  } catch { /* ignore */ }
}

async function save() {
  loading.value = true
  try {
    await api.post('/config-trackdechets', config)
    uiStore.showSuccess('Configuration sauvegardee')
  } catch (e) {
    uiStore.showError(e.response?.data?.message || 'Erreur')
  }
  loading.value = false
}

async function testConnection() {
  testing.value = true
  testResult.value = null
  try {
    const { data } = await api.post('/config-trackdechets/test')
    testResult.value = data
  } catch (e) {
    testResult.value = { success: false, message: e.response?.data?.message || 'Echec de la connexion' }
  }
  testing.value = false
}

onMounted(fetchConfig)
</script>
