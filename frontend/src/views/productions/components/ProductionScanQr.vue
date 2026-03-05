<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      <v-icon
        color="primary"
        class="mr-2"
      >
        mdi-qrcode-scan
      </v-icon>
      Scan QR - Saisie rapide
    </v-card-title>

    <v-card-text>
      <v-row>
        <v-col
          cols="12"
          md="6"
        >
          <div
            v-if="!scanned"
            class="scanner-area"
          >
            <div
              v-if="cameraError"
              class="text-center pa-8"
            >
              <v-icon
                size="64"
                color="grey-lighten-1"
                class="mb-3"
              >
                mdi-camera-off
              </v-icon>
              <p class="text-body-1 text-grey mb-4">
                Camera non disponible
              </p>
              <v-text-field
                v-model="manualQr"
                label="Saisir le code QR manuellement"
                prepend-inner-icon="mdi-qrcode"
                @keyup.enter="onManualScan"
              />
              <v-btn
                color="primary"
                class="mt-2"
                @click="onManualScan"
              >
                Rechercher
              </v-btn>
            </div>
            <div v-else>
              <qrcode-stream
                @detect="onDecode"
                @error="onCameraError"
              />
              <p class="text-caption text-center mt-2 text-grey">
                Scannez le QR code d'un conteneur
              </p>
            </div>
          </div>

          <div
            v-else
            class="text-center pa-4"
          >
            <v-icon
              size="64"
              color="success"
              class="mb-3"
            >
              mdi-check-circle
            </v-icon>
            <p class="text-h6 text-success mb-2">
              Conteneur identifie
            </p>
            <v-chip
              color="primary"
              size="large"
              class="mb-4"
            >
              {{ conteneurInfo?.nom }}
            </v-chip>
            <br>
            <v-btn
              variant="outlined"
              size="small"
              @click="resetScan"
            >
              Scanner un autre conteneur
            </v-btn>
          </div>
        </v-col>

        <v-col
          cols="12"
          md="6"
        >
          <v-form
            ref="formRef"
            @submit.prevent="save"
          >
            <v-alert
              v-if="scanned && conteneurInfo"
              type="info"
              variant="tonal"
              density="compact"
              class="mb-4"
            >
              <div class="font-weight-medium">
                {{ conteneurInfo.nom }}
              </div>
              <div class="text-caption">
                Type: {{ conteneurInfo.type_dechet?.denomination || '-' }}
                | Remplissage: {{ Math.round(conteneurInfo.pourcentage || 0) }}%
              </div>
            </v-alert>

            <v-select
              v-model="form.type_dechet_id"
              :items="typesDechets"
              item-title="denomination"
              item-value="id"
              label="Type de dechet *"
              :rules="[rules.required]"
              :disabled="!!conteneurInfo?.type_dechet_id"
            />

            <v-text-field
              v-model.number="form.quantite"
              label="Quantite *"
              type="number"
              step="0.001"
              min="0.001"
              :rules="[rules.required, rules.positive]"
              :suffix="form.unite"
              autofocus
            />

            <v-text-field
              v-model="form.date_production"
              label="Date"
              type="date"
              :rules="[rules.required]"
            />

            <v-text-field
              v-model="form.vehicule_info"
              label="Vehicule (optionnel)"
              placeholder="Immatriculation..."
            />

            <v-btn
              color="primary"
              size="x-large"
              block
              :loading="saving"
              :disabled="!form.type_dechet_id || !form.quantite"
              class="mt-4"
              @click="save"
            >
              <v-icon
                start
                size="large"
              >
                mdi-content-save
              </v-icon>
              Enregistrer la production
            </v-btn>
          </v-form>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { QrcodeStream } from 'vue-qrcode-reader'
import api from '../../../services/api'
import { useUiStore } from '../../../stores/ui'
import { required, positiveNumber } from '../../../services/validators'

const emit = defineEmits(['saved'])

const uiStore = useUiStore()
const formRef = ref(null)
const saving = ref(false)
const scanned = ref(false)
const cameraError = ref(false)
const manualQr = ref('')
const conteneurInfo = ref(null)
const typesDechets = ref([])

const form = ref({
  type_dechet_id: null,
  conteneur_id: null,
  quantite: null,
  unite: 'kg',
  date_production: new Date().toISOString().substring(0, 10),
  source_type: 'scan_qr',
  vehicule_info: '',
})

const rules = { required, positive: positiveNumber }

function onDecode(detectedCodes) {
  if (detectedCodes && detectedCodes.length > 0) {
    const code = detectedCodes[0].rawValue
    lookupConteneur(code)
  }
}

function onCameraError() {
  cameraError.value = true
}

function onManualScan() {
  if (manualQr.value) {
    lookupConteneur(manualQr.value)
  }
}

async function lookupConteneur(codeQr) {
  try {
    const { data } = await api.get('/conteneurs', { params: { code_qr: codeQr } })
    const list = data.data || data || []
    const cont = list.find(c => c.code_qr === codeQr)

    if (cont) {
      conteneurInfo.value = {
        ...cont,
        pourcentage: cont.capacite > 0 ? (cont.niveau_actuel / cont.capacite) * 100 : 0,
      }
      form.value.conteneur_id = cont.id
      form.value.type_dechet_id = cont.type_dechet_id
      form.value.unite = cont.unite || 'kg'
      scanned.value = true
    } else {
      uiStore.showWarning('Conteneur non trouve pour ce QR code.')
    }
  } catch (e) {
    uiStore.showError('Erreur lors de la recherche du conteneur.')
  }
}

function resetScan() {
  scanned.value = false
  conteneurInfo.value = null
  form.value = {
    type_dechet_id: null,
    conteneur_id: null,
    quantite: null,
    unite: 'kg',
    date_production: new Date().toISOString().substring(0, 10),
    source_type: 'scan_qr',
    vehicule_info: '',
  }
}

async function save() {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  saving.value = true
  try {
    await api.post('/productions', form.value)
    uiStore.showSuccess('Production enregistree depuis le scan.')
    emit('saved')
    resetScan()
  } catch (e) {
    const msg = e.response?.data?.message || 'Erreur lors de l\'enregistrement.'
    uiStore.showError(msg)
  } finally {
    saving.value = false
  }
}

async function fetchTypesDechets() {
  try {
    const { data } = await api.get('/types-dechets')
    typesDechets.value = data.data || data || []
  } catch {
    uiStore.showError('Erreur lors du chargement des types de dechets.')
  }
}

onMounted(fetchTypesDechets)
</script>

<style scoped>
.scanner-area {
  min-height: 300px;
  border: 2px dashed #ccc;
  border-radius: 12px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
