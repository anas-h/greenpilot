import axios from 'axios'
import { useAuthStore } from '../stores/auth'
import { useGarageStore } from '../stores/garage'
import router from '../router'

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true,
})

api.interceptors.request.use((config) => {
  const authStore = useAuthStore()
  const garageStore = useGarageStore()

  if (authStore.token) {
    config.headers.Authorization = `Bearer ${authStore.token}`
  }
  if (garageStore.currentGarageId) {
    config.headers['X-Garage-Id'] = String(garageStore.currentGarageId)
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      const authStore = useAuthStore()
      authStore.logout()
      const currentRoute = router.currentRoute.value
      if (!currentRoute.meta?.guest) {
        router.push({ name: 'login' })
      }
    }

    // Handle read-only mode (trial expired)
    if (error.response?.status === 403 && error.response?.data?.read_only) {
      router.push({ name: 'abonnement' })
    }

    return Promise.reject(error)
  }
)

export default api
