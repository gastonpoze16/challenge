<script setup lang="ts">
defineProps<{
  title: string
  type: 'doughnut' | 'bar' | 'line' | 'pie'
  data: Record<string, unknown>
  options: Record<string, unknown>
  hasData: boolean
}>()
</script>

<template>
  <Card class="chart-card">
    <template #title>{{ title }}</template>
    <template #content>
      <div class="chart-wrap">
        <Chart v-if="hasData" :type="type" :data="data" :options="options" />
        <p v-else class="empty-chart">No data yet</p>
      </div>
    </template>
  </Card>
</template>

<style scoped>
.chart-card :deep(.p-card-body) {
  padding: 0.85rem 1rem;
}
.chart-card :deep(.p-card-title) {
  font-size: 0.88rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}
.chart-wrap {
  height: 220px;
  position: relative;
}
.empty-chart {
  color: var(--p-text-muted-color, #6b7280);
  text-align: center;
  padding-top: 3rem;
  font-size: 0.9rem;
}
</style>
