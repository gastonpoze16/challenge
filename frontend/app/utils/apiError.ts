/**
 * Mensaje legible a partir de errores de $fetch / createError (401, 422, etc.).
 */
export function getApiErrorMessage (e: unknown, fallback = 'No se pudo completar la operación.'): string {
  if (!e || typeof e !== 'object') {
    return fallback
  }

  const err = e as {
    data?: {
      message?: string
      errors?: Record<string, string[] | string>
    }
    statusMessage?: string
  }

  const d = err.data
  if (d?.errors) {
    const first = Object.values(d.errors).flatMap((v) =>
      Array.isArray(v) ? v : [String(v)]
    )[0]
    if (first) {
      return first
    }
  }
  if (d?.message && typeof d.message === 'string') {
    return d.message
  }
  if (err.statusMessage && err.statusMessage !== 'Unauthorized') {
    return err.statusMessage
  }

  return fallback
}
