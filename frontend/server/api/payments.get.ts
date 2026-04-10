export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()
  const query = getQuery(event)

  const search = new URLSearchParams()
  const passthrough = [
    'limit',
    'page',
    'event',
    'status',
    'date_from',
    'date_to',
    'currency'
  ] as const
  for (const key of passthrough) {
    const v = query[key]
    if (v !== undefined && v !== null && v !== '') {
      search.set(key, String(v))
    }
  }

  const path = search.toString() ? `/payments?${search.toString()}` : '/payments'
  return await $fetch(path, {
    baseURL: config.public.apiBase,
    headers: proxyAuthHeaders(event)
  })
})
