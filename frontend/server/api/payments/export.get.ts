export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()
  const query = getQuery(event)

  const search = new URLSearchParams()
  const passthrough = ['user_id', 'event', 'status', 'date_from', 'date_to', 'currency'] as const
  for (const key of passthrough) {
    const v = query[key]
    if (v !== undefined && v !== null && v !== '') {
      search.set(key, String(v))
    }
  }

  const path = search.toString() ? `/payments/export?${search.toString()}` : '/payments/export'

  const csvText = await $fetch<string>(path, {
    baseURL: config.public.apiBase,
    headers: proxyAuthHeaders(event),
    responseType: 'text'
  })

  setResponseHeaders(event, {
    'Content-Type': 'text/csv; charset=utf-8',
    'Content-Disposition': 'attachment; filename="payments.csv"'
  })

  return csvText
})
