import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAlertesStore } from '../../src/stores/alertes'

vi.mock('../../src/services/api', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
  },
}))

import api from '../../src/services/api'

describe('Alertes Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('starts with empty state', () => {
    const store = useAlertesStore()
    expect(store.alertes).toEqual([])
    expect(store.totalAlertes).toBe(0)
    expect(store.counts).toEqual({ total: 0, critique: 0, haute: 0, moyenne: 0, basse: 0 })
    expect(store.loading).toBe(false)
    expect(store.error).toBeNull()
  })

  it('fetchAlertes loads list', async () => {
    api.get.mockResolvedValueOnce({
      data: {
        data: [
          { id: 1, type: 'capacite', priorite: 'haute' },
          { id: 2, type: 'expiration', priorite: 'moyenne' },
        ],
        total: 2,
      },
    })

    const store = useAlertesStore()
    await store.fetchAlertes()

    expect(store.alertes).toHaveLength(2)
    expect(store.totalAlertes).toBe(2)
    expect(store.loading).toBe(false)
  })

  it('fetchAlertes passes filters to API', async () => {
    api.get.mockResolvedValueOnce({ data: { data: [], total: 0 } })

    const store = useAlertesStore()
    await store.fetchAlertes({ type: 'capacite', priorite: 'haute' })

    expect(api.get).toHaveBeenCalledWith('/alertes', {
      params: { type: 'capacite', priorite: 'haute' },
    })
  })

  it('fetchAlertes sets error on failure', async () => {
    api.get.mockRejectedValueOnce({
      response: { data: { message: 'Erreur serveur' } },
    })

    const store = useAlertesStore()
    await expect(store.fetchAlertes()).rejects.toBeTruthy()

    expect(store.error).toBe('Erreur serveur')
  })

  it('fetchCounts updates counts', async () => {
    api.get.mockResolvedValueOnce({
      data: { total: 5, critique: 1, haute: 2, moyenne: 1, basse: 1 },
    })

    const store = useAlertesStore()
    await store.fetchCounts()

    expect(store.counts.total).toBe(5)
    expect(store.counts.critique).toBe(1)
  })

  it('fetchCounts fails silently', async () => {
    api.get.mockRejectedValueOnce(new Error('Network error'))

    const store = useAlertesStore()
    await store.fetchCounts()

    expect(store.counts.total).toBe(0)
  })

  it('markRead updates local alerte and refreshes counts', async () => {
    api.post.mockResolvedValueOnce({ data: { message: 'ok' } })
    api.get.mockResolvedValueOnce({ data: { total: 0, critique: 0, haute: 0, moyenne: 0, basse: 0 } })

    const store = useAlertesStore()
    store.alertes = [{ id: 1, lue: false, type: 'capacite' }]

    await store.markRead(1)

    expect(store.alertes[0].lue).toBe(true)
    expect(api.post).toHaveBeenCalledWith('/alertes/mark-read', { id: 1 })
    expect(api.get).toHaveBeenCalledWith('/alertes/count')
  })

  it('markAllRead updates all local alertes', async () => {
    api.post.mockResolvedValueOnce({ data: { message: 'ok' } })
    api.get.mockResolvedValueOnce({ data: { total: 0, critique: 0, haute: 0, moyenne: 0, basse: 0 } })

    const store = useAlertesStore()
    store.alertes = [
      { id: 1, lue: false },
      { id: 2, lue: false },
    ]

    await store.markAllRead()

    expect(store.alertes[0].lue).toBe(true)
    expect(store.alertes[1].lue).toBe(true)
    expect(api.post).toHaveBeenCalledWith('/alertes/mark-read')
  })

  it('resolve updates local alerte state', async () => {
    api.post.mockResolvedValueOnce({ data: { message: 'ok' } })
    api.get.mockResolvedValueOnce({ data: { total: 0, critique: 0, haute: 0, moyenne: 0, basse: 0 } })

    const store = useAlertesStore()
    store.alertes = [{ id: 1, resolue: false, lue: false }]

    await store.resolve(1)

    expect(store.alertes[0].resolue).toBe(true)
    expect(store.alertes[0].lue).toBe(true)
    expect(api.post).toHaveBeenCalledWith('/alertes/1/resolve')
  })

  it('startPolling fetches counts immediately and sets interval', async () => {
    api.get.mockResolvedValue({ data: { total: 3, critique: 1, haute: 1, moyenne: 1, basse: 0 } })

    const store = useAlertesStore()
    store.startPolling()

    expect(api.get).toHaveBeenCalledWith('/alertes/count')

    vi.advanceTimersByTime(60000)
    expect(api.get).toHaveBeenCalledTimes(2)

    vi.advanceTimersByTime(60000)
    expect(api.get).toHaveBeenCalledTimes(3)

    store.stopPolling()
  })

  it('stopPolling clears interval', () => {
    api.get.mockResolvedValue({ data: { total: 0, critique: 0, haute: 0, moyenne: 0, basse: 0 } })

    const store = useAlertesStore()
    store.startPolling()

    vi.clearAllMocks()
    store.stopPolling()

    vi.advanceTimersByTime(120000)
    expect(api.get).not.toHaveBeenCalled()
  })

  it('$reset clears all state and stops polling', () => {
    api.get.mockResolvedValue({ data: { total: 0, critique: 0, haute: 0, moyenne: 0, basse: 0 } })

    const store = useAlertesStore()
    store.alertes = [{ id: 1 }]
    store.totalAlertes = 1
    store.error = 'error'
    store.startPolling()

    store.$reset()

    expect(store.alertes).toEqual([])
    expect(store.totalAlertes).toBe(0)
    expect(store.error).toBeNull()

    vi.clearAllMocks()
    vi.advanceTimersByTime(120000)
    expect(api.get).not.toHaveBeenCalled()
  })
})
