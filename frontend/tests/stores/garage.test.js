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

describe('Garage Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
  })

  it('starts with no garages', () => {
    const store = useGarageStore()
    expect(store.garages).toEqual([])
    expect(store.currentGarageId).toBeNull()
  })

  it('fetches garages and auto-selects first', async () => {
    api.get.mockResolvedValueOnce({
      data: {
        data: [
          { id: 1, nom: 'Garage A' },
          { id: 2, nom: 'Garage B' },
        ],
      },
    })

    const store = useGarageStore()
    await store.fetchGarages()

    expect(store.garages).toHaveLength(2)
    expect(store.currentGarageId).toBe(1)
  })

  it('persists garage selection', () => {
    const store = useGarageStore()
    store.setCurrentGarage(42)

    expect(store.currentGarageId).toBe(42)
    expect(localStorage.getItem('garageId')).toBe('42')
  })

  it('returns current garage computed', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: [{ id: 5, nom: 'Mon Garage' }] },
    })

    const store = useGarageStore()
    await store.fetchGarages()

    expect(store.currentGarage.nom).toBe('Mon Garage')
  })

  it('creates garage and adds to list', async () => {
    api.post.mockResolvedValueOnce({
      data: { data: { id: 10, nom: 'Nouveau' } },
    })

    const store = useGarageStore()
    const result = await store.createGarage({ nom: 'Nouveau' })

    expect(result.id).toBe(10)
    expect(store.garages).toHaveLength(1)
  })

  it('handles garage deletion and reselects', async () => {
    const store = useGarageStore()
    store.garages = [
      { id: 1, nom: 'A' },
      { id: 2, nom: 'B' },
    ]
    store.currentGarageId = 1

    api.delete.mockResolvedValueOnce({})
    await store.deleteGarage(1)

    expect(store.garages).toHaveLength(1)
    expect(store.currentGarageId).toBe(2)
  })
})
