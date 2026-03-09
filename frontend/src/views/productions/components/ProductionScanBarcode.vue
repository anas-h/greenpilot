<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      <v-icon
        color="primary"
        class="mr-2"
      >
        mdi-barcode-scan
      </v-icon>
      Scan Produit - Saisie rapide
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
                v-model="manualEan"
                label="Saisir le code-barres manuellement"
                prepend-inner-icon="mdi-barcode"
                @keyup.enter="onManualScan"
              />
              <v-btn
                color="primary"
                class="mt-2"
                :loading="scanning"
                @click="onManualScan"
              >
                Rechercher
              </v-btn>
            </div>
            <div v-else>
              <qrcode-stream
                :formats="['ean_13', 'ean_8', 'upc_a', 'code_128']"
                @detect="onDecode"
                @error="onCameraError"
              />
              <p class="text-caption text-center mt-2 text-grey">
                Scannez le code-barres d'un produit
              </p>
            </div>
          </div>

          <div
            v-else
            class="pa-4"
          >
            <div
              v-if="productInfo"
              class="text-center"
            >
              <v-icon
                size="64"
                color="success"
                class="mb-3"
              >
                mdi-check-circle
              </v-icon>
              <p class="text-h6 text-success mb-2">
                Produit identifie
              </p>
              <v-card
                variant="tonal"
                color="primary"
                class="mb-4 text-left"
              >
                <v-card-text>
                  <div class="font-weight-medium text-body-1">
                    {{ productInfo.name }}
                  </div>
                  <div
                    v-if="productInfo.brand"
                    class="text-body-2 text-grey mt-1"
                  >
                    Marque: {{ productInfo.brand }}
                  </div>
                  <div
                    v-if="productInfo.category"
                    class="text-body-2 text-grey mt-1"
                  >
                    Categorie: {{ productInfo.category }}
                  </div>
                  <div
                    v-if="productInfo.suggested_waste_type"
                    class="text-body-2 mt-2"
                  >
                    <v-chip
                      size="small"
                      color="success"
                      variant="tonal"
                    >
                      Type suggere: {{ productInfo.suggested_waste_type }}
                    </v-chip>
                  </div>
                </v-card-text>
              </v-card>
            </div>

            <div
              v-else
              class="text-center"
            >
              <v-icon
                size="64"
                color="warning"
                class="mb-3"
              >
                mdi-alert-circle
              </v-icon>
              <p class="text-h6 text-warning mb-2">
                Produit non trouve
              </p>
              <p class="text-body-2 text-grey mb-4">
                Le code-barres {{ scannedEan }} n'a pas ete trouve. Vous pouvez remplir le formulaire manuellement.
              </p>
            </div>

            <div class="text-center">
              <v-btn
                variant="outlined"
                size="small"
                @click="resetScan"
              >
                Scanner un autre produit
              </v-btn>
            </div>
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
              v-if="scanned && productInfo"
              type="info"
              variant="tonal"
              density="compact"
              class="mb-4"
            >
              <div class="font-weight-medium">
                {{ productInfo.name }}
              </div>
              <div class="text-caption">
                {{ productInfo.brand ? `Marque: ${productInfo.brand}` : '' }}
                {{ productInfo.category ? `| Categorie: ${productInfo.category}` : '' }}
              </div>
            </v-alert>

            <v-select
              v-model="form.type_dechet_id"
              :items="typesDechets"
              item-title="denomination"
              item-value="id"
              label="Type de dechet *"
              :rules="[rules.required]"
              @update:model-value="onTypeChanged"
            />

            <v-select
              v-model="form.conteneur_id"
              :items="filteredConteneurs"
              item-title="nom"
              item-value="id"
              label="Conteneur"
              clearable
            >
              <template #item="{ item, props: itemProps }">
                <v-list-item v-bind="itemProps">
                  <template #append>
                    <span class="text-caption text-grey">
                      {{ Math.round(item.raw.pourcentage || 0) }}% plein
                    </span>
                  </template>
                </v-list-item>
              </template>
            </v-select>

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

            <v-text-field
              v-model="form.reference_externe"
              label="Reference externe"
              placeholder="Code EAN..."
            />

            <v-textarea
              v-model="form.notes"
              label="Notes"
              rows="2"
              auto-grow
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
import { ref, computed, onMounted } from 'vue'
import { QrcodeStream } from 'vue-qrcode-reader'
import api from '../../../services/api'
import { useUiStore } from '../../../stores/ui'
import { required, positiveNumber } from '../../../services/validators'

