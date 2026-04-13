import { defineStore } from 'pinia'
import { paymentsApi } from '~/api/payments'
import type { PaymentRow, PaymentsMeta } from '~/types/payment'

export const usePaymentsStore = defineStore('payments', () => {
  const payments = ref<PaymentRow[]>([])
  const meta = ref<PaymentsMeta | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchList (queryString: string) {
    loading.value = true
    error.value = null
    try {
      const res = await paymentsApi.list(queryString)
      payments.value = res.data
      meta.value = res.meta
    } catch (e: unknown) {
      error.value = getApiErrorMessage(e, 'Could not load the payments list.')
      throw e
    } finally {
      loading.value = false
    }
  }

  const refundingPaymentId = ref<string | null>(null)
  const refundBanner = ref<{ type: 'ok' | 'err'; text: string } | null>(null)

  async function refund (paymentId: string) {
    refundBanner.value = null
    refundingPaymentId.value = paymentId
    try {
      await paymentsApi.refund(paymentId)
      refundBanner.value = { type: 'ok', text: 'Refund registered (internal webhook).' }
    } catch (e: unknown) {
      refundBanner.value = {
        type: 'err',
        text: getApiErrorMessage(e, 'Could not register the refund.')
      }
    } finally {
      refundingPaymentId.value = null
    }
  }

  return {
    payments,
    meta,
    loading,
    error,
    fetchList,
    refundingPaymentId,
    refundBanner,
    refund
  }
})
