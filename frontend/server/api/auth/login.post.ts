export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()
  const body = await readBody(event)

  try {
    return await $fetch('/login', {
      baseURL: config.public.apiBase,
      method: 'POST',
      body
    })
  } catch (e: unknown) {
    const err = e as {
      data?: Record<string, unknown>
      statusCode?: number
      status?: number
      statusMessage?: string
    }
    const statusCode = err.statusCode ?? err.status ?? 500
    const data =
      err.data && typeof err.data === 'object'
        ? err.data
        : { message: 'Error al iniciar sesión.' }

    throw createError({
      statusCode,
      statusMessage: err.statusMessage ?? 'Login failed',
      data
    })
  }
})
