export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()
  const paymentId = getRouterParam(event, 'paymentId')

  if (!paymentId) {
    throw createError({
      statusCode: 400,
      statusMessage: 'paymentId is required'
    })
  }

  return await $fetch(
    `/admin/payments/${encodeURIComponent(paymentId)}/refund`,
    {
      method: 'POST',
      baseURL: config.public.apiBase,
      headers: proxyAuthHeaders(event)
    }
  )
})
