import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAdminStore } from '../../src/stores/admin'

vi.mock('../../src/services/api', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}))

import api from '../../src/services/api'

describe('Admin Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('starts with empty state', () => {
    const store = useAdminStore()
    expect(store.entreprises).toEqual([])
    expect(store.currentEntreprise).toBeNull()
    expect(store.users).toEqual([])
    expect(store.stats).toBeNull()
    expect(store.loading).toBe(false)
  })

  it('fetchEntreprises loads list with pagination', async () => {
    api.get.mockResolvedValueOnce({
      data: {
        data: [{ id: 1, raison_sociale: 'Test SARL' }],
        current_page: 1,
        last_page: 3,
        total: 25,
      },
    })

    const store = useAdminStore()
    await store.fetchEntreprises()

    expect(store.entreprises).toHaveLength(1)
    expect(store.entreprisesPagination.total).toBe(25)
    expect(store.loading).toBe(false)
  })

  it('fetchEntreprise loads single enterprise', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: { id: 1, raison_sociale: 'Test SARL', garages: [] } },
    })

    const store = useAdminStore()
    await store.fetchEntreprise(1)

    expect(store.currentEntreprise.id).toBe(1)
  })

  it('createEntreprise calls API', async () => {
    api.post.mockResolvedValueOnce({
      data: { data: { id: 2, raison_sociale: 'New Corp' } },
    })

    const store = useAdminStore()
    const result = await store.createEntreprise({ raison_sociale: 'New Corp' })

    expect(result.id).toBe(2)
    expect(api.post).toHaveBeenCalledWith('/admin/entreprises', { raison_sociale: 'New Corp' })
  })

  it('updateEntreprise updates current', async () => {
    api.put.mockResolvedValueOnce({
      data: { data: { id: 1, raison_sociale: 'Updated' } },
    })

    const store = useAdminStore()
    await store.updateEntreprise(1, { raison_sociale: 'Updated' })

    expect(store.currentEntreprise.raison_sociale).toBe('Updated')
  })

  it('toggleActive updates list and current', async () => {
    const store = useAdminStore()
    store.entreprises = [{ id: 1, actif: true }]
    store.currentEntreprise = { id: 1, actif: true }

    api.post.mockResolvedValueOnce({
      data: { data: { id: 1, actif: false } },
    })

    await store.toggleActive(1)

    expect(store.entreprises[0].actif).toBe(false)
    expect(store.currentEntreprise.actif).toBe(false)
  })

  it('toggleArchive updates list and current', async () => {
    const store = useAdminStore()
    store.entreprises = [{ id: 1, archived_at: null }]
    store.currentEntreprise = { id: 1, archived_at: null }

    api.post.mockResolvedValueOnce({
      data: { data: { id: 1, archived_at: '2026-01-01' } },
    })

    await store.toggleArchive(1)

    expect(store.entreprises[0].archived_at).toBe('2026-01-01')
  })

  it('fetchStats loads statistics', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: { total_entreprises: 10, total_users: 50 } },
    })

    const store = useAdminStore()
    await store.fetchStats()

    expect(store.stats.total_entreprises).toBe(10)
  })

  it('fetchUsers loads user list with pagination', async () => {
    api.get.mockResolvedValueOnce({
      data: {
        data: [{ id: 1, nom: 'Test' }],
        current_page: 1,
        last_page: 1,
        total: 1,
      },
    })

    const store = useAdminStore()
    await store.fetchUsers()

    expect(store.users).toHaveLength(1)
    expect(store.usersPagination.total).toBe(1)
  })

  it('createGarage calls API and refreshes enterprise', async () => {
    const store = useAdminStore()
    store.currentEntreprise = { id: 1 }

    api.post.mockResolvedValueOnce({
      data: { data: { id: 5, nom: 'New Garage' } },
    })
    api.get.mockResolvedValueOnce({
      data: { data: { id: 1, garages: [{ id: 5 }] } },
    })

    const result = await store.createGarage(1, { nom: 'New Garage' })

    expect(result.nom).toBe('New Garage')
    expect(api.get).toHaveBeenCalledWith('/admin/entreprises/1')
  })

  it('deleteUser calls API', async () => {
    api.delete.mockResolvedValueOnce({ data: { message: 'ok' } })

    const store = useAdminStore()
    store.currentEntreprise = null

    await store.deleteUser(1)

    expect(api.delete).toHaveBeenCalledWith('/admin/users/1')
  })

  it('loginAs returns impersonation data', async () => {
    api.post.mockResolvedValueOnce({
      data: { token: 'imp-token', user: { id: 5 }, impersonating: true },
    })

    const store = useAdminStore()
    const result = await store.loginAs(5)

    expect(result.token).toBe('imp-token')
    expect(result.impersonating).toBe(true)
  })

  it('fetchBordereaux loads with pagination', async () => {
    api.get.mockResolvedValueOnce({
      data: {
        data: [{ id: 1, statut: 'DRAFT' }],
        current_page: 1,
        last_page: 1,
        total: 1,
      },
    })

    const store = useAdminStore()
    await store.fetchBordereaux()

    expect(store.bordereaux).toHaveLength(1)
    expect(store.bordereauxPagination.total).toBe(1)
  })

  it('retryBsd calls API', async () => {
    api.post.mockResolvedValueOnce({ data: { message: 'Retry lancé' } })

    const store = useAdminStore()
    const result = await store.retryBsd(1)

    expect(api.post).toHaveBeenCalledWith('/admin/bordereaux/1/retry')
    expect(result.message).toBe('Retry lancé')
  })
})
