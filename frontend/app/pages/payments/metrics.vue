<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const router = useRouter()

const {
  total, byStatus, statusChartData, dailyChartData, currencyChartData
} = await usePaymentMetrics()

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom' as const } }
}

const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    y: { beginAtZero: true, ticks: { stepSize: 1 } }
  }
}
</script>

<template>
  <div class="page">
    <div class="page-header">
      <h1 class="page-title">Payment Metrics</h1>
      <SpButton type="button" intent="secondary" variant="outline" size="sm" @click="router.push('/')">
        Back to Dashboard
      </SpButton>
    </div>

    <div class="metrics-counters">
      <MetricCounterCard :value="total" label="Total Payments" highlight />
      <MetricCounterCard v-for="s in byStatus" :key="s.event" :value="s.count" :label="s.label" />
    </div>

    <div class="metrics-charts">
      <MetricChartCard title="By Status" type="doughnut" :data="statusChartData" :options="doughnutOptions" :has-data="byStatus.length > 0" />
      <MetricChartCard title="By Day" type="bar" :data="dailyChartData" :options="barOptions" :has-data="dailyChartData.labels.length > 0" />
      <MetricChartCard title="By Currency" type="doughnut" :data="currencyChartData" :options="doughnutOptions" :has-data="currencyChartData.labels.length > 0" />
    </div>
  </div>
</template>

<style scoped>
.page {
  max-width: 1100px;
  margin: 0 auto;
  padding: 1.25rem;
  font-family: Inter, system-ui, sans-serif;
}
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}
.page-title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
}
.metrics-counters {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
  gap: 0.75rem;
  margin-bottom: 1rem;
}
.metrics-charts {
  display: grid;
  grid-template-columns: 1fr 2fr 1fr;
  gap: 0.75rem;
}
@media (max-width: 700px) {
  .metrics-charts {
    grid-template-columns: 1fr;
  }
}
</style>
