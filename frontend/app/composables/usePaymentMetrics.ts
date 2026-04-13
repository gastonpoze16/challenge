export async function usePaymentMetrics () {
  const store = useMetricsStore()

  await store.fetch()

  const nuxtApp = useNuxtApp()

  onMounted(() => {
    nuxtApp.$echo.channel('payments').listen('.refresh', () => {
      void store.fetch()
    })
  })

  return {
    total: computed(() => store.total),
    byStatus: computed(() => store.byStatus),
    byDay: computed(() => store.byDay),
    byCurrency: computed(() => store.byCurrency),
    statusChartData: store.statusChartData,
    dailyChartData: store.dailyChartData,
    currencyChartData: store.currencyChartData,
    refresh: store.fetch
  }
}
