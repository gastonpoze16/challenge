import { describe, it, expect, vi } from 'vitest'
import { mockNuxtImport } from '@nuxt/test-utils/runtime'
import { MOCK_EVENT_TYPES } from '../helpers'

const mockStore = {
  types: ref(MOCK_EVENT_TYPES),
  loaded: ref(true),
  fetch: vi.fn(),
  filterSelectOptions: computed(() => [
    { label: 'Any', value: '' },
    ...MOCK_EVENT_TYPES.map(t => ({ label: t.label, value: t.code })),
  ]),
  toStatusLabel: (eventValue?: string | null) => {
    if (!eventValue) return '-'
    const row = MOCK_EVENT_TYPES.find(t => t.code === eventValue)
    return row?.label ?? eventValue
  },
  isRefundedStatus: (eventValue?: string | null) => {
    if (!eventValue) return false
    return MOCK_EVENT_TYPES.some(t => t.code === eventValue && t.is_refunded)
  },
}

mockNuxtImport('useEventTypesStore', () => () => mockStore)

describe('usePaymentEventTypes', () => {
  it('returns filterSelectOptions with "Any" first', async () => {
    const { usePaymentEventTypes } = await import('~/composables/usePaymentEventTypes')
    const { filterSelectOptions } = await usePaymentEventTypes()

    expect(filterSelectOptions.value[0]).toEqual({ label: 'Any', value: '' })
    expect(filterSelectOptions.value).toHaveLength(5)
    expect(filterSelectOptions.value[1]).toEqual({ label: 'Created', value: 'payment.created' })
  })

  it('toStatusLabel returns label for known code', async () => {
    const { usePaymentEventTypes } = await import('~/composables/usePaymentEventTypes')
    const { toStatusLabel } = await usePaymentEventTypes()

    expect(toStatusLabel('payment.completed')).toBe('Completed')
    expect(toStatusLabel('payment.refunded')).toBe('Refunded')
  })

  it('toStatusLabel returns raw code for unknown code', async () => {
    const { usePaymentEventTypes } = await import('~/composables/usePaymentEventTypes')
    const { toStatusLabel } = await usePaymentEventTypes()

    expect(toStatusLabel('payment.unknown')).toBe('payment.unknown')
  })

  it('toStatusLabel returns dash for null/undefined', async () => {
    const { usePaymentEventTypes } = await import('~/composables/usePaymentEventTypes')
    const { toStatusLabel } = await usePaymentEventTypes()

    expect(toStatusLabel(null)).toBe('-')
    expect(toStatusLabel(undefined)).toBe('-')
  })

  it('isRefundedStatus returns true only for refunded codes', async () => {
    const { usePaymentEventTypes } = await import('~/composables/usePaymentEventTypes')
    const { isRefundedStatus } = await usePaymentEventTypes()

    expect(isRefundedStatus('payment.refunded')).toBe(true)
    expect(isRefundedStatus('payment.completed')).toBe(false)
    expect(isRefundedStatus('payment.created')).toBe(false)
    expect(isRefundedStatus(null)).toBe(false)
    expect(isRefundedStatus(undefined)).toBe(false)
  })
})
