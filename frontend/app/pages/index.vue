<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const router = useRouter()
const { filterSelectOptions, toStatusLabel } = await usePaymentEventTypes()

const {
  filterForm, dateFromModel, dateToModel,
  currencyFilterWarning, queryString,
  applyFilters, clearFilters, onPaginatorPage
} = usePaymentFilters()

const {
  payments, meta, pending, error, refresh,
  listLoadMessage, refreshError
} = await usePaymentsList(queryString)

const {
  refundingPaymentId, refundBanner, canRefund, triggerRefund
} = await usePaymentRefund(payments, refresh)
</script>

<template>
  <div class="page">
    <div class="page-header">
      <h1 class="page-title">Payments Dashboard</h1>
      <SpButton type="button" intent="primary" variant="outline" size="sm" @click="router.push('/payments/metrics')">
        View Metrics
      </SpButton>
    </div>

    <Card>
      <template #content>
        <div class="filters-bar" role="region" aria-label="Filters">
          <div class="filters-toolbar-row">
            <div class="filters-grid">
              <div class="field">
                <SpLabel>Status (event)</SpLabel>
                <SpSelect
                  v-model="filterForm.event"
                  :options="filterSelectOptions"
                  option-label="label"
                  option-value="value"
                  placeholder="Any"
                  size="sm"
                  intent="default"
                  class="filter-control"
                />
              </div>
              <div class="field">
                <SpLabel>From</SpLabel>
                <SpDatePicker
                  v-model="dateFromModel"
                  date-format="yy-mm-dd"
                  show-icon
                  placeholder="Start date"
                  size="sm"
                  intent="default"
                  class="filter-control"
                />
              </div>
              <div class="field">
                <SpLabel>To</SpLabel>
                <SpDatePicker
                  v-model="dateToModel"
                  date-format="yy-mm-dd"
                  show-icon
                  placeholder="End date"
                  size="sm"
                  intent="default"
                  class="filter-control"
                />
              </div>
              <div class="field field-currency">
                <SpLabel>Currency</SpLabel>
                <SpInput
                  v-model="filterForm.currency"
                  type="text"
                  maxlength="3"
                  placeholder="USD"
                  size="sm"
                  intent="default"
                  class="filter-control currency-input"
                />
              </div>
            </div>
            <div class="filter-actions">
              <SpButton type="button" intent="primary" size="sm" @click="applyFilters">
                Apply
              </SpButton>
              <SpButton type="button" intent="secondary" variant="outline" size="sm" @click="clearFilters">
                Clear
              </SpButton>
            </div>
          </div>
        </div>

        <Message v-if="currencyFilterWarning" severity="warn" :closable="false" class="stack-msg">
          {{ currencyFilterWarning }}
        </Message>

        <Message v-if="error" severity="error" :closable="false" class="stack-msg">
          {{ listLoadMessage || 'Failed to load payments.' }}
        </Message>

        <template v-else>
          <Message v-if="refreshError" severity="error" :closable="false" class="stack-msg">
            {{ refreshError }}
          </Message>
          <Message
            v-if="refundBanner"
            :severity="refundBanner.type === 'ok' ? 'success' : 'error'"
            :closable="false"
            class="stack-msg"
          >
            {{ refundBanner.text }}
          </Message>

          <Message v-if="meta" severity="success" :closable="false" class="stack-msg meta-line">
            Showing {{ meta.from ?? 0 }}-{{ meta.to ?? 0 }} of {{ meta.total ?? 0 }}
          </Message>

          <DataTable
            :value="payments"
            :loading="pending"
            data-key="payment_id"
            striped-rows
            size="small"
            table-style="min-width: 50rem"
            empty-message="No matching payments found."
            class="payments-table"
          >
            <Column field="payment_id" header="Payment ID" style="min-width: 12rem">
              <template #body="{ data }">
                <strong>{{ data.payment_id }}</strong>
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
            <Column header="Updated" style="min-width: 9rem">
              <template #body="{ data }">
                <span class="cell-muted">{{ formatPaymentDateTime(data.updated_at) }}</span>
              </template>
            </Column>
            <Column header="Actions" :exportable="false" style="min-width: 14rem">
              <template #body="{ data }">
                <div class="action-group">
                  <SpButton
                    type="button"
                    intent="info"
                    variant="outline"
                    size="sm"
                    @click="router.push(`/payments/${encodeURIComponent(data.payment_id)}`)"
                  >
                    Events
                  </SpButton>
                  <SpButton
                    type="button"
                    intent="warning"
                    variant="outline"
                    size="sm"
                    :loading="refundingPaymentId === data.payment_id"
                    :disabled="!canRefund(data) || (refundingPaymentId !== null && refundingPaymentId !== data.payment_id)"
                    @click="triggerRefund(data.payment_id)"
                  >
                    Refund
                  </SpButton>
                </div>
              </template>
            </Column>
          </DataTable>

          <Paginator
            v-if="meta"
            :rows="meta.per_page"
            :total-records="meta.total"
            :first="Math.max(0, (meta.current_page - 1) * meta.per_page)"
            template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
            current-page-report-template="Page {currentPage} of {totalPages}"
            class="pager"
            @page="onPaginatorPage"
          />
        </template>
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
.filters-bar {
  margin: -0.5rem 0 1rem;
  padding: 0.5rem 0;
  width: 100%;
  box-sizing: border-box;
}
.filters-toolbar-row {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  column-gap: 1rem;
  row-gap: 0.75rem;
  width: 100%;
  box-sizing: border-box;
}
.filters-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem 1rem;
  align-items: flex-end;
  flex: 0 1 auto;
  min-width: 0;
}
.field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  min-width: 10rem;
}
.field-currency { min-width: 7rem; }
.filter-control { min-width: 10rem; }
.currency-input { min-width: 5rem; max-width: 6rem; text-transform: uppercase; }
.filter-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: center;
  flex: 0 0 auto;
  margin-left: auto;
}
.stack-msg { margin: 0 0 0.75rem; }
.meta-line :deep(.p-message-text) { font-size: 0.9rem; }
.payments-table { margin-top: 0.25rem; }
.cell-muted { color: var(--p-text-muted-color, #6b7280); font-size: 0.88rem; }
.action-group { display: inline-flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
.pager { margin-top: 1rem; border: 0; background: transparent; }
</style>
