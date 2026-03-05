<template>
  <v-dialog
    :model-value="modelValue"
    max-width="600"
    persistent
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <v-card>
      <v-card-title class="d-flex align-center justify-space-between">
        <span>{{ isEdit ? 'Modifier le conteneur' : 'Nouveau conteneur' }}</span>
        <v-btn
          icon="mdi-close"
          variant="text"
          title="Fermer"
          @click="tryClose"
        />
      </v-card-title>

      <v-card-text>
        <v-form
          ref="formRef"
          :disabled="loading"
          @submit.prevent="handleSubmit"
        >
          <v-text-field
            v-model="form.nom"
            label="Nom du conteneur"
            :rules="[rules.required]"
            class="mb-2"
          />

          <v-select
            v-model="form.type_dechet_id"
            label="Type de dechet"
            :items="typeItems"
            item-title="text"
            item-value="value"
            :rules="[rules.required]"
            class="mb-2"
          >
            <template #item="{ item, props }">
              <v-list-item v-bind="props">
                <template #prepend>
                  <v-icon
                    :color="item.raw.dangereux ? 'error' : 'success'"
                    size="small"
                  >
                    {{ item.raw.dangereux ? 'mdi-alert-circle' : 'mdi-recycle' }}
                  </v-icon>
                </template>
              </v-list-item>
            </template>
          </v-select>

          <v-row>
            <v-col cols="6">
              <v-text-field
                v-model.number="form.capacite"
                label="Capacite"
                type="number"
                min="0.01"
                step="0.01"
                :rules="[rules.required, rules.positiveNumber]"
              />
            </v-col>
            <v-col cols="6">
              <v-select
                v-model="form.unite"
                label="Unite"
                :items="uniteOptions"
                :rules="[rules.required]"
              />
            </v-col>
          </v-row>

          <v-text-field
            v-model="form.emplacement"
            label="Emplacement"
            prepend-inner-icon="mdi-map-marker"
            class="mb-2"
          />

          <v-text-field
            v-model="form.date_mise_en_service"
            label="Date de mise en service"
            type="date"
            class="mb-2"
          />

          <v-switch
            v-model="form.mixte"
            label="Conteneur mixte (plusieurs types de dechets)"
            color="primary"
            hide-details
            class="mb-4"
          />
        </v-form>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn
          variant="text"
          :disabled="loading"
          @click="tryClose"
        >
          Annuler
        </v-btn>
        <v-btn
          color="primary"
          :loading="loading"
          @click="handleSubmit"
        >
          {{ isEdit ? 'Mettre a jour' : 'Creer' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <ConfirmDialog
    v-model="showDirtyWarning"
    title="Modifications non sauvegardees"
    message="Vous avez des modifications non sauvegardees. Voulez-vous vraiment fermer ?"
    confirm-text="Fermer sans sauvegarder"
    confirm-color="warning"
    @confirm="forceClose"
  />
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { useDechetsStore } from '../../../stores/dechets'
import { useFormDirty } from '../../../composables/useFormDirty'
import ConfirmDialog from '../../../components/ConfirmDialog.vue'
import { required, positiveNumber } from '../../../services/validators'

const props = defineProps({
  modelValue: Boolean,
  conteneur: {
    type: Object,
    default: null,
  },
  types: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['update:modelValue', 'saved'])

const dechetsStore = useDechetsStore()
const formRef = ref(null)
const loading = ref(false)

const isEdit = computed(() => !!props.conteneur?.id)

const uniteOptions = ['kg', 'litres', 'unites', 'tonnes']

const typeItems = computed(() => {
  return props.types.map(t => ({
    text: `${t.denomination} (${t.categorie})${t.dangereux ? ' - DD' : ''}`,
    value: t.id,
    dangereux: t.dangereux,
  }))
})

const defaultForm = () => ({
  nom: '',
  type_dechet_id: null,
  capacite: null,
  unite: 'litres',
  emplacement: '',
  mixte: false,
  date_mise_en_service: '',
})

const form = ref(defaultForm())
const { isDirty, snapshot } = useFormDirty(form)
const showDirtyWarning = ref(false)

const rules = { required, positiveNumber }

watch(() => props.modelValue, (val) => {
  if (val) {
    if (props.conteneur) {
      form.value = {
        nom: props.conteneur.nom || '',
        type_dechet_id: props.conteneur.type_dechet_id || null,
        capacite: props.conteneur.capacite || null,
        unite: props.conteneur.unite || 'litres',
        emplacement: props.conteneur.emplacement || '',
        mixte: props.conteneur.mixte || false,
        date_mise_en_service: props.conteneur.date_mise_en_service || '',
      }
    } else {
      form.value = defaultForm()
    }
    nextTick(() => snapshot())
  }
})

function tryClose() {
  if (isDirty.value) {
    showDirtyWarning.value = true
  } else {
    close()
  }
}

function forceClose() {
  showDirtyWarning.value = false
  close()
}

function close() {
  emit('update:modelValue', false)
}

async function handleSubmit() {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  loading.value = true
  try {
    if (isEdit.value) {
      await dechetsStore.updateConteneur(props.conteneur.id, form.value)
    } else {
      await dechetsStore.createConteneur(form.value)
    }
    emit('saved')
  } catch (error) {
    // Error handling is delegated to the parent via the API interceptor
    console.error('Error saving conteneur:', error)
  } finally {
    loading.value = false
  }
}
</script>
