export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()

  return await $fetch('/me', {
    baseURL: config.public.apiBase,
    headers: proxyAuthHeaders(event)
  })
})
