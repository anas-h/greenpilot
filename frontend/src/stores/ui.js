import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useUiStore = defineStore('ui', () => {
  const sidebarOpen = ref(true)
  const snackbar = ref({ show: false, message: '', color: 'success' })

  function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value
  }

  function showSuccess(message) {
    snackbar.value = { show: true, message, color: 'success' }
  }

  function showError(message) {
    snackbar.value = { show: true, message, color: 'error' }
  }

  function showWarning(message) {
    snackbar.value = { show: true, message, color: 'warning' }
  }

  function showInfo(message) {
    snackbar.value = { show: true, message, color: 'info' }
  }

  return { sidebarOpen, snackbar, toggleSidebar, showSuccess, showError, showWarning, showInfo }
})
