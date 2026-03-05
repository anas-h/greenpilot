<template>
  <div :class="['conteneur-jauge', { 'jauge-compact': compact, 'jauge-inline': inline }]">
    <!-- Percentage display -->
    <div
      v-if="!inline"
      class="text-center mb-1"
    >
      <span
        class="text-h6 font-weight-bold"
        :style="{ color: jaugeColor }"
      >
        {{ pourcentage }}%
      </span>
    </div>

    <!-- Gauge bar -->
    <div
      class="jauge-bar-container"
      :style="{ height: inline ? `${height}px` : `${height}px` }"
    >
      <div
        class="jauge-bar-fill"
        :style="{
          width: `${pourcentage}%`,
          backgroundColor: jaugeColor,
        }"
      />
      <span
        v-if="inline"
        class="jauge-inline-label"
      >
        {{ pourcentage }}%
      </span>
    </div>

    <!-- Values -->
    <div
      v-if="!inline"
      class="d-flex justify-space-between mt-1"
    >
      <span class="text-caption text-grey">{{ formatNumber(niveau) }} {{ unite }}</span>
      <span class="text-caption text-grey">{{ formatNumber(capacite) }} {{ unite }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  niveau: {
    type: Number,
    default: 0,
  },
  capacite: {
    type: Number,
    default: 1,
  },
  unite: {
    type: String,
    default: 'litres',
  },
  height: {
    type: [Number, String],
    default: 80,
  },
  compact: {
    type: Boolean,
    default: false,
  },
  inline: {
    type: Boolean,
    default: false,
  },
})

const pourcentage = computed(() => {
  if (!props.capacite || props.capacite === 0) return 0
  return Math.min(100, Math.round((props.niveau / props.capacite) * 100))
})

const jaugeColor = computed(() => {
  const pct = pourcentage.value
  if (pct >= 95) return '#F44336'   // Red
  if (pct >= 80) return '#FF9800'   // Yellow/Orange
  return '#4CAF50'                   // Green
})

function formatNumber(val) {
  if (val === null || val === undefined) return '0'
  return Number(val).toLocaleString('fr-FR', { maximumFractionDigits: 2 })
}
</script>

<style scoped>
.conteneur-jauge {
  width: 100%;
}

.jauge-bar-container {
  width: 100%;
  background-color: #E8F5E9;
  border-radius: 6px;
  overflow: hidden;
  position: relative;
}

.jauge-bar-fill {
  height: 100%;
  border-radius: 6px;
  transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.3s ease;
  min-width: 2px;
  background-image: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.15));
}

.jauge-inline-label {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 11px;
  font-weight: 600;
  color: #333;
  text-shadow: 0 0 3px rgba(255, 255, 255, 0.9);
}

.jauge-compact .jauge-bar-container {
  height: 12px !important;
}

.jauge-compact .text-h6 {
  font-size: 1rem !important;
}

.jauge-inline .jauge-bar-container {
  height: 24px;
  border-radius: 12px;
}

.jauge-inline .jauge-bar-fill {
  border-radius: 12px;
}
</style>
