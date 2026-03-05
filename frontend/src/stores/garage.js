import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

export const useGarageStore = defineStore('garage', () => {
  const garages = ref([])
  const storedId = localStorage.getItem('garageId')
  const currentGarageId = ref(storedId && !isNaN(Number(storedId)) ? Number(storedId) : null)

  const currentGarage = computed(() => garages.value.find(g => g.id === currentGarageId.value))

  async function fetchGarages() {
    const { data } = await api.get('/garages')
    garages.value = data.data || data
    // Toujours verifier que le garage selectionne existe dans la liste
    const exists = garages.value.some(g => g.id === currentGarageId.value)
    if (!exists && garages.value.length > 0) {
      setCurrentGarage(garages.value[0].id)
    }
  }

  function setCurrentGarage(id) {
    currentGarageId.value = id
    localStorage.setItem('garageId', id)
  }

  async function createGarage(form) {
    const { data } = await api.post('/garages', form)
    garages.value.push(data.data || data)
    return data.data || data
  }

  async function updateGarage(id, form) {
    const { data } = await api.put(`/garages/${id}`, form)
    const idx = garages.value.findIndex(g => g.id === id)
    if (idx !== -1) garages.value[idx] = data.data || data
    return data.data || data
  }

  async function deleteGarage(id) {
    await api.delete(`/garages/${id}`)
    garages.value = garages.value.filter(g => g.id !== id)
    if (currentGarageId.value === id) {
      setCurrentGarage(garages.value[0]?.id || null)
    }
  }

  return { garages, currentGarageId, currentGarage, fetchGarages, setCurrentGarage, createGarage, updateGarage, deleteGarage }
})
