import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

export const useSubscriptionStore = defineStore('subscription', () => {
  const status = ref(null)
  const loading = ref(false)

  const plan = computed(() => status.value?.plan || null)
  const onTrial = computed(() => status.value?.on_trial || false)
  const trialDaysRemaining = computed(() => status.value?.days_remaining || 0)
  const subscribed = computed(() => status.value?.subscribed || false)
  const isReadOnly = computed(() => status.value?.read_only || false)
  const subscription = computed(() => status.value?.subscription || null)
  const pmLastFour = computed(() => status.value?.pm_last_four || null)
  const pmType = computed(() => status.value?.pm_type || null)

  async function fetchStatus() {
    loading.value = true
    try {
      const { data } = await api.get('/subscription')
      status.value = data
    } catch {
      // ignore
    } finally {
      loading.value = false
    }
  }

  async function getConfig() {
    const { data } = await api.get('/subscription/config')
    return data.public_key
  }

  async function createSubscription(plan) {
    const { data } = await api.post('/subscription/create', { plan })
    return data
  }

  async function confirmSubscription(subscriptionId, plan) {
    const { data } = await api.post('/subscription/confirm', {
      subscription_id: subscriptionId,
      plan,
    })
    await fetchStatus()
    return data
  }

  async function checkout(plan) {
    const { data } = await api.post('/subscription/checkout', { plan })
    window.location.href = data.checkout_url
  }

  async function openPortal() {
    const { data } = await api.post('/subscription/portal')
    window.location.href = data.portal_url
  }

  async function previewChange(plan) {
    const { data } = await api.post('/subscription/preview-change', { plan })
    return data
  }

  async function changePlan(plan) {
    const { data } = await api.post('/subscription/change-plan', { plan })
    await fetchStatus()
    return data
  }

  async function fetchInvoices() {
    const { data } = await api.get('/subscription/invoices')
    return data
  }

  async function fetchPaymentMethods() {
    const { data } = await api.get('/subscription/payment-methods')
    return data
  }

  function $reset() {
    status.value = null
    loading.value = false
  }

  return {
    status,
    loading,
    plan,
    onTrial,
    trialDaysRemaining,
    subscribed,
    isReadOnly,
    subscription,
    pmLastFour,
    pmType,
    fetchStatus,
    getConfig,
    createSubscription,
    confirmSubscription,
    checkout,
    openPortal,
    previewChange,
    changePlan,
    fetchInvoices,
    fetchPaymentMethods,
    $reset,
  }
})
