import { defineStore } from 'pinia'
import { paymentsApi } from '~/api/payments'
import type { StatusMetric, DayMetric, CurrencyMetric } from '~/types/payment'

const PALETTE = ['#3b82f6', '#22c55e', '#ef4444', '#a855f7', '#f59e0b', '#06b6d4', '#ec4899', '#64748b']

export const useMetricsStore = defineStore('metrics', () => {
  const total = ref(0)
  const byStatus = ref<StatusMetric[]>([])
  const byDay = ref<DayMetric[]>([])
  const byCurrency = ref<CurrencyMetric[]>([])
  const loading = ref(false)

  async function fetch () {
    loading.value = true
    try {
      const res = await paymentsApi.metrics()
      total.value = res.total
      byStatus.value = res.by_status
      byDay.value = res.by_day
      byCurrency.value = res.by_currency
    } finally {
      loading.value = false
    }
  }

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
    loading,
    fetch,
    statusChartData,
    dailyChartData,
    currencyChartData
  }
})
