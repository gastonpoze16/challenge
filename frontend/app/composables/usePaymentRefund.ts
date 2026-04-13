import type { PaymentRow } from '~/types/payment'

export async function usePaymentRefund (
  payments: Ref<PaymentRow[]>,
  refresh: () => Promise<void>
) {
  const store = usePaymentsStore()
  const { isRefundedStatus } = await usePaymentEventTypes()

  const canRefund = (row: PaymentRow) => !isRefundedStatus(row.event)

  const triggerRefund = async (paymentId: string) => {
    const row = payments.value.find(p => p.payment_id === paymentId)
    if (row && isRefundedStatus(row.event)) {
      return
    }
    await store.refund(paymentId)
    if (store.refundBanner?.type === 'ok') {
      await refresh()
    }
  }

  return {
    refundingPaymentId: toRef(store, 'refundingPaymentId'),
    refundBanner: toRef(store, 'refundBanner'),
    canRefund,
    triggerRefund
  }
}
