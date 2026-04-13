import { vi } from 'vitest'
import type { PaymentEventTypeDto } from '~/stores/eventTypes'
import type { PaymentRow } from '~/types/payment'

export const MOCK_EVENT_TYPES: PaymentEventTypeDto[] = [
  { code: 'payment.created', label: 'Created', sort_order: 10, is_refunded: false },
  { code: 'payment.completed', label: 'Completed', sort_order: 20, is_refunded: false },
  { code: 'payment.failed', label: 'Failed', sort_order: 30, is_refunded: false },
  { code: 'payment.refunded', label: 'Refunded', sort_order: 40, is_refunded: true },
]

export const MOCK_AUTH_RETURN = {
  authHeaders: () => ({ Authorization: 'Bearer fake' }),
}

export function mockAsyncDataResult (types = MOCK_EVENT_TYPES) {
  return {
    data: ref({ data: types }),
    pending: ref(false),
    error: ref(null),
    refresh: vi.fn(),
  }
}

export function createFetchMock () {
  const mock = vi.fn()
  vi.stubGlobal('$fetch', mock)
  return mock
}

export function makePaymentRow (event: string, overrides: Partial<PaymentRow> = {}): PaymentRow {
  return {
    payment_id: 'pay_1',
    event,
    amount: 100,
    currency: 'USD',
    updated_at: '2026-04-10T12:00:00Z',
    ...overrides,
  }
}