const emit = defineEmits(['saved'])

const uiStore = useUiStore()
const formRef = ref(null)
const saving = ref(false)
const scanning = ref(false)
const scanned = ref(false)
const cameraError = ref(false)
const manualEan = ref('')
const scannedEan = ref('')
const productInfo = ref(null)
const typesDechets = ref([])
const conteneurs = ref([])

const form = ref({
  type_dechet_id: null,
  conteneur_id: null,
  quantite: null,
  unite: 'kg',
  date_production: new Date().toISOString().substring(0, 10),
  source_type: 'scan_barcode',
  vehicule_info: '',
  reference_externe: '',
  notes: '',
})

const rules = { required, positive: positiveNumber }

const filteredConteneurs = computed(() => {
  if (!form.value.type_dechet_id) return conteneurs.value
  return conteneurs.value.filter(c =>
    c.type_dechet_id === form.value.type_dechet_id || c.mixte
  )
})

function onTypeChanged(typeId) {
  const type = typesDechets.value.find(t => t.id === typeId)
  if (type && type.unite_mesure) {
    form.value.unite = type.unite_mesure
  }
  // Reset conteneur if not compatible
  if (form.value.conteneur_id) {
    const cont = conteneurs.value.find(c => c.id === form.value.conteneur_id)
    if (cont && cont.type_dechet_id !== typeId && !cont.mixte) {
      form.value.conteneur_id = null
    }
  }
}

function onDecode(detectedCodes) {
  if (detectedCodes && detectedCodes.length > 0) {
    const code = detectedCodes[0].rawValue
    lookupProduct(code)
  }
}

function onCameraError() {
  cameraError.value = true
}

function onManualScan() {
  if (manualEan.value) {
    lookupProduct(manualEan.value.trim())
  }
}

async function lookupProduct(ean) {
  scanning.value = true
  scannedEan.value = ean
  try {
    const { data } = await api.post('/productions/scan-barcode', { ean })
    const product = data.data || data

    if (product && product.name) {
      productInfo.value = product

      // Pre-fill form from product info
      if (product.type_dechet_id) {
        form.value.type_dechet_id = product.type_dechet_id
        onTypeChanged(product.type_dechet_id)
      }
      if (product.estimated_quantity) {
        form.value.quantite = product.estimated_quantity
      }
      form.value.reference_externe = ean
      form.value.notes = `Produit: ${product.name}${product.brand ? ` (${product.brand})` : ''}`
    } else {
      productInfo.value = null
      form.value.reference_externe = ean
    }

    scanned.value = true
  } catch (e) {
    if (e.response?.status === 404) {
      productInfo.value = null
      form.value.reference_externe = ean
      scanned.value = true
    } else {
      uiStore.showError('Erreur lors de la recherche du produit.')
    }
  } finally {
    scanning.value = false
  }
}

function resetScan() {
  scanned.value = false
  scannedEan.value = ''
  productInfo.value = null
  form.value = {
    type_dechet_id: null,
    conteneur_id: null,
    quantite: null,
    unite: 'kg',
    date_production: new Date().toISOString().substring(0, 10),
    source_type: 'scan_barcode',
    vehicule_info: '',
    reference_externe: '',
    notes: '',
  }
}

async function save() {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  saving.value = true
  try {
    await api.post('/productions', form.value)
    uiStore.showSuccess('Production enregistree depuis le scan produit.')
    emit('saved')
    resetScan()
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
    conteneurs.value = (contRes.data.data || contRes.data || []).map(c => ({
      ...c,
      pourcentage: c.capacite > 0 ? (c.niveau_actuel / c.capacite) * 100 : 0,
    }))
  } catch {
    uiStore.showError('Erreur lors du chargement des donnees.')
  }
}

onMounted(fetchData)
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
