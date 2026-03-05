import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useSubscriptionStore } from '../../src/stores/subscription'

vi.mock('../../src/services/api', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
  },
}))

import api from '../../src/services/api'

describe('TrialBanner Logic', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('shows trial banner when on trial', async () => {
    api.get.mockResolvedValueOnce({
      data: {
        plan: 'gratuit',
        on_trial: true,
        days_remaining: 7,
        subscribed: false,
        read_only: false,
      },
    })

    const store = useSubscriptionStore()
    await store.fetchStatus()

    // TrialBanner shows when onTrial is true
    expect(store.onTrial).toBe(true)
    expect(store.isReadOnly).toBe(false)
    expect(store.trialDaysRemaining).toBe(7)
  })

  it('shows expired banner when read-only', async () => {
    api.get.mockResolvedValueOnce({
      data: {
        plan: 'gratuit',
        on_trial: false,
        days_remaining: 0,
        subscribed: false,
        read_only: true,
      },
    })

    const store = useSubscriptionStore()
    await store.fetchStatus()

    // TrialBanner shows expired state when isReadOnly and !onTrial
    expect(store.onTrial).toBe(false)
    expect(store.isReadOnly).toBe(true)
  })

  it('hides banner when subscribed', async () => {
    api.get.mockResolvedValueOnce({
      data: {
        plan: 'standard',
        on_trial: false,
        days_remaining: 0,
        subscribed: true,
        read_only: false,
      },
    })

    const store = useSubscriptionStore()
    await store.fetchStatus()

    // Neither banner shows: !onTrial && !isReadOnly
    expect(store.onTrial).toBe(false)
    expect(store.isReadOnly).toBe(false)
    expect(store.subscribed).toBe(true)
  })

  it('shows singular "jour" for 1 day remaining', async () => {
    api.get.mockResolvedValueOnce({
      data: {
        plan: 'gratuit',
        on_trial: true,
        days_remaining: 1,
        subscribed: false,
        read_only: false,
      },
    })

    const store = useSubscriptionStore()
    await store.fetchStatus()

    // Template: {{ days_remaining }} jour{{ days > 1 ? 's' : '' }}
    const days = store.trialDaysRemaining
    const suffix = days > 1 ? 's' : ''
    expect(suffix).toBe('')
    expect(`${days} jour${suffix}`).toBe('1 jour')
  })

  it('shows plural "jours" for multiple days', async () => {
    api.get.mockResolvedValueOnce({
      data: {
        plan: 'gratuit',
        on_trial: true,
        days_remaining: 5,
        subscribed: false,
        read_only: false,
      },
    })

    const store = useSubscriptionStore()
    await store.fetchStatus()

    const days = store.trialDaysRemaining
    const suffix = days > 1 ? 's' : ''
    expect(suffix).toBe('s')
    expect(`${days} jour${suffix}`).toBe('5 jours')
  })
})
