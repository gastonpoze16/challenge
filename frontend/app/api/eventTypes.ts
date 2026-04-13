import type { PaymentEventTypeDto } from '~/composables/usePaymentEventTypes'
import { apiGet } from './client'

type EventTypesResponse = { data: PaymentEventTypeDto[] }

export const eventTypesApi = {
  list: () =>
    apiGet<EventTypesResponse>('/api/payment-event-types')
}
