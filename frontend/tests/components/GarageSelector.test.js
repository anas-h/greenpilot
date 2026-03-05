import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useGarageStore } from '../../src/stores/garage'

vi.mock('../../src/services/api', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}))

import api from '../../src/services/api'

describe('GarageSelector Logic', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
  })

  it('renders when garages are available', async () => {
    api.get.mockResolvedValueOnce({
      data: {
        data: [
          { id: 1, nom: 'Garage Paris' },
          { id: 2, nom: 'Garage Lyon' },
        ],
      },
    })

    const store = useGarageStore()
    await store.fetchGarages()

    // v-if="garageStore.garages.length > 0"
    expect(store.garages.length > 0).toBe(true)
    expect(store.garages).toHaveLength(2)
  })

  it('does not render when no garages', () => {
    const store = useGarageStore()

    expect(store.garages.length > 0).toBe(false)
  })

  it('setCurrentGarage updates selection', async () => {
    api.get.mockResolvedValueOnce({
      data: {
        data: [
          { id: 1, nom: 'Garage Paris' },
          { id: 2, nom: 'Garage Lyon' },
        ],
      },
    })

    const store = useGarageStore()
    await store.fetchGarages()

    store.setCurrentGarage(2)

    expect(store.currentGarageId).toBe(2)
    expect(store.currentGarage).toEqual({ id: 2, nom: 'Garage Lyon' })
  })

  it('currentGarage computed returns selected garage', async () => {
    api.get.mockResolvedValueOnce({
      data: {
        data: [
          { id: 1, nom: 'Garage A' },
          { id: 3, nom: 'Garage C' },
        ],
      },
    })

    const store = useGarageStore()
    await store.fetchGarages()

    store.setCurrentGarage(3)
    expect(store.currentGarage.nom).toBe('Garage C')
  })

  it('persists selection in localStorage', async () => {
    api.get.mockResolvedValueOnce({
      data: {
        data: [{ id: 1, nom: 'Garage Persistent' }],
      },
    })

    const store = useGarageStore()
    await store.fetchGarages()
    store.setCurrentGarage(1)

    expect(localStorage.getItem('garageId')).toBe('1')
  })

  it('displays garage items by nom field', async () => {
    api.get.mockResolvedValueOnce({
      data: {
        data: [
          { id: 1, nom: 'Garage Alpha' },
          { id: 2, nom: 'Garage Beta' },
        ],
      },
    })

    const store = useGarageStore()
    await store.fetchGarages()

    // The v-select uses item-title="nom" and item-value="id"
    const names = store.garages.map(g => g.nom)
    expect(names).toEqual(['Garage Alpha', 'Garage Beta'])
  })
})
