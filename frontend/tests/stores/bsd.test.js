import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useBsdStore } from '../../src/stores/bsd'

vi.mock('../../src/services/api', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}))

import api from '../../src/services/api'

describe('BSD Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('starts with empty state', () => {
    const store = useBsdStore()
    expect(store.bordereaux).toEqual([])
    expect(store.totalBordereaux).toBe(0)
    expect(store.currentBordereau).toBeNull()
    expect(store.loading).toBe(false)
    expect(store.error).toBeNull()
  })

  it('fetchBordereaux loads list', async () => {
    api.get.mockResolvedValueOnce({
      data: {
        data: [
          { id: 1, statut: 'DRAFT', code_dechet: '13 02 05*' },
          { id: 2, statut: 'SEALED', code_dechet: '14 06 01*' },
        ],
        total: 2,
      },
    })

    const store = useBsdStore()
    await store.fetchBordereaux()

    expect(store.bordereaux).toHaveLength(2)
    expect(store.totalBordereaux).toBe(2)
    expect(store.loading).toBe(false)
  })

  it('fetchBordereaux sets error on failure', async () => {
    api.get.mockRejectedValueOnce({
      response: { data: { message: 'Server error' } },
    })

    const store = useBsdStore()
    await expect(store.fetchBordereaux()).rejects.toBeTruthy()

    expect(store.error).toBe('Server error')
    expect(store.loading).toBe(false)
  })

  it('fetchBordereau loads single item', async () => {
    api.get.mockResolvedValueOnce({
      data: { id: 1, statut: 'DRAFT', code_dechet: '13 02 05*' },
    })

    const store = useBsdStore()
    await store.fetchBordereau(1)

    expect(store.currentBordereau).toEqual({ id: 1, statut: 'DRAFT', code_dechet: '13 02 05*' })
  })

  it('createBordereau adds to list', async () => {
    api.post.mockResolvedValueOnce({
      data: { id: 3, statut: 'DRAFT', code_dechet: '16 06 01*' },
    })

    const store = useBsdStore()
    const result = await store.createBordereau({ code_dechet: '16 06 01*' })

    expect(result.id).toBe(3)
    expect(store.bordereaux).toHaveLength(1)
    expect(store.totalBordereaux).toBe(1)
  })

  it('updateBordereau updates in list', async () => {
    const store = useBsdStore()
    store.bordereaux = [{ id: 1, statut: 'DRAFT', code_dechet: '13 02 05*' }]

    api.put.mockResolvedValueOnce({
      data: { id: 1, statut: 'DRAFT', code_dechet: '14 06 01*' },
    })

    await store.updateBordereau(1, { code_dechet: '14 06 01*' })

    expect(store.bordereaux[0].code_dechet).toBe('14 06 01*')
  })

  it('deleteBordereau removes from list', async () => {
    const store = useBsdStore()
    store.bordereaux = [
      { id: 1, statut: 'DRAFT' },
      { id: 2, statut: 'DRAFT' },
    ]
    store.totalBordereaux = 2

    api.delete.mockResolvedValueOnce({})

    await store.deleteBordereau(1)

    expect(store.bordereaux).toHaveLength(1)
    expect(store.bordereaux[0].id).toBe(2)
    expect(store.totalBordereaux).toBe(1)
  })

  it('deleteBordereau clears currentBordereau if deleted', async () => {
    const store = useBsdStore()
    store.bordereaux = [{ id: 1, statut: 'DRAFT' }]
    store.currentBordereau = { id: 1, statut: 'DRAFT' }
    store.totalBordereaux = 1

    api.delete.mockResolvedValueOnce({})
    await store.deleteBordereau(1)

    expect(store.currentBordereau).toBeNull()
  })

  it('publishBordereau updates status to SEALED', async () => {
    const store = useBsdStore()
    store.bordereaux = [{ id: 1, statut: 'DRAFT' }]

    api.post.mockResolvedValueOnce({
      data: { id: 1, statut: 'SEALED' },
    })

    await store.publishBordereau(1)

    expect(store.bordereaux[0].statut).toBe('SEALED')
  })

  it('signBordereau updates status to SIGNED_BY_PRODUCER', async () => {
    const store = useBsdStore()
    store.bordereaux = [{ id: 1, statut: 'SEALED' }]

    api.post.mockResolvedValueOnce({
      data: { id: 1, statut: 'SIGNED_BY_PRODUCER' },
    })

    await store.signBordereau(1)

    expect(store.bordereaux[0].statut).toBe('SIGNED_BY_PRODUCER')
  })

  it('cancelBordereau updates status to CANCELED', async () => {
    const store = useBsdStore()
    store.bordereaux = [{ id: 1, statut: 'DRAFT' }]

    api.post.mockResolvedValueOnce({
      data: { id: 1, statut: 'CANCELED' },
    })

    await store.cancelBordereau(1, 'Erreur de saisie')

    expect(api.post).toHaveBeenCalledWith('/bordereaux/1/cancel', {
      motif_annulation: 'Erreur de saisie',
    })
    expect(store.bordereaux[0].statut).toBe('CANCELED')
  })

  it('dupliquerBordereau adds new copy to list', async () => {
    const store = useBsdStore()
    store.bordereaux = [{ id: 1, statut: 'REFUSED' }]
    store.totalBordereaux = 1

    api.post.mockResolvedValueOnce({
      data: { id: 2, statut: 'DRAFT', code_dechet: '13 02 05*' },
    })

    const result = await store.dupliquerBordereau(1)

    expect(result.id).toBe(2)
    expect(result.statut).toBe('DRAFT')
    expect(store.bordereaux).toHaveLength(2)
    expect(store.totalBordereaux).toBe(2)
  })

  it('draftCount returns count of DRAFT bordereaux', () => {
    const store = useBsdStore()
    store.bordereaux = [
      { id: 1, statut: 'DRAFT' },
      { id: 2, statut: 'SEALED' },
      { id: 3, statut: 'DRAFT' },
    ]

    expect(store.draftCount).toBe(2)
  })

  it('activeCount returns count of active bordereaux', () => {
    const store = useBsdStore()
    store.bordereaux = [
      { id: 1, statut: 'DRAFT' },
      { id: 2, statut: 'SEALED' },
      { id: 3, statut: 'SIGNED_BY_PRODUCER' },
      { id: 4, statut: 'SENT' },
      { id: 5, statut: 'PROCESSED' },
    ]

    expect(store.activeCount).toBe(3)
  })

  it('bordereauById returns correct item', () => {
    const store = useBsdStore()
    store.bordereaux = [
      { id: 1, code_dechet: 'A' },
      { id: 2, code_dechet: 'B' },
    ]

    expect(store.bordereauById(2)).toEqual({ id: 2, code_dechet: 'B' })
    expect(store.bordereauById(99)).toBeUndefined()
  })

  it('bordereauByStatut returns filtered items', () => {
    const store = useBsdStore()
    store.bordereaux = [
      { id: 1, statut: 'DRAFT' },
      { id: 2, statut: 'SEALED' },
      { id: 3, statut: 'DRAFT' },
    ]

    expect(store.bordereauByStatut('DRAFT')).toHaveLength(2)
    expect(store.bordereauByStatut('SEALED')).toHaveLength(1)
  })

  it('$reset clears all state', () => {
    const store = useBsdStore()
    store.bordereaux = [{ id: 1 }]
    store.totalBordereaux = 1
    store.currentBordereau = { id: 1 }
    store.error = 'some error'

    store.$reset()

    expect(store.bordereaux).toEqual([])
    expect(store.totalBordereaux).toBe(0)
    expect(store.currentBordereau).toBeNull()
    expect(store.loading).toBe(false)
    expect(store.error).toBeNull()
  })
})
