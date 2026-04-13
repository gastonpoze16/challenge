<script setup lang="ts">
import { paymentsApi } from '~/api/payments'

definePageMeta({
  middleware: 'auth'
})

const route = useRoute()
const router = useRouter()
const { toStatusLabel } = await usePaymentEventTypes()
const paymentId = computed(() => String(route.params.paymentId ?? ''))

const { data, pending, error } = await useAsyncData(
  () => `payment-events-${paymentId.value}`,
  () => paymentsApi.events(paymentId.value),
  { watch: [paymentId] }
)

const events = computed(() => data.value?.data ?? [])
</script>

<template>
  <div class="page">
    <Toolbar class="page-toolbar" aria-label="Payment header">
      <template #start>
        <div class="heading">
          <h1 class="page-title">Payment Event History</h1>
          <p class="subtitle">
            Payment ID: <SpBadge intent="secondary" variant="outline" size="sm">{{ paymentId }}</SpBadge>
          </p>
        </div>
      </template>
      <template #end>
        <SpButton
          type="button"
          intent="secondary"
          variant="outline"
          size="sm"
          @click="router.push('/')"
        >
          Back to payments
        </SpButton>
      </template>
    </Toolbar>

    <Card>
      <template #title>Events ordered</template>
      <template #content>
        <Message v-if="error" severity="error" :closable="false">
          Failed to load payment events.
        </Message>
        <DataTable
          v-else
          :value="events"
          :loading="pending"
          data-key="event_id"
          striped-rows
          size="small"
          table-style="min-width: 40rem"
          empty-message="No events found for this payment."
        >
          <Column field="event_id" header="Event ID" style="min-width: 12rem">
            <template #body="{ data }">
              <strong>{{ data.event_id }}</strong>
            </template>
          </Column>
          <Column header="Status" style="min-width: 8rem">
            <template #body="{ data }">
              <SpBadge intent="info" variant="subtle" size="sm">
                {{ toStatusLabel(data.event) }}
              </SpBadge>
            </template>
          </Column>
          <Column field="amount" header="Amount" />
          <Column field="currency" header="Currency" style="width: 6rem" />
          <Column header="Received At" style="min-width: 10rem">
            <template #body="{ data }">
              <span class="cell-muted">{{ formatPaymentDateTime(String(data.received_at)) }}</span>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<style scoped>
.page {
  max-width: 1100px;
  margin: 0 auto;
  padding: 1.25rem;
  font-family: Inter, system-ui, sans-serif;
}
.page-toolbar {
  margin-bottom: 1rem;
  border-radius: 12px;
  flex-wrap: wrap;
  gap: 0.75rem;
}
.heading {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}
.page-title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
}
.subtitle {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  color: var(--p-text-muted-color, #6b7280);
}
.cell-muted {
  color: var(--p-text-muted-color, #6b7280);
  font-size: 0.88rem;
}
</style>
