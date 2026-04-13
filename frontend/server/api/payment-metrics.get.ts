export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()

  return await $fetch('/payments/metrics', {
    baseURL: config.public.apiBase,
    headers: proxyAuthHeaders(event)
  })
})
