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

export type StatusMetric = {
  event: string
  label: string
  count: number
}

export type DayMetric = {
  date: string
  count: number
}

export type CurrencyMetric = {
  currency: string
  count: number
}

export type MetricsResponse = {
  total: number
  by_status: StatusMetric[]
  by_day: DayMetric[]
  by_currency: CurrencyMetric[]
}
