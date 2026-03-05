import { describe, it, expect } from 'vitest'

// Test the color logic used in ConteneurJauge component
function getJaugeColor(pourcentage) {
  if (pourcentage >= 95) return 'error'
  if (pourcentage >= 80) return 'warning'
  return 'success'
}

describe('ConteneurJauge Logic', () => {
  it('returns green for low fill', () => {
    expect(getJaugeColor(50)).toBe('success')
  })

  it('returns warning for 80-95% fill', () => {
    expect(getJaugeColor(85)).toBe('warning')
  })

  it('returns error for 95%+ fill', () => {
    expect(getJaugeColor(98)).toBe('error')
  })

  it('handles 0% fill', () => {
    expect(getJaugeColor(0)).toBe('success')
  })

  it('handles 100% fill', () => {
    expect(getJaugeColor(100)).toBe('error')
  })

  it('handles boundary at exactly 80%', () => {
    expect(getJaugeColor(80)).toBe('warning')
  })

  it('handles boundary at exactly 95%', () => {
    expect(getJaugeColor(95)).toBe('error')
  })

  it('handles boundary at 79%', () => {
    expect(getJaugeColor(79)).toBe('success')
  })

  it('handles boundary at 94%', () => {
    expect(getJaugeColor(94)).toBe('warning')
  })
})
