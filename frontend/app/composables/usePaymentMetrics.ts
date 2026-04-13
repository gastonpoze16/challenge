import type { MetricsResponse } from '~/types/payment'

export async function usePaymentMetrics () {
  const { authHeaders } = useAuth()

  let refreshFn: (() => Promise<void>) | null = null

  const nuxtApp = useNuxtApp()

  onMounted(() => {
    nuxtApp.$echo.channel('payments').listen('.refresh', () => {
      void refreshFn?.()
    })
  })

  const { data, refresh } = await useAsyncData<MetricsResponse>(
    'payment-metrics',
    () => $fetch<MetricsResponse>('/api/payment-metrics', { headers: authHeaders() }),
    { default: () => ({ total: 0, by_status: [], by_day: [], by_currency: [] }) }
  )

  refreshFn = refresh

  const total = computed(() => data.value?.total ?? 0)
  const byStatus = computed(() => data.value?.by_status ?? [])
  const byDay = computed(() => data.value?.by_day ?? [])
  const byCurrency = computed(() => data.value?.by_currency ?? [])

  const PALETTE = ['#3b82f6', '#22c55e', '#ef4444', '#a855f7', '#f59e0b', '#06b6d4', '#ec4899', '#64748b']

  const statusChartData = computed(() => ({
    labels: byStatus.value.map(s => s.label),
    datasets: [{
      data: byStatus.value.map(s => s.count),
      backgroundColor: byStatus.value.map((_, i) => PALETTE[i % PALETTE.length])
    }]
  }))

  const dailyChartData = computed(() => ({
    labels: byDay.value.map(d => d.date),
    datasets: [{
      label: 'Payments',
      data: byDay.value.map(d => d.count),
      backgroundColor: '#3b82f6',
      borderColor: '#2563eb',
      borderWidth: 1
    }]
  }))

  const currencyChartData = computed(() => ({
    labels: byCurrency.value.map(c => c.currency),
    datasets: [{
      data: byCurrency.value.map(c => c.count),
      backgroundColor: byCurrency.value.map((_, i) => PALETTE[i % PALETTE.length])
    }]
  }))

  return {
    total,
    byStatus,
    byDay,
    byCurrency,
    statusChartData,
    dailyChartData,
    currencyChartData,
    refresh
  }
}
