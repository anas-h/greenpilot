import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/api'

export const useAdminStore = defineStore('admin', () => {
  const entreprises = ref([])
  const entreprisesPagination = ref({})
  const currentEntreprise = ref(null)
  const users = ref([])
  const usersPagination = ref({})
  const stats = ref(null)
  const loading = ref(false)
  const bordereaux = ref([])
  const bordereauxPagination = ref({})

  async function fetchEntreprises(params = {}) {
    loading.value = true
    try {
      const { data } = await api.get('/admin/entreprises', { params })
      entreprises.value = data.data || []
      entreprisesPagination.value = {
        current_page: data.current_page,
        last_page: data.last_page,
        total: data.total,
      }
    } finally {
      loading.value = false
    }
  }

  async function createEntreprise(form) {
    const { data } = await api.post('/admin/entreprises', form)
    return data.data || data
  }

  async function fetchEntreprise(id) {
    loading.value = true
    try {
      const { data } = await api.get(`/admin/entreprises/${id}`)
      currentEntreprise.value = data.data || data
      return currentEntreprise.value
    } finally {
      loading.value = false
    }
  }

  async function updateEntreprise(id, form) {
    const { data } = await api.put(`/admin/entreprises/${id}`, form)
    currentEntreprise.value = data.data || data
    return currentEntreprise.value
  }

  async function toggleActive(id) {
    const { data } = await api.post(`/admin/entreprises/${id}/toggle-active`)
    // Update in list
    const idx = entreprises.value.findIndex(e => e.id === id)
    if (idx !== -1) {
      entreprises.value[idx] = data.data || data
    }
    if (currentEntreprise.value?.id === id) {
      currentEntreprise.value = data.data || data
    }
    return data
  }

  async function toggleArchive(id) {
    const { data } = await api.post(`/admin/entreprises/${id}/toggle-archive`)
    const idx = entreprises.value.findIndex(e => e.id === id)
    if (idx !== -1) {
      entreprises.value[idx] = data.data || data
    }
    if (currentEntreprise.value?.id === id) {
      currentEntreprise.value = data.data || data
    }
    return data
  }

  async function fetchStats() {
    loading.value = true
    try {
      const { data } = await api.get('/admin/stats')
      stats.value = data.data || data
      return stats.value
    } finally {
      loading.value = false
    }
  }

  async function fetchUsers(params = {}) {
    loading.value = true
    try {
      const { data } = await api.get('/admin/users', { params })
      users.value = data.data || []
      usersPagination.value = {
        current_page: data.current_page,
        last_page: data.last_page,
        total: data.total,
      }
    } finally {
      loading.value = false
    }
  }

  async function createGarage(entrepriseId, form) {
    const { data } = await api.post(`/admin/entreprises/${entrepriseId}/garages`, form)
    // Refresh entreprise detail
    if (currentEntreprise.value?.id === entrepriseId) {
      await fetchEntreprise(entrepriseId)
    }
    return data.data || data
  }

  async function updateUser(userId, form) {
    const { data } = await api.put(`/admin/users/${userId}`, form)
    return data.data || data
  }

  async function createUser(entrepriseId, form) {
    const { data } = await api.post(`/admin/entreprises/${entrepriseId}/users`, form)
    if (currentEntreprise.value?.id === entrepriseId) {
      await fetchEntreprise(entrepriseId)
    }
    return data.data || data
  }

  async function deleteUser(userId) {
    const { data } = await api.delete(`/admin/users/${userId}`)
    if (currentEntreprise.value) {
      await fetchEntreprise(currentEntreprise.value.id)
    }
    return data
  }

  async function deleteGarage(garageId) {
    const { data } = await api.delete(`/admin/garages/${garageId}`)
    if (currentEntreprise.value) {
      await fetchEntreprise(currentEntreprise.value.id)
    }
    return data
  }

  async function loginAs(userId) {
    const { data } = await api.post(`/admin/users/${userId}/login-as`)
    return data
  }

  async function fetchBordereaux(params = {}) {
    loading.value = true
    try {
      const { data } = await api.get('/admin/bordereaux', { params })
      bordereaux.value = data.data || []
      bordereauxPagination.value = {
        current_page: data.current_page,
        last_page: data.last_page,
        total: data.total,
      }
    } finally {
      loading.value = false
    }
  }

  async function retryBsd(bordereauId) {
    const { data } = await api.post(`/admin/bordereaux/${bordereauId}/retry`)
    return data
  }

  return {
    entreprises,
    entreprisesPagination,
    currentEntreprise,
    users,
    usersPagination,
    stats,
    loading,
    bordereaux,
    bordereauxPagination,
    fetchEntreprises,
    createEntreprise,
    fetchEntreprise,
    updateEntreprise,
    toggleActive,
    toggleArchive,
    fetchStats,
    fetchUsers,
    createGarage,
    updateUser,
    createUser,
    deleteUser,
    deleteGarage,
    loginAs,
    fetchBordereaux,
    retryBsd,
  }
})
