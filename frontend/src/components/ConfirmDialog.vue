<template>
  <v-dialog
    :model-value="modelValue"
    max-width="400"
    :fullscreen="mobile"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <v-card>
      <v-card-title class="text-h6">
        {{ title }}
      </v-card-title>
      <v-card-text>{{ message }}</v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn
          variant="text"
          @click="$emit('update:modelValue', false)"
        >
          Annuler
        </v-btn>
        <v-btn
          :color="confirmColor"
          @click="$emit('confirm')"
        >
          {{ confirmText }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { useDisplay } from 'vuetify'

const { mobile } = useDisplay()

defineProps({
  modelValue: Boolean,
  title: { type: String, default: 'Confirmer' },
  message: { type: String, default: 'Etes-vous sur ?' },
  confirmText: { type: String, default: 'Confirmer' },
  confirmColor: { type: String, default: 'error' },
})

defineEmits(['update:modelValue', 'confirm'])
</script>
