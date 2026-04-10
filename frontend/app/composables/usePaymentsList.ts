import type { PaymentsResponse, PaymentRow } from '~/types/payment'

export async function usePaymentsList (queryString: Ref<string>) {
  const { authHeaders } = useAuth()

  const listLoadMessage = ref('')
  const refreshError = ref('')

  let refreshFn: (() => Promise<void>) | null = null

  const runRefresh = async () => {
    refreshError.value = ''
    try {
      await refreshFn?.()
    } catch {
      refreshError.value = 'Failed to refresh payments list.'
    }
  }

  const nuxtApp = useNuxtApp()

  onMounted(() => {
    nuxtApp.$echo.channel('payments').listen('.refresh', () => {
      void runRefresh()
    })
  })

  onBeforeUnmount(() => {
    nuxtApp.$echo.leave('payments')
  })

  const { data, pending, error, refresh } = await useAsyncData<PaymentsResponse>(
    () => `payments-list-${queryString.value}`,
    async () => {
      listLoadMessage.value = ''
      try {
        return await $fetch<PaymentsResponse>(`/api/payments?${queryString.value}`, {
          headers: authHeaders()
        })
      } catch (e: unknown) {
        listLoadMessage.value = getApiErrorMessage(e, 'No se pudo cargar la lista.')
        throw e
      }
    },
    { watch: [queryString] }
  )

  refreshFn = refresh

  const payments = computed<PaymentRow[]>(() => data.value?.data ?? [])
  const meta = computed(() => data.value?.meta)

  return {
    payments,
    meta,
    pending,
    error,
    refresh,
    listLoadMessage,
    refreshError
  }
}
