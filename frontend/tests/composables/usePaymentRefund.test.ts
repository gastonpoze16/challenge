import { describe, it, expect, vi } from 'vitest'
import { mockNuxtImport } from '@nuxt/test-utils/runtime'
import { MOCK_AUTH_RETURN, MOCK_EVENT_TYPES, createFetchMock, makePaymentRow } from '../helpers'

mockNuxtImport('useAuth', () => () => MOCK_AUTH_RETURN)

mockNuxtImport('useAsyncData', () => async () => ({
  data: ref({ data: MOCK_EVENT_TYPES }),
  pending: ref(false),
  error: ref(null),
  refresh: vi.fn(),
}))

const fetchMock = createFetchMock()

describe('usePaymentRefund', () => {
  it('canRefund returns true for non-refunded payment', async () => {
    const { usePaymentRefund } = await import('~/composables/usePaymentRefund')
    const payments = ref([makePaymentRow('payment.completed')])
    const { canRefund } = await usePaymentRefund(payments, vi.fn())

    expect(canRefund(payments.value[0])).toBe(true)
  })

  it('canRefund returns false for refunded payment', async () => {
    const { usePaymentRefund } = await import('~/composables/usePaymentRefund')
    const payments = ref([makePaymentRow('payment.refunded')])
    const { canRefund } = await usePaymentRefund(payments, vi.fn())

    expect(canRefund(payments.value[0])).toBe(false)
  })

  it('triggerRefund skips already refunded payment', async () => {
    fetchMock.mockClear()
    const { usePaymentRefund } = await import('~/composables/usePaymentRefund')
    const payments = ref([makePaymentRow('payment.refunded')])
    const { triggerRefund } = await usePaymentRefund(payments, vi.fn())

    await triggerRefund('pay_1')

    expect(fetchMock).not.toHaveBeenCalled()
  })

  it('triggerRefund calls API and sets success banner', async () => {
    fetchMock.mockClear()
    fetchMock.mockResolvedValueOnce({})
    const refreshMock = vi.fn()
    const { usePaymentRefund } = await import('~/composables/usePaymentRefund')
    const payments = ref([makePaymentRow('payment.completed')])
    const { triggerRefund, refundBanner } = await usePaymentRefund(payments, refreshMock)

    await triggerRefund('pay_1')

    expect(fetchMock).toHaveBeenCalledWith(
      '/api/payments/pay_1/refund',
      expect.objectContaining({ method: 'POST' }),
    )
    expect(refundBanner.value).toEqual({ type: 'ok', text: 'Refund registered (internal webhook).' })
    expect(refreshMock).toHaveBeenCalled()
  })

  it('triggerRefund sets error banner on failure', async () => {
    fetchMock.mockClear()
    fetchMock.mockRejectedValueOnce({ data: { message: 'Server error' } })
    const { usePaymentRefund } = await import('~/composables/usePaymentRefund')
    const payments = ref([makePaymentRow('payment.completed')])
    const { triggerRefund, refundBanner } = await usePaymentRefund(payments, vi.fn())

    await triggerRefund('pay_1')

    expect(refundBanner.value?.type).toBe('err')
    expect(refundBanner.value?.text).toBe('Server error')
  })

  it('triggerRefund clears refundingPaymentId after completion', async () => {
    fetchMock.mockClear()
    fetchMock.mockResolvedValueOnce({})
    const { usePaymentRefund } = await import('~/composables/usePaymentRefund')
    const payments = ref([makePaymentRow('payment.completed')])
    const { triggerRefund, refundingPaymentId } = await usePaymentRefund(payments, vi.fn())

    await triggerRefund('pay_1')

    expect(refundingPaymentId.value).toBeNull()
  })
})
