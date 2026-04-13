import type { PaymentsResponse, MetricsResponse } from '~/types/payment'
import { apiGet, apiPost } from './client'

export type EventLogRow = {
  event_id: string
  payment_id: string
  event: string
  amount: string | number
  currency: string
  timestamp: string
}

export const paymentsApi = {
  list: (query: string) =>
    apiGet<PaymentsResponse>(`/api/payments?${query}`),

  events: (paymentId: string) =>
    apiGet<{ data: EventLogRow[] }>(`/api/payments/${encodeURIComponent(paymentId)}/events`),

  refund: (paymentId: string) =>
    apiPost<void>(`/api/payments/${encodeURIComponent(paymentId)}/refund`),

  metrics: () =>
    apiGet<MetricsResponse>('/api/payment-metrics'),

  exportCsv: (query: string) =>
    $fetch<string>(`/api/payments/export?${query}`, {
      headers: { Authorization: `Bearer ${useCookie<string | null>('auth_token').value ?? ''}` },
      responseType: 'text'
    })
}
