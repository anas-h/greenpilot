<template>
  <div style="max-width: min(500px, 100%); width: 100%; margin: 0 auto">
    <Doughnut
      v-if="chartData"
      :data="chartData"
      :options="{ responsive: true, plugins: { legend: { position: 'bottom' } } }"
    />
    <p
      v-else
      class="text-center text-grey pa-8"
    >
      Aucune donnee
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'

ChartJS.register(ArcElement, Tooltip, Legend)

const props = defineProps({
  data: { type: Object, default: () => ({}) },
})

const colors = [
  '#2E7D32',
  '#66BB6A',
  '#F44336',
  '#FF9800',
  '#2196F3',
  '#9C27B0',
  '#795548',
  '#607D8B',
  '#E91E63',
  '#00BCD4',
  '#CDDC39',
  '#FF5722',
]

const chartData = computed(() => {
  if (!props.data.labels) return null
  return {
    labels: props.data.labels,
    datasets: [
      {
        data: props.data.values || [],
        backgroundColor: colors.slice(0, props.data.labels.length),
      },
    ],
  }
})
</script>
