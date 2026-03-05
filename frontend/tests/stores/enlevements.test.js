import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useEnlevementsStore } from '../../src/stores/enlevements'

vi.mock('../../src/services/api', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}))

import api from '../../src/services/api'

describe('Enlevements Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('starts with empty state', () => {
    const store = useEnlevementsStore()
    expect(store.enlevements).toEqual([])
    expect(store.currentEnlevement).toBeNull()
    expect(store.loading).toBe(false)
    expect(store.error).toBeNull()
  })

  it('fetchEnlevements loads list', async () => {
    api.get.mockResolvedValueOnce({
      data: {
        data: [
          { id: 1, statut: 'brouillon', numero: 'ENL-2026-00001' },
          { id: 2, statut: 'planifie', numero: 'ENL-2026-00002' },
          { id: 3, statut: 'complete', numero: 'ENL-2026-00003' },
        ],
      },
    })

    const store = useEnlevementsStore()
    await store.fetchEnlevements()

    expect(store.enlevements).toHaveLength(3)
    expect(store.loading).toBe(false)
  })

  it('fetchEnlevements passes filters', async () => {
    api.get.mockResolvedValueOnce({ data: { data: [] } })

    const store = useEnlevementsStore()
    await store.fetchEnlevements({ statut: 'planifie' })

    expect(api.get).toHaveBeenCalledWith('/enlevements', { params: { statut: 'planifie' } })
  })

  it('fetchEnlevements sets error on failure', async () => {
    api.get.mockRejectedValueOnce({
      response: { data: { message: 'Erreur serveur' } },
    })

    const store = useEnlevementsStore()
    await expect(store.fetchEnlevements()).rejects.toBeTruthy()

    expect(store.error).toBe('Erreur serveur')
  })

  it('fetchEnlevement loads single item', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: { id: 1, statut: 'brouillon', numero: 'ENL-2026-00001' } },
    })

    const store = useEnlevementsStore()
    await store.fetchEnlevement(1)

    expect(store.currentEnlevement).toEqual({ id: 1, statut: 'brouillon', numero: 'ENL-2026-00001' })
  })

  it('createEnlevement adds to list', async () => {
    api.post.mockResolvedValueOnce({
      data: { data: { id: 4, statut: 'brouillon', numero: 'ENL-2026-00004' } },
    })

    const store = useEnlevementsStore()
    const result = await store.createEnlevement({ collecteur_id: 1 })

    expect(result.id).toBe(4)
    expect(store.enlevements).toHaveLength(1)
    expect(store.enlevements[0].id).toBe(4)
  })

  it('updateEnlevement updates in list', async () => {
    const store = useEnlevementsStore()
    store.enlevements = [{ id: 1, statut: 'brouillon', notes: 'old' }]

    api.put.mockResolvedValueOnce({
      data: { data: { id: 1, statut: 'brouillon', notes: 'updated' } },
    })

    await store.updateEnlevement(1, { notes: 'updated' })

    expect(store.enlevements[0].notes).toBe('updated')
  })

  it('deleteEnlevement removes from list', async () => {
    const store = useEnlevementsStore()
    store.enlevements = [
      { id: 1, statut: 'brouillon' },
      { id: 2, statut: 'brouillon' },
    ]

    api.delete.mockResolvedValueOnce({})

    await store.deleteEnlevement(1)

    expect(store.enlevements).toHaveLength(1)
    expect(store.enlevements[0].id).toBe(2)
  })

  it('completeEnlevement updates status in list', async () => {
    const store = useEnlevementsStore()
    store.enlevements = [{ id: 1, statut: 'planifie' }]

    api.post.mockResolvedValueOnce({
      data: { data: { id: 1, statut: 'complete', cout_total: 150 } },
    })

    const result = await store.completeEnlevement(1, { lignes: [] })

    expect(result.statut).toBe('complete')
    expect(store.enlevements[0].statut).toBe('complete')
  })

  it('computed brouillons filters correctly', () => {
    const store = useEnlevementsStore()
    store.enlevements = [
      { id: 1, statut: 'brouillon' },
      { id: 2, statut: 'planifie' },
      { id: 3, statut: 'brouillon' },
    ]

    expect(store.brouillons).toHaveLength(2)
  })

  it('computed planifies filters correctly', () => {
    const store = useEnlevementsStore()
    store.enlevements = [
      { id: 1, statut: 'brouillon' },
      { id: 2, statut: 'planifie' },
      { id: 3, statut: 'complete' },
    ]

    expect(store.planifies).toHaveLength(1)
    expect(store.planifies[0].id).toBe(2)
  })

  it('computed completes filters correctly', () => {
    const store = useEnlevementsStore()
    store.enlevements = [
      { id: 1, statut: 'complete' },
      { id: 2, statut: 'planifie' },
      { id: 3, statut: 'complete' },
    ]

    expect(store.completes).toHaveLength(2)
  })

  it('clearError resets error', () => {
    const store = useEnlevementsStore()
    store.error = 'Some error'

    store.clearError()

    expect(store.error).toBeNull()
  })
})
