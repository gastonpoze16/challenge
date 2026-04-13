import type { PaymentRow } from '~/types/payment'
import { paymentsApi } from '~/api/payments'

export async function usePaymentsList (queryString: Ref<string>) {
  const store = usePaymentsStore()

  const listLoadMessage = ref('')
  const refreshError = ref('')

  const nuxtApp = useNuxtApp()

  const { data, pending, error, refresh } = await useAsyncData(
    () => `payments-list-${queryString.value}`,
    async () => {
      listLoadMessage.value = ''
      try {
        const res = await paymentsApi.list(queryString.value)
        store.payments = res.data
        store.meta = res.meta
        return res
      } catch (e: unknown) {
        listLoadMessage.value = getApiErrorMessage(e, 'Could not load the payments list.')
        throw e
      }
    },
    { watch: [queryString] }
  )

  const runRefresh = async () => {
    refreshError.value = ''
    try {
      await refresh()
    } catch {
      refreshError.value = 'Failed to refresh payments list.'
    }
  }

  onMounted(() => {
    nuxtApp.$echo.channel('payments').listen('.refresh', () => {
      void runRefresh()
    })
  })

  onBeforeUnmount(() => {
    nuxtApp.$echo.leave('payments')
  })

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
