export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()

  return await $fetch('/payment-event-types', {
    baseURL: config.public.apiBase,
    headers: proxyAuthHeaders(event)
  })
})
