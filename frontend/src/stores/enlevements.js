import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

export const useEnlevementsStore = defineStore('enlevements', () => {
  const enlevements = ref([])
  const currentEnlevement = ref(null)
  const loading = ref(false)
  const error = ref(null)

  const brouillons = computed(() => enlevements.value.filter(e => e.statut === 'brouillon'))
  const planifies = computed(() => enlevements.value.filter(e => e.statut === 'planifie'))
  const completes = computed(() => enlevements.value.filter(e => e.statut === 'complete'))

  async function fetchEnlevements(filters = {}) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/enlevements', { params: filters })
      enlevements.value = data.data || []
      return enlevements.value
    } catch (e) {
      error.value = e.response?.data?.message || 'Erreur lors du chargement des enlevements.'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function fetchEnlevement(id) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get(`/enlevements/${id}`)
      currentEnlevement.value = data.data || data
      return currentEnlevement.value
    } catch (e) {
      error.value = e.response?.data?.message || 'Erreur lors du chargement.'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function createEnlevement(payload) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.post('/enlevements', payload)
      const created = data.data || data
      enlevements.value.unshift(created)
      return created
    } catch (e) {
      error.value = e.response?.data?.message || 'Erreur lors de la creation.'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function updateEnlevement(id, payload) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.put(`/enlevements/${id}`, payload)
      const updated = data.data || data
      const idx = enlevements.value.findIndex(e => e.id === id)
      if (idx !== -1) enlevements.value[idx] = updated
      return updated
    } catch (e) {
      error.value = e.response?.data?.message || 'Erreur lors de la mise a jour.'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function deleteEnlevement(id) {
    loading.value = true
    error.value = null
    try {
      await api.delete(`/enlevements/${id}`)
      enlevements.value = enlevements.value.filter(e => e.id !== id)
    } catch (e) {
      error.value = e.response?.data?.message || 'Erreur lors de la suppression.'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function completeEnlevement(id, payload) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.post(`/enlevements/${id}/complete`, payload)
      const completed = data.data || data
      const idx = enlevements.value.findIndex(e => e.id === id)
      if (idx !== -1) enlevements.value[idx] = completed
      return completed
    } catch (e) {
      error.value = e.response?.data?.message || 'Erreur lors de la completion.'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function fetchCalendrier(params = {}) {
    try {
      const { data } = await api.get('/enlevements/calendrier', { params })
      return data.data || []
    } catch (e) {
      error.value = e.response?.data?.message || 'Erreur lors du chargement du calendrier.'
      throw e
    }
  }

  async function downloadBonPdf(id, numero) {
    try {
      const response = await api.get(`/enlevements/${id}/bon-pdf`, {
        responseType: 'blob',
      })
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `bon-enlevement-${numero}.pdf`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
    } catch (e) {
      error.value = 'Erreur lors du telechargement du PDF.'
      throw e
    }
  }

  function clearError() {
    error.value = null
  }

  return {
    enlevements,
    currentEnlevement,
    loading,
    error,
    brouillons,
    planifies,
    completes,
    fetchEnlevements,
    fetchEnlevement,
    createEnlevement,
    updateEnlevement,
    deleteEnlevement,
    completeEnlevement,
    fetchCalendrier,
    downloadBonPdf,
    clearError,
  }
})
