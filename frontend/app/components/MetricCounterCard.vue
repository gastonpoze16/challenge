<script setup lang="ts">
defineProps<{
  value: number
  label: string
  highlight?: boolean
}>()
</script>

<template>
  <Card class="metric-card" :class="{ 'metric-card--highlight': highlight }">
    <template #content>
      <span class="metric-value">{{ value }}</span>
      <span class="metric-label">{{ label }}</span>
    </template>
  </Card>
</template>

<style scoped>
.metric-card :deep(.p-card-body) {
  padding: 0.85rem 1rem;
}
.metric-card :deep(.p-card-content) {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.2rem;
  padding: 0;
}
.metric-value {
  font-size: 1.75rem;
  font-weight: 700;
  line-height: 1;
}
.metric-label {
  font-size: 0.78rem;
  font-weight: 500;
  color: var(--p-text-muted-color, #6b7280);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
.metric-card--highlight .metric-value {
  color: var(--p-primary-color, #3b82f6);
}
</style>
