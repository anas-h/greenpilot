<template>
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between mb-6 ga-3">
      <div>
        <h1 class="text-h5 text-sm-h4 font-weight-bold text-primary">
          Enlevements
        </h1>
        <p class="text-body-2 text-grey mt-1">
          Planification et suivi des enlevements de dechets
        </p>
      </div>
      <v-btn
        color="primary"
        prepend-icon="mdi-plus"
        @click="openDialog()"
      >
        Nouvel enlevement
      </v-btn>
    </div>

    <v-tabs
      v-model="activeTab"
      color="primary"
      class="mb-4"
    >
      <v-tab value="liste">
        <v-icon start>
          mdi-format-list-bulleted
        </v-icon>
        Liste
      </v-tab>
      <v-tab value="calendrier">
        <v-icon start>
          mdi-calendar
        </v-icon>
        Calendrier
      </v-tab>
    </v-tabs>

    <v-tabs-window v-model="activeTab">
      <v-tabs-window-item value="liste">
        <EnlevementsList
          ref="listRef"
          @edit="openDialog"
          @complete="openCompletion"
          @download-pdf="downloadPdf"
        />
      </v-tabs-window-item>

      <v-tabs-window-item value="calendrier">
        <EnlevementCalendrier
          ref="calendarRef"
          @select="openDialog"
        />
      </v-tabs-window-item>
    </v-tabs-window>

    <EnlevementDialog
      v-model="dialogOpen"
      :enlevement="editingEnlevement"
      @saved="refreshList"
    />

    <EnlevementCompletion
      v-model="completionOpen"
      :enlevement="completingEnlevement"
      @completed="refreshList"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '../../services/api'
import { useUiStore } from '../../stores/ui'
import EnlevementsList from './components/EnlevementsList.vue'
import EnlevementCalendrier from './components/EnlevementCalendrier.vue'
import EnlevementDialog from './components/EnlevementDialog.vue'
import EnlevementCompletion from './components/EnlevementCompletion.vue'

const uiStore = useUiStore()

const activeTab = ref('liste')
const dialogOpen = ref(false)
const completionOpen = ref(false)
const editingEnlevement = ref(null)
const completingEnlevement = ref(null)
const listRef = ref(null)
const calendarRef = ref(null)

function openDialog(enlevement = null) {
  editingEnlevement.value = enlevement
  dialogOpen.value = true
}

function openCompletion(enlevement) {
  completingEnlevement.value = enlevement
  completionOpen.value = true
}

async function downloadPdf(enlevement) {
  try {
    const response = await api.get(`/enlevements/${enlevement.id}/bon-pdf`, {
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `bon-enlevement-${enlevement.numero}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    uiStore.showError('Erreur lors du telechargement du PDF.')
  }
}

function refreshList() {
  if (listRef.value?.fetchEnlevements) {
    listRef.value.fetchEnlevements()
  }
  if (calendarRef.value?.fetchCalendrier) {
    calendarRef.value.fetchCalendrier()
  }
}
</script>
