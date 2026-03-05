import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useDechetsStore } from '../../src/stores/dechets'

vi.mock('../../src/services/api', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}))

import api from '../../src/services/api'

describe('Dechets Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('starts with empty state', () => {
    const store = useDechetsStore()
    expect(store.typesDechets).toEqual([])
    expect(store.conteneurs).toEqual([])
    expect(store.loadingTypes).toBe(false)
    expect(store.loadingConteneurs).toBe(false)
  })

  // Types tests
  it('fetchTypes loads waste types', async () => {
    api.get.mockResolvedValueOnce({
      data: {
        data: [
          { id: 1, denomination: 'Huiles usagées', dangereux: true, categorie: 'huile' },
          { id: 2, denomination: 'Pneus', dangereux: false, categorie: 'pneu' },
        ],
      },
    })

    const store = useDechetsStore()
    await store.fetchTypes()

    expect(store.typesDechets).toHaveLength(2)
    expect(store.loadingTypes).toBe(false)
  })

  it('fetchSystemTypes returns system types', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: [{ id: 1, denomination: 'System Type' }] },
    })

    const store = useDechetsStore()
    const result = await store.fetchSystemTypes()

    expect(result).toHaveLength(1)
    expect(api.get).toHaveBeenCalledWith('/types-dechets/systeme')
  })

  it('createType adds to list', async () => {
    api.post.mockResolvedValueOnce({
      data: { data: { id: 3, denomination: 'New Type', dangereux: false } },
    })

    const store = useDechetsStore()
    const result = await store.createType({ denomination: 'New Type' })

    expect(result.id).toBe(3)
    expect(store.typesDechets).toHaveLength(1)
  })

  it('updateType updates in list', async () => {
    const store = useDechetsStore()
    store.typesDechets = [{ id: 1, denomination: 'Old Name', dangereux: true }]

    api.put.mockResolvedValueOnce({
      data: { data: { id: 1, denomination: 'New Name', dangereux: true } },
    })

    await store.updateType(1, { denomination: 'New Name' })

    expect(store.typesDechets[0].denomination).toBe('New Name')
  })

  it('deleteType removes from list', async () => {
    const store = useDechetsStore()
    store.typesDechets = [
      { id: 1, denomination: 'A' },
      { id: 2, denomination: 'B' },
    ]

    api.delete.mockResolvedValueOnce({})

    await store.deleteType(1)

    expect(store.typesDechets).toHaveLength(1)
    expect(store.typesDechets[0].id).toBe(2)
  })

  it('toggleTypeActivation calls API and refreshes', async () => {
    api.post.mockResolvedValueOnce({ data: { actif: true } })
    api.get.mockResolvedValueOnce({
      data: { data: [{ id: 1, denomination: 'Type', garage_config: { actif: true } }] },
    })

    const store = useDechetsStore()
    await store.toggleTypeActivation(1)

    expect(api.post).toHaveBeenCalledWith('/types-dechets/1/toggle')
    expect(api.get).toHaveBeenCalledWith('/types-dechets', { params: {} })
  })

  // Computed getters
  it('typesDangereux filters dangerous types', () => {
    const store = useDechetsStore()
    store.typesDechets = [
      { id: 1, dangereux: true },
      { id: 2, dangereux: false },
      { id: 3, dangereux: true },
    ]

    expect(store.typesDangereux).toHaveLength(2)
  })

  it('typesNonDangereux filters non-dangerous types', () => {
    const store = useDechetsStore()
    store.typesDechets = [
      { id: 1, dangereux: true },
      { id: 2, dangereux: false },
    ]

    expect(store.typesNonDangereux).toHaveLength(1)
    expect(store.typesNonDangereux[0].id).toBe(2)
  })

  it('typesActifs filters types with active garage config', () => {
    const store = useDechetsStore()
    store.typesDechets = [
      { id: 1, garage_config: { actif: true } },
      { id: 2, garage_config: { actif: false } },
      { id: 3, garage_config: null },
    ]

    expect(store.typesActifs).toHaveLength(1)
    expect(store.typesActifs[0].id).toBe(1)
  })

  it('categories returns unique sorted categories', () => {
    const store = useDechetsStore()
    store.typesDechets = [
      { id: 1, categorie: 'huile' },
      { id: 2, categorie: 'pneu' },
      { id: 3, categorie: 'huile' },
      { id: 4, categorie: 'batterie' },
    ]

    expect(store.categories).toEqual(['batterie', 'huile', 'pneu'])
  })

  // Conteneurs tests
  it('fetchConteneurs loads containers', async () => {
    api.get.mockResolvedValueOnce({
      data: {
        data: [
          { id: 1, nom: 'Fut huiles', capacite: 200 },
          { id: 2, nom: 'Bac pneus', capacite: 50 },
        ],
      },
    })

    const store = useDechetsStore()
    await store.fetchConteneurs()

    expect(store.conteneurs).toHaveLength(2)
    expect(store.loadingConteneurs).toBe(false)
  })

  it('createConteneur adds to list', async () => {
    api.post.mockResolvedValueOnce({
      data: { data: { id: 3, nom: 'New Container', capacite: 100 } },
    })

    const store = useDechetsStore()
    const result = await store.createConteneur({ nom: 'New Container' })

    expect(result.id).toBe(3)
    expect(store.conteneurs).toHaveLength(1)
  })

  it('updateConteneur updates in list', async () => {
    const store = useDechetsStore()
    store.conteneurs = [{ id: 1, nom: 'Old', capacite: 100 }]

    api.put.mockResolvedValueOnce({
      data: { data: { id: 1, nom: 'Updated', capacite: 200 } },
    })

    await store.updateConteneur(1, { nom: 'Updated', capacite: 200 })

    expect(store.conteneurs[0].nom).toBe('Updated')
    expect(store.conteneurs[0].capacite).toBe(200)
  })

  it('deleteConteneur removes from list', async () => {
    const store = useDechetsStore()
    store.conteneurs = [{ id: 1 }, { id: 2 }]

    api.delete.mockResolvedValueOnce({})

    await store.deleteConteneur(1)

    expect(store.conteneurs).toHaveLength(1)
    expect(store.conteneurs[0].id).toBe(2)
  })
})
