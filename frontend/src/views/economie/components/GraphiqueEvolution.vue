<template>
  <div style="height: 50vh; min-height: 250px; max-height: 400px">
    <Line
      v-if="chartData"
      :data="chartData"
      :options="chartOptions"
    />
    <p
      v-else
      class="text-center text-grey pa-8"
    >
      Aucune donnee disponible
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler)

const props = defineProps({
  data: { type: Object, default: () => ({}) },
})

const chartData = computed(() => {
  if (!props.data.labels) return null
  return {
    labels: props.data.labels,
    datasets: [
      {
        label: 'Couts collecte',
        data: props.data.couts || [],
        borderColor: '#F44336',
        backgroundColor: 'rgba(244,67,54,0.1)',
        fill: true,
        tension: 0.3,
      },
      {
        label: 'Revenus revente',
        data: props.data.revenus || [],
        borderColor: '#4CAF50',
        backgroundColor: 'rgba(76,175,80,0.1)',
        fill: true,
        tension: 0.3,
      },
    ],
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'top' },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        callback: (v) => v + ' EUR',
      },
    },
  },
}
</script>
