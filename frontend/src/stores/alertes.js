import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/services/api'

export const useAlertesStore = defineStore('alertes', () => {
  // State
  const alertes = ref([])
  const totalAlertes = ref(0)
  const counts = ref({
    total: 0,
    critique: 0,
    haute: 0,
    moyenne: 0,
    basse: 0,
  })
  const loading = ref(false)
  const error = ref(null)
  let pollingInterval = null

  // Actions

  /**
   * Fetch alertes list with filters.
   */
  async function fetchAlertes(params = {}) {
    loading.value = true
    error.value = null
    try {
      const response = await api.get('/alertes', { params })
      alertes.value = response.data.data
      totalAlertes.value = response.data.total
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement des alertes'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch unread/unresolved alert counts.
   */
  async function fetchCounts() {
    try {
      const response = await api.get('/alertes/count')
      counts.value = response.data
      return response.data
    } catch (err) {
      // Silent fail for count polling
      console.warn('Failed to fetch alert counts:', err)
    }
  }

  /**
   * Mark a specific alert as read.
   */
  async function markRead(id) {
    try {
      const response = await api.post('/alertes/mark-read', { id })
      // Update local state
      const index = alertes.value.findIndex(a => a.id === id)
      if (index !== -1) {
        alertes.value[index].lue = true
        alertes.value[index].date_lecture = new Date().toISOString()
      }
      // Update counts
      await fetchCounts()
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du marquage de l\'alerte'
      throw err
    }
  }

  /**
   * Mark all alerts as read.
   */
  async function markAllRead() {
    try {
      const response = await api.post('/alertes/mark-read')
      // Update local state
      alertes.value.forEach(a => {
        a.lue = true
        a.date_lecture = a.date_lecture || new Date().toISOString()
      })
      await fetchCounts()
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du marquage des alertes'
      throw err
    }
  }

  /**
   * Mark an alert as resolved.
   */
  async function resolve(id) {
    try {
      const response = await api.post(`/alertes/${id}/resolve`)
      // Update local state
      const index = alertes.value.findIndex(a => a.id === id)
      if (index !== -1) {
        alertes.value[index].resolue = true
        alertes.value[index].date_resolution = new Date().toISOString()
        alertes.value[index].lue = true
      }
      await fetchCounts()
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la resolution de l\'alerte'
      throw err
    }
  }

  /**
   * Start polling for alert counts every 60 seconds.
   */
  function startPolling() {
    stopPolling() // Clear any existing interval
    fetchCounts() // Immediate fetch
    pollingInterval = setInterval(() => {
      fetchCounts()
    }, 60000) // 60 seconds
  }

  /**
   * Stop polling for alert counts.
   */
  function stopPolling() {
    if (pollingInterval) {
      clearInterval(pollingInterval)
      pollingInterval = null
    }
  }

  /**
   * Reset store state.
   */
  function $reset() {
    alertes.value = []
    totalAlertes.value = 0
    counts.value = { total: 0, critique: 0, haute: 0, moyenne: 0, basse: 0 }
    loading.value = false
    error.value = null
    stopPolling()
  }

  return {
    // State
    alertes,
    totalAlertes,
    counts,
    loading,
    error,

    // Actions
    fetchAlertes,
    fetchCounts,
    markRead,
    markAllRead,
    resolve,
    startPolling,
    stopPolling,
    $reset,
  }
})
