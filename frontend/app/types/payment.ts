export type PaymentRow = {
  payment_id: string
  event: string
  amount: string | number
  currency: string
  updated_at: string
}

export type PaymentsMeta = {
  current_page: number
  per_page: number
  total: number
  last_page: number
  from: number | null
  to: number | null
}

export type PaymentsResponse = {
  data: PaymentRow[]
  meta: PaymentsMeta
}
