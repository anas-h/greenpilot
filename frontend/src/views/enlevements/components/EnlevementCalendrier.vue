<template>
  <v-card>
    <v-card-title class="d-flex align-center pa-4">
      <v-btn
        icon="mdi-chevron-left"
        variant="text"
        title="Mois precedent"
        @click="prevMonth"
      />
      <span class="text-h6 mx-4">{{ monthLabel }}</span>
      <v-btn
        icon="mdi-chevron-right"
        variant="text"
        title="Mois suivant"
        @click="nextMonth"
      />
      <v-spacer />
      <v-btn
        variant="outlined"
        size="small"
        @click="goToday"
      >
        Aujourd'hui
      </v-btn>
    </v-card-title>

    <v-divider />

    <v-card-text class="pa-2">
      <v-progress-linear
        v-if="loading"
        indeterminate
        color="primary"
        class="mb-2"
      />

      <div class="calendar-grid">
        <div
          v-for="day in dayNames"
          :key="day"
          class="calendar-header"
        >
          {{ day }}
        </div>
        <div
          v-for="(cell, idx) in calendarCells"
          :key="idx"
          :class="['calendar-cell', { 'other-month': !cell.currentMonth, 'today': cell.isToday }]"
        >
          <div class="day-number">
            {{ cell.day }}
          </div>
          <div class="events">
            <div
              v-for="enl in cell.enlevements"
              :key="enl.id"
              :class="['event-chip', `event-${enl.statut}`]"
              @click="$emit('select', enl)"
            >
              <v-icon
                size="x-small"
                class="mr-1"
              >
                mdi-truck
              </v-icon>
              <span class="text-truncate">{{ enl.collecteur?.raison_sociale || enl.numero }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex align-center ga-4 mt-4 pa-2">
        <div class="d-flex align-center ga-1">
          <div class="legend-dot event-brouillon" />
          <span class="text-caption">Brouillon</span>
        </div>
        <div class="d-flex align-center ga-1">
          <div class="legend-dot event-planifie" />
          <span class="text-caption">Planifie</span>
        </div>
        <div class="d-flex align-center ga-1">
          <div class="legend-dot event-complete" />
          <span class="text-caption">Complete</span>
        </div>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import api from '../../../services/api'
import { useUiStore } from '../../../stores/ui'

const emit = defineEmits(['select'])

const uiStore = useUiStore()
const loading = ref(false)
const enlevements = ref([])

const currentMonth = ref(new Date().getMonth())
const currentYear = ref(new Date().getFullYear())

const dayNames = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']

const monthLabel = computed(() => {
  const months = [
    'Janvier', 'Fevrier', 'Mars', 'Avril', 'Mai', 'Juin',
    'Juillet', 'Aout', 'Septembre', 'Octobre', 'Novembre', 'Decembre',
  ]
  return `${months[currentMonth.value]} ${currentYear.value}`
})

const calendarCells = computed(() => {
  const cells = []
  const firstDay = new Date(currentYear.value, currentMonth.value, 1)
  const lastDay = new Date(currentYear.value, currentMonth.value + 1, 0)

  // Day of week for the first day (0=Sun, 1=Mon...)
  let startDow = firstDay.getDay()
  // Convert to Mon=0 format
  startDow = startDow === 0 ? 6 : startDow - 1

  // Days from previous month
  const prevMonthLast = new Date(currentYear.value, currentMonth.value, 0)
  for (let i = startDow - 1; i >= 0; i--) {
    const day = prevMonthLast.getDate() - i
    cells.push({
      day,
      currentMonth: false,
      isToday: false,
      enlevements: [],
    })
  }

  // Days in current month
  const today = new Date()
  for (let d = 1; d <= lastDay.getDate(); d++) {
    const dateStr = `${currentYear.value}-${String(currentMonth.value + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`
    const isToday = today.getDate() === d && today.getMonth() === currentMonth.value && today.getFullYear() === currentYear.value

    const dayEnlevements = enlevements.value.filter(e => {
      const eDateStr = e.date_prevue ? e.date_prevue.substring(0, 10) : ''
      return eDateStr === dateStr
    })

    cells.push({
      day: d,
      currentMonth: true,
      isToday,
      enlevements: dayEnlevements,
    })
  }

  // Fill rest of grid (to complete last row of 7)
  const remaining = 7 - (cells.length % 7)
  if (remaining < 7) {
    for (let d = 1; d <= remaining; d++) {
      cells.push({
        day: d,
        currentMonth: false,
        isToday: false,
        enlevements: [],
      })
    }
  }

  return cells
})

function prevMonth() {
  if (currentMonth.value === 0) {
    currentMonth.value = 11
    currentYear.value--
  } else {
    currentMonth.value--
  }
}

function nextMonth() {
  if (currentMonth.value === 11) {
    currentMonth.value = 0
    currentYear.value++
  } else {
    currentMonth.value++
  }
}

function goToday() {
  const today = new Date()
  currentMonth.value = today.getMonth()
  currentYear.value = today.getFullYear()
}

async function fetchCalendrier() {
  loading.value = true
  try {
    const { data } = await api.get('/enlevements/calendrier', {
      params: {
        mois: currentMonth.value + 1,
        annee: currentYear.value,
      },
    })
    enlevements.value = data.data || []
  } catch (e) {
    uiStore.showError('Erreur lors du chargement du calendrier.')
  } finally {
    loading.value = false
  }
}

watch([currentMonth, currentYear], fetchCalendrier)

onMounted(fetchCalendrier)

defineExpose({ fetchCalendrier })
</script>

<style scoped>
.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 1px;
  background-color: #e0e0e0;
}

.calendar-header {
  background-color: #2E7D32;
  color: white;
  text-align: center;
  padding: 8px 4px;
  font-weight: bold;
  font-size: 12px;
}

.calendar-cell {
  background-color: white;
  min-height: 50px;
  padding: 4px;
}

@media (min-width: 600px) {
  .calendar-cell {
    min-height: 90px;
  }
}


.calendar-cell.other-month {
  background-color: #fafafa;
}

.calendar-cell.other-month .day-number {
  color: #bbb;
}

.calendar-cell.today {
  background-color: #E8F5E9;
}

.calendar-cell.today .day-number {
  color: #2E7D32;
  font-weight: bold;
}

.day-number {
  font-size: 12px;
  font-weight: 500;
  margin-bottom: 2px;
}

.events {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.event-chip {
  font-size: 10px;
  padding: 2px 4px;
  border-radius: 4px;
  cursor: pointer;
  display: flex;
  align-items: center;
  overflow: hidden;
  white-space: nowrap;
}

@media (max-width: 599px) {
  .event-chip span {
    display: none;
  }
  .event-chip .mr-1 {
    margin-right: 0 !important;
  }
}

.event-chip:hover {
  opacity: 0.8;
}

.event-brouillon {
  background-color: #E0E0E0;
  color: #616161;
}

.event-planifie {
  background-color: #BBDEFB;
  color: #1565C0;
}

.event-complete {
  background-color: #C8E6C9;
  color: #2E7D32;
}

.event-annule {
  background-color: #FFCDD2;
  color: #C62828;
}

.legend-dot {
  width: 12px;
  height: 12px;
  border-radius: 3px;
}
</style>
