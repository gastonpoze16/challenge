export type PaymentEventTypeDto = {
  code: string
  label: string
  sort_order: number
  is_refunded: boolean
}

type PaymentEventTypesResponse = {
  data: PaymentEventTypeDto[]
}

export async function usePaymentEventTypes () {
  const { authHeaders } = useAuth()

  const { data } = await useAsyncData(
    'payment-event-types',
    () =>
      $fetch<PaymentEventTypesResponse>('/api/payment-event-types', {
        headers: authHeaders()
      }),
    {
      default: () => ({ data: [] as PaymentEventTypeDto[] })
    }
  )

  const types = computed(() => data.value?.data ?? [])

  const filterSelectOptions = computed(() => [
    { label: 'Any', value: '' },
    ...types.value.map(t => ({ label: t.label, value: t.code }))
  ])

  const toStatusLabel = (eventValue?: string | null) => {
    if (!eventValue) return '-'
    const row = types.value.find(t => t.code === eventValue)
    return row?.label ?? eventValue
  }

  const isRefundedStatus = (eventValue?: string | null) => {
    if (!eventValue) return false
    return types.value.some(t => t.code === eventValue && t.is_refunded)
  }

  return {
    filterSelectOptions,
    toStatusLabel,
    isRefundedStatus
  }
}
