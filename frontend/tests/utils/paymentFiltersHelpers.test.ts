import { describe, it, expect } from 'vitest'
import { parseYmd, formatYmd, normalizeCurrencyQuery } from '~/utils/paymentFiltersHelpers'

describe('parseYmd', () => {
  it('parses a valid YYYY-MM-DD string', () => {
    const d = parseYmd('2026-04-10')
    expect(d).toBeInstanceOf(Date)
    expect(d!.getFullYear()).toBe(2026)
    expect(d!.getMonth()).toBe(3) // 0-indexed
    expect(d!.getDate()).toBe(10)
  })

  it('returns null for empty string', () => {
    expect(parseYmd('')).toBeNull()
  })

  it('returns null for invalid format', () => {
    expect(parseYmd('04-10-2026')).toBeNull()
    expect(parseYmd('2026/04/10')).toBeNull()
    expect(parseYmd('not-a-date')).toBeNull()
  })

  it('returns null for invalid date values', () => {
    expect(parseYmd('2026-13-01')).toBeNull()
  })
})

describe('formatYmd', () => {
  it('formats a Date to YYYY-MM-DD', () => {
    const d = new Date(2026, 0, 5, 12)
    expect(formatYmd(d)).toBe('2026-01-05')
  })

  it('pads single-digit month and day', () => {
    const d = new Date(2026, 3, 1, 12)
    expect(formatYmd(d)).toBe('2026-04-01')
  })

  it('returns empty string for null', () => {
    expect(formatYmd(null)).toBe('')
  })
})

describe('normalizeCurrencyQuery', () => {
  it('normalizes lowercase to uppercase', () => {
    expect(normalizeCurrencyQuery('usd')).toBe('USD')
  })

  it('trims whitespace', () => {
    expect(normalizeCurrencyQuery('  eur  ')).toBe('EUR')
  })

  it('returns null for undefined', () => {
    expect(normalizeCurrencyQuery(undefined)).toBeNull()
  })

  it('returns null for null', () => {
    expect(normalizeCurrencyQuery(null)).toBeNull()
  })

  it('returns null for invalid length', () => {
    expect(normalizeCurrencyQuery('US')).toBeNull()
    expect(normalizeCurrencyQuery('USDD')).toBeNull()
  })

  it('returns null for numeric input', () => {
    expect(normalizeCurrencyQuery('123')).toBeNull()
  })

  it('returns null for empty string', () => {
    expect(normalizeCurrencyQuery('')).toBeNull()
  })
})
