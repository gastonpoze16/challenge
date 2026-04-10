export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()
  const paymentId = getRouterParam(event, 'paymentId')

  if (!paymentId) {
    throw createError({
      statusCode: 400,
      statusMessage: 'paymentId is required'
    })
  }

  return await $fetch(`/payments/${encodeURIComponent(paymentId)}/events`, {
    baseURL: config.public.apiBase,
    headers: proxyAuthHeaders(event)
  })
})
