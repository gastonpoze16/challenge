import type { PaymentRow } from '~/types/payment'
import { paymentsApi } from '~/api/payments'

export async function usePaymentsList (queryString: Ref<string>) {
  const store = usePaymentsStore()

  const listLoadMessage = ref('')
  const refreshError = ref('')

  const nuxtApp = useNuxtApp()

  /** Set after `useAsyncData` resolves so Echo can call the real refresh (hooks must register before any await). */
  const runRefreshImpl = ref<(() => Promise<void>) | null>(null)

  onMounted(() => {
    nuxtApp.$echo.channel('payments').listen('.refresh', () => {
      const fn = runRefreshImpl.value
      if (fn) void fn()
    })
  })

  onBeforeUnmount(() => {
    nuxtApp.$echo.leave('payments')
  })

  const listKey = computed(() => `payments-list-${queryString.value}`)

  const { data, pending, error, refresh } = await useAsyncData(
    listKey,
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
    }
  )

  const runRefresh = async () => {
    refreshError.value = ''
    try {
      await refresh()
    } catch {
      refreshError.value = 'Failed to refresh payments list.'
    }
  }

  runRefreshImpl.value = runRefresh

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
