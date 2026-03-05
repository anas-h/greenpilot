import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('token'))
  const permissions = ref([])
  const authReady = ref(false)

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.role === 'admin')
  const isSuperAdmin = computed(() => user.value?.role === 'super_admin')

  function hasPermission(permission) {
    if (isAdmin.value || isSuperAdmin.value) return true
    return permissions.value.includes(permission)
  }

  function hasAnyPermission(perms) {
    if (isAdmin.value) return true
    return perms.some(p => permissions.value.includes(p))
  }

  async function login(credentials) {
    const { data } = await api.post('/auth/login', credentials)
    token.value = data.token
    user.value = data.user
    permissions.value = data.permissions || []
    localStorage.setItem('token', data.token)
  }

  async function register(form) {
    const { data } = await api.post('/auth/register', form)
    token.value = data.token
    user.value = data.user
    permissions.value = data.permissions || []
    localStorage.setItem('token', data.token)
    return data
  }

  async function checkAuth() {
    if (!token.value) {
      authReady.value = true
      return
    }
    try {
      const { data } = await api.get('/auth/me')
      user.value = data.user
      permissions.value = data.permissions || []
    } catch {
      logout()
    } finally {
      authReady.value = true
    }
  }

  function logout() {
    if (token.value) {
      api.post('/auth/logout').catch(() => {})
    }
    token.value = null
    user.value = null
    permissions.value = []
    localStorage.removeItem('token')
  }

  return { user, token, permissions, authReady, isAuthenticated, isAdmin, isSuperAdmin, hasPermission, hasAnyPermission, login, register, checkAuth, logout }
})
