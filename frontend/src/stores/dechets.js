import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

export const useDechetsStore = defineStore('dechets', () => {
  const typesDechets = ref([])
  const conteneurs = ref([])
  const loadingTypes = ref(false)
  const loadingConteneurs = ref(false)

  // Computed getters
  const typesDangereux = computed(() => typesDechets.value.filter(t => t.dangereux))
  const typesNonDangereux = computed(() => typesDechets.value.filter(t => !t.dangereux))
  const typesActifs = computed(() => typesDechets.value.filter(t => t.garage_config?.actif))
  const categories = computed(() => [...new Set(typesDechets.value.map(t => t.categorie))].sort())

  // Types actions
  async function fetchTypes(params = {}) {
    loadingTypes.value = true
    try {
      const { data } = await api.get('/types-dechets', { params })
      typesDechets.value = data.data || data
    } finally {
      loadingTypes.value = false
    }
  }

  async function fetchSystemTypes() {
    const { data } = await api.get('/types-dechets/systeme')
    return data.data || data
  }

  async function createType(form) {
    const { data } = await api.post('/types-dechets', form)
    const created = data.data || data
    typesDechets.value.push(created)
    return created
  }

  async function updateType(id, form) {
    const { data } = await api.put(`/types-dechets/${id}`, form)
    const updated = data.data || data
    const idx = typesDechets.value.findIndex(t => t.id === id)
    if (idx !== -1) typesDechets.value[idx] = updated
    return updated
  }

  async function deleteType(id) {
    await api.delete(`/types-dechets/${id}`)
    typesDechets.value = typesDechets.value.filter(t => t.id !== id)
  }

  async function toggleTypeActivation(id) {
    const { data } = await api.post(`/types-dechets/${id}/toggle`)
    // Refresh types to get updated pivot data
    await fetchTypes()
    return data
  }

  // Conteneurs actions
  async function fetchConteneurs(params = {}) {
    loadingConteneurs.value = true
    try {
      const { data } = await api.get('/conteneurs', { params })
      conteneurs.value = data.data || data
    } finally {
      loadingConteneurs.value = false
    }
  }

  async function createConteneur(form) {
    const { data } = await api.post('/conteneurs', form)
    const created = data.data || data
    conteneurs.value.push(created)
    return created
  }

  async function updateConteneur(id, form) {
    const { data } = await api.put(`/conteneurs/${id}`, form)
    const updated = data.data || data
    const idx = conteneurs.value.findIndex(c => c.id === id)
    if (idx !== -1) conteneurs.value[idx] = updated
    return updated
  }

  async function deleteConteneur(id) {
    await api.delete(`/conteneurs/${id}`)
    conteneurs.value = conteneurs.value.filter(c => c.id !== id)
  }

  async function fetchConteneur(id) {
    const { data } = await api.get(`/conteneurs/${id}`)
    return data.data || data
  }

  async function scanQrCode(codeQr) {
    const { data } = await api.post('/conteneurs/scan', { code_qr: codeQr })
    return data.data || data
  }

  async function fetchHistorique(id, params = {}) {
    const { data } = await api.get(`/conteneurs/${id}/historique`, { params })
    return data
  }

  async function checkConformite() {
    const { data } = await api.get('/conteneurs-conformite')
    return data.data || data
  }

  function downloadQrCode(id) {
    return api.get(`/conteneurs/${id}/qr-download`, { responseType: 'blob' })
  }

  return {
    typesDechets,
    conteneurs,
    loadingTypes,
    loadingConteneurs,
    typesDangereux,
    typesNonDangereux,
    typesActifs,
    categories,
    fetchTypes,
    fetchSystemTypes,
    createType,
    updateType,
    deleteType,
    toggleTypeActivation,
    fetchConteneurs,
    createConteneur,
    updateConteneur,
    deleteConteneur,
    fetchConteneur,
    scanQrCode,
    fetchHistorique,
    checkConformite,
    downloadQrCode,
  }
})
