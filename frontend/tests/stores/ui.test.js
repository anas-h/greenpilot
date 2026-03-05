import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useUiStore } from '../../src/stores/ui'

describe('UI Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('starts with sidebar open', () => {
    const store = useUiStore()
    expect(store.sidebarOpen).toBe(true)
  })

  it('starts with snackbar hidden', () => {
    const store = useUiStore()
    expect(store.snackbar.show).toBe(false)
    expect(store.snackbar.message).toBe('')
    expect(store.snackbar.color).toBe('success')
  })

  it('toggleSidebar switches state', () => {
    const store = useUiStore()
    expect(store.sidebarOpen).toBe(true)

    store.toggleSidebar()
    expect(store.sidebarOpen).toBe(false)

    store.toggleSidebar()
    expect(store.sidebarOpen).toBe(true)
  })

  it('showSuccess sets snackbar with success color', () => {
    const store = useUiStore()
    store.showSuccess('Opération réussie')

    expect(store.snackbar.show).toBe(true)
    expect(store.snackbar.message).toBe('Opération réussie')
    expect(store.snackbar.color).toBe('success')
  })

  it('showError sets snackbar with error color', () => {
    const store = useUiStore()
    store.showError('Une erreur est survenue')

    expect(store.snackbar.show).toBe(true)
    expect(store.snackbar.message).toBe('Une erreur est survenue')
    expect(store.snackbar.color).toBe('error')
  })

  it('showWarning sets snackbar with warning color', () => {
    const store = useUiStore()
    store.showWarning('Attention')

    expect(store.snackbar.show).toBe(true)
    expect(store.snackbar.message).toBe('Attention')
    expect(store.snackbar.color).toBe('warning')
  })

  it('showInfo sets snackbar with info color', () => {
    const store = useUiStore()
    store.showInfo('Information')

    expect(store.snackbar.show).toBe(true)
    expect(store.snackbar.message).toBe('Information')
    expect(store.snackbar.color).toBe('info')
  })

  it('consecutive snackbar calls overwrite previous', () => {
    const store = useUiStore()
    store.showSuccess('First')
    store.showError('Second')

    expect(store.snackbar.message).toBe('Second')
    expect(store.snackbar.color).toBe('error')
  })
})
