import type { H3Event } from 'h3'

export function proxyAuthHeaders (event: H3Event): Record<string, string> {
  const auth = getHeader(event, 'authorization')
  return auth ? { Authorization: auth } : {}
}
