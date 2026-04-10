export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()

  return await $fetch('/logout', {
    baseURL: config.public.apiBase,
    method: 'POST',
    headers: proxyAuthHeaders(event)
  })
})
