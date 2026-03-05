import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useBsdStore = defineStore('bsd', () => {
  // State
  const bordereaux = ref([])
  const totalBordereaux = ref(0)
  const currentBordereau = ref(null)
  const loading = ref(false)
  const error = ref(null)

  // Getters
  const bordereauById = computed(() => {
    return (id) => bordereaux.value.find(b => b.id === id)
  })

  const bordereauByStatut = computed(() => {
    return (statut) => bordereaux.value.filter(b => b.statut === statut)
  })

  const draftCount = computed(() => {
    return bordereaux.value.filter(b => b.statut === 'DRAFT').length
  })

  const activeCount = computed(() => {
    const activeStatuts = ['SEALED', 'SIGNED_BY_PRODUCER', 'SENT', 'RECEIVED']
    return bordereaux.value.filter(b => activeStatuts.includes(b.statut)).length
  })

  // Actions

  /**
   * Fetch list of bordereaux with filters.
   */
  async function fetchBordereaux(params = {}) {
    loading.value = true
    error.value = null
    try {
      const response = await api.get('/bordereaux', { params })
      bordereaux.value = response.data.data
      totalBordereaux.value = response.data.total
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement des bordereaux'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch a single bordereau by ID.
   */
  async function fetchBordereau(id) {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(`/bordereaux/${id}`)
      currentBordereau.value = response.data
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement du bordereau'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Create a new bordereau (DRAFT).
   */
  async function createBordereau(data) {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/bordereaux', data)
      bordereaux.value.unshift(response.data)
      totalBordereaux.value++
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la creation du bordereau'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Update a DRAFT bordereau.
   */
  async function updateBordereau(id, data) {
    loading.value = true
    error.value = null
    try {
      const response = await api.put(`/bordereaux/${id}`, data)
      const index = bordereaux.value.findIndex(b => b.id === id)
      if (index !== -1) {
        bordereaux.value[index] = response.data
      }
      if (currentBordereau.value?.id === id) {
        currentBordereau.value = response.data
      }
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la mise a jour du bordereau'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Delete a DRAFT bordereau.
   */
  async function deleteBordereau(id) {
    loading.value = true
    error.value = null
    try {
      await api.delete(`/bordereaux/${id}`)
      bordereaux.value = bordereaux.value.filter(b => b.id !== id)
      totalBordereaux.value--
      if (currentBordereau.value?.id === id) {
        currentBordereau.value = null
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la suppression du bordereau'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Publish a bordereau: DRAFT -> SEALED.
   */
  async function publishBordereau(id) {
    error.value = null
    try {
      const response = await api.post(`/bordereaux/${id}/publish`)
      updateLocalBordereau(id, response.data)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la publication du bordereau'
      throw err
    }
  }

  /**
   * Sign a bordereau: SEALED -> SIGNED_BY_PRODUCER.
   */
  async function signBordereau(id) {
    error.value = null
    try {
      const response = await api.post(`/bordereaux/${id}/sign`)
      updateLocalBordereau(id, response.data)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la signature du bordereau'
      throw err
    }
  }

  /**
   * Cancel a bordereau.
   */
  async function cancelBordereau(id, motifAnnulation) {
    error.value = null
    try {
      const response = await api.post(`/bordereaux/${id}/cancel`, {
        motif_annulation: motifAnnulation,
      })
      updateLocalBordereau(id, response.data)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de l\'annulation du bordereau'
      throw err
    }
  }

  /**
   * Duplicate a bordereau (for REFUSED rework).
   */
  async function dupliquerBordereau(id) {
    error.value = null
    try {
      const response = await api.post(`/bordereaux/${id}/dupliquer`)
      bordereaux.value.unshift(response.data)
      totalBordereaux.value++
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la duplication du bordereau'
      throw err
    }
  }

  /**
   * Get allowed status transitions for a bordereau.
   */
  async function getStatutTransitions(id) {
    try {
      const response = await api.get(`/bordereaux/${id}/transitions`)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la recuperation des transitions'
      throw err
    }
  }

  /**
   * Download the BSD PDF.
   */
  async function downloadPdf(id) {
    try {
      const response = await api.get(`/bordereaux/${id}/pdf`)
      const url = response.data.url
      if (url) {
        window.open(url, '_blank')
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du telechargement du PDF'
      throw err
    }
  }

  /**
   * Helper: update a bordereau in the local state.
   */
  function updateLocalBordereau(id, data) {
    const index = bordereaux.value.findIndex(b => b.id === id)
    if (index !== -1) {
      bordereaux.value[index] = data
    }
    if (currentBordereau.value?.id === id) {
      currentBordereau.value = data
    }
  }

  /**
   * Reset store state.
   */
  function $reset() {
    bordereaux.value = []
    totalBordereaux.value = 0
    currentBordereau.value = null
    loading.value = false
    error.value = null
  }

  return {
    // State
    bordereaux,
    totalBordereaux,
    currentBordereau,
    loading,
    error,

    // Getters
    bordereauById,
    bordereauByStatut,
    draftCount,
    activeCount,

    // Actions
    fetchBordereaux,
    fetchBordereau,
    createBordereau,
    updateBordereau,
    deleteBordereau,
    publishBordereau,
    signBordereau,
    cancelBordereau,
    dupliquerBordereau,
    getStatutTransitions,
    downloadPdf,
    $reset,
  }
})
