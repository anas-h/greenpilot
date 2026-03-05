import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '../../src/stores/auth'

// Mock api module
vi.mock('../../src/services/api', () => ({
  default: {
    post: vi.fn(),
    get: vi.fn(),
  },
}))

import api from '../../src/services/api'

describe('Auth Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
  })

  it('starts unauthenticated', () => {
    const store = useAuthStore()
    expect(store.isAuthenticated).toBe(false)
    expect(store.user).toBeNull()
  })

  it('sets user and token on login', async () => {
    api.post.mockResolvedValueOnce({
      data: {
        token: 'test-token-123',
        user: { id: 1, nom: 'Test', prenom: 'User', role: 'admin' },
        permissions: ['dashboard.view', 'users.view'],
      },
    })

    const store = useAuthStore()
    await store.login({ email: 'test@test.com', password: 'password' })

    expect(store.isAuthenticated).toBe(true)
    expect(store.token).toBe('test-token-123')
    expect(store.user.nom).toBe('Test')
    expect(store.permissions).toContain('dashboard.view')
    expect(localStorage.getItem('token')).toBe('test-token-123')
  })

  it('clears state on logout', async () => {
    api.post.mockResolvedValueOnce({
      data: {
        token: 'test-token',
        user: { id: 1, role: 'admin' },
        permissions: [],
      },
    })

    const store = useAuthStore()
    await store.login({ email: 'test@test.com', password: 'password' })

    api.post.mockResolvedValueOnce({})
    store.logout()

    expect(store.isAuthenticated).toBe(false)
    expect(store.token).toBeNull()
    expect(store.user).toBeNull()
    expect(localStorage.getItem('token')).toBeNull()
  })

  it('admin has all permissions', async () => {
    api.post.mockResolvedValueOnce({
      data: {
        token: 'token',
        user: { id: 1, role: 'admin' },
        permissions: [],
      },
    })

    const store = useAuthStore()
    await store.login({ email: 'admin@test.com', password: 'password' })

    expect(store.hasPermission('anything')).toBe(true)
  })

  it('non-admin checks permission list', async () => {
    api.post.mockResolvedValueOnce({
      data: {
        token: 'token',
        user: { id: 1, role: 'mecanicien' },
        permissions: ['productions.create', 'productions.view'],
      },
    })

    const store = useAuthStore()
    await store.login({ email: 'meca@test.com', password: 'password' })

    expect(store.hasPermission('productions.create')).toBe(true)
    expect(store.hasPermission('users.view')).toBe(false)
  })

  it('checkAuth loads user from token', async () => {
    localStorage.setItem('token', 'existing-token')

    api.get.mockResolvedValueOnce({
      data: {
        user: { id: 1, nom: 'Existing', role: 'admin' },
        permissions: ['dashboard.view'],
      },
    })

    const store = useAuthStore()
    await store.checkAuth()

    expect(store.user.nom).toBe('Existing')
    expect(store.isAuthenticated).toBe(true)
  })

  it('checkAuth logs out on error', async () => {
    localStorage.setItem('token', 'expired-token')

    api.get.mockRejectedValueOnce(new Error('Unauthorized'))
    // logout() will call api.post('/auth/logout') which needs a mock
    api.post.mockResolvedValueOnce({})

    const store = useAuthStore()
    await store.checkAuth()

    expect(store.isAuthenticated).toBe(false)
    expect(store.token).toBeNull()
  })
})
