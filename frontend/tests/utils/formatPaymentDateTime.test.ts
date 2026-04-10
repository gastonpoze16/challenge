import { describe, it, expect } from 'vitest'
import { formatPaymentDateTime } from '~/utils/formatPaymentDateTime'

describe('formatPaymentDateTime', () => {
  it('formats a valid ISO string', () => {
    const result = formatPaymentDateTime('2026-04-10T14:30:00Z')
    expect(result).toContain('4/10/26')
    expect(result).toMatch(/\d{1,2}:\d{2}\s*(AM|PM)/i)
  })

  it('returns original string for invalid date', () => {
    expect(formatPaymentDateTime('not-a-date')).toBe('not-a-date')
  })

  it('returns original string for empty string', () => {
    expect(formatPaymentDateTime('')).toBe('')
  })

  it('handles midnight UTC (timezone-dependent output)', () => {
    const result = formatPaymentDateTime('2026-01-15T12:00:00Z')
    expect(result).toMatch(/1\/15\/26/)
  })

  it('uses en-US locale consistently', () => {
    const result = formatPaymentDateTime('2026-12-25T18:00:00Z')
    expect(result).toContain('12/25/26')
  })
})
