<template>
  <v-form
    :disabled="loading"
    @submit.prevent="save"
  >
    <v-row>
      <v-col
        cols="12"
        md="6"
      >
        <v-text-field
          v-model="form.nom"
          label="Nom du garage"
        />
        <v-text-field
          v-model="form.siret_etablissement"
          label="SIRET etablissement"
          maxlength="14"
        />
        <v-text-field
          v-model="form.adresse"
          label="Adresse"
        />
        <v-row dense>
          <v-col cols="4">
            <v-text-field
              v-model="form.code_postal"
              label="Code postal"
            />
          </v-col>
          <v-col cols="8">
            <v-text-field
              v-model="form.ville"
              label="Ville"
            />
          </v-col>
        </v-row>
        <v-text-field
          v-model="form.telephone"
          label="Telephone"
        />
      </v-col>
      <v-col
        cols="12"
        md="6"
      >
        <v-text-field
          v-model="form.surface_atelier"
          label="Surface atelier (m2)"
          type="number"
        />
        <v-select
          v-model="form.regime_icpe"
          label="Regime ICPE"
          :items="['non_classe', 'declaration', 'enregistrement']"
          clearable
        />
        <p class="text-subtitle-2 mb-2">
          Activites
        </p>
        <v-chip-group
          v-model="form.activites"
          multiple
          column
        >
          <v-chip
            v-for="a in ['mecanique', 'carrosserie', 'peinture', 'pneumatique']"
            :key="a"
            :value="a"
            filter
            variant="outlined"
            color="primary"
          >
            {{ a }}
          </v-chip>
        </v-chip-group>
      </v-col>
    </v-row>
    <v-btn
      type="submit"
      color="primary"
      :loading="loading"
      class="mt-4"
    >
      Enregistrer
    </v-btn>
  </v-form>
</template>
<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { useGarageStore } from '../../../stores/garage'
import { useUiStore } from '../../../stores/ui'

const garageStore = useGarageStore()
const uiStore = useUiStore()
const loading = ref(false)
const form = reactive({ nom: '', siret_etablissement: '', adresse: '', code_postal: '', ville: '', telephone: '', surface_atelier: null, regime_icpe: null, activites: [] })

function loadGarage() {
  const g = garageStore.currentGarage
  if (g) Object.assign(form, { nom: g.nom, siret_etablissement: g.siret_etablissement, adresse: g.adresse, code_postal: g.code_postal, ville: g.ville, telephone: g.telephone, surface_atelier: g.surface_atelier, regime_icpe: g.regime_icpe, activites: g.activites || [] })
}

async function save() {
  loading.value = true
  try {
    await garageStore.updateGarage(garageStore.currentGarageId, form)
    uiStore.showSuccess('Garage mis a jour')
  } catch (e) {
    uiStore.showError(e.response?.data?.message || 'Erreur')
  } finally {
    loading.value = false
  }
}

watch(() => garageStore.currentGarageId, loadGarage)
onMounted(loadGarage)
</script>
