import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useSubscriptionStore } from '../../src/stores/subscription'

vi.mock('../../src/services/api', () => ({
  default: {
    post: vi.fn(),
    get: vi.fn(),
  },
}))

import api from '../../src/services/api'

describe('Subscription Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('starts with null status', () => {
    const store = useSubscriptionStore()
    expect(store.status).toBeNull()
    expect(store.loading).toBe(false)
    expect(store.plan).toBeNull()
  })

  it('fetchStatus sets status data', async () => {
    api.get.mockResolvedValueOnce({
      data: {
        plan: 'standard',
        on_trial: true,
        days_remaining: 10,
        subscribed: false,
        read_only: false,
        pm_last_four: '4242',
        pm_type: 'card',
      },
    })

    const store = useSubscriptionStore()
    await store.fetchStatus()

    expect(store.plan).toBe('standard')
    expect(store.onTrial).toBe(true)
    expect(store.trialDaysRemaining).toBe(10)
    expect(store.subscribed).toBe(false)
    expect(store.isReadOnly).toBe(false)
    expect(store.pmLastFour).toBe('4242')
    expect(store.pmType).toBe('card')
    expect(store.loading).toBe(false)
  })

  it('fetchStatus handles errors silently', async () => {
    api.get.mockRejectedValueOnce(new Error('Network error'))

    const store = useSubscriptionStore()
    await store.fetchStatus()

    expect(store.status).toBeNull()
    expect(store.loading).toBe(false)
  })

  it('computed isReadOnly returns true when read_only', async () => {
    api.get.mockResolvedValueOnce({
      data: { plan: 'gratuit', read_only: true, on_trial: false, subscribed: false },
    })

    const store = useSubscriptionStore()
    await store.fetchStatus()

    expect(store.isReadOnly).toBe(true)
  })

  it('computed subscription returns subscription object', async () => {
    const sub = { stripe_status: 'active', stripe_price: 'price_123' }
    api.get.mockResolvedValueOnce({
      data: { plan: 'premium', subscription: sub, on_trial: false, subscribed: true },
    })

    const store = useSubscriptionStore()
    await store.fetchStatus()

    expect(store.subscription).toEqual(sub)
  })

  it('getConfig returns public key', async () => {
    api.get.mockResolvedValueOnce({ data: { public_key: 'pk_test_123' } })

    const store = useSubscriptionStore()
    const key = await store.getConfig()

    expect(key).toBe('pk_test_123')
    expect(api.get).toHaveBeenCalledWith('/subscription/config')
  })

  it('createSubscription calls API with plan', async () => {
    api.post.mockResolvedValueOnce({
      data: { subscription_id: 'sub_123', client_secret: 'cs_123', type: 'payment_intent' },
    })

    const store = useSubscriptionStore()
    const result = await store.createSubscription('premium')

    expect(api.post).toHaveBeenCalledWith('/subscription/create', { plan: 'premium' })
    expect(result.subscription_id).toBe('sub_123')
    expect(result.client_secret).toBe('cs_123')
  })

  it('confirmSubscription calls API and refreshes status', async () => {
    api.post.mockResolvedValueOnce({
      data: { message: 'Abonnement active avec succes.', plan: 'standard' },
    })
    api.get.mockResolvedValueOnce({
      data: { plan: 'standard', subscribed: true, on_trial: false },
    })

    const store = useSubscriptionStore()
    const result = await store.confirmSubscription('sub_123', 'standard')

    expect(api.post).toHaveBeenCalledWith('/subscription/confirm', {
      subscription_id: 'sub_123',
      plan: 'standard',
    })
    expect(result.plan).toBe('standard')
    expect(api.get).toHaveBeenCalledWith('/subscription')
  })

  it('changePlan calls API and refreshes status', async () => {
    api.post.mockResolvedValueOnce({
      data: { message: 'Plan mis a jour.', plan: 'premium' },
    })
    api.get.mockResolvedValueOnce({
      data: { plan: 'premium', subscribed: true },
    })

    const store = useSubscriptionStore()
    const result = await store.changePlan('premium')

    expect(api.post).toHaveBeenCalledWith('/subscription/change-plan', { plan: 'premium' })
    expect(result.plan).toBe('premium')
  })

  it('previewChange calls API with plan', async () => {
    api.post.mockResolvedValueOnce({
      data: { can_change: true, direction: 'upgrade', new_plan: 'premium' },
    })

    const store = useSubscriptionStore()
    const result = await store.previewChange('premium')

    expect(result.can_change).toBe(true)
    expect(result.direction).toBe('upgrade')
  })

  it('$reset clears all state', async () => {
    api.get.mockResolvedValueOnce({
      data: { plan: 'standard', on_trial: true },
    })

    const store = useSubscriptionStore()
    await store.fetchStatus()
    expect(store.status).not.toBeNull()

    store.$reset()
    expect(store.status).toBeNull()
    expect(store.loading).toBe(false)
  })
})
