import type { PaymentRow } from '~/types/payment'

export async function usePaymentRefund (
  payments: Ref<PaymentRow[]>,
  refresh: () => Promise<void>
) {
  const { authHeaders } = useAuth()
  const { isRefundedStatus } = await usePaymentEventTypes()

  const refundingPaymentId = ref<string | null>(null)
  const refundBanner = ref<{ type: 'ok' | 'err'; text: string } | null>(null)

  const canRefund = (row: PaymentRow) => !isRefundedStatus(row.event)

  const triggerRefund = async (paymentId: string) => {
    const row = payments.value.find(p => p.payment_id === paymentId)
    if (row && isRefundedStatus(row.event)) {
      return
    }
    refundBanner.value = null
    refundingPaymentId.value = paymentId
    try {
      await $fetch(`/api/payments/${encodeURIComponent(paymentId)}/refund`, {
        method: 'POST',
        headers: authHeaders()
      })
      refundBanner.value = { type: 'ok', text: 'Refund registered (internal webhook).' }
      await refresh()
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
    refundingPaymentId,
    refundBanner,
    canRefund,
    triggerRefund
  }
}
