import type { FetchOptions } from 'ofetch'

type RequestOptions = FetchOptions & { headers?: Record<string, string> }

function getAuthToken (): string | null {
  return useCookie<string | null>('auth_token').value
}

function buildHeaders (custom?: Record<string, string>): Record<string, string> {
  const headers: Record<string, string> = { ...custom }
  const token = getAuthToken()
  if (token) {
    headers.Authorization = `Bearer ${token}`
  }
  return headers
}

export function apiGet<T> (url: string, opts?: RequestOptions): Promise<T> {
  return $fetch<T>(url, {
    method: 'GET',
    ...opts,
    headers: buildHeaders(opts?.headers as Record<string, string>)
  })
}

export function apiPost<T> (url: string, body?: unknown, opts?: RequestOptions): Promise<T> {
  return $fetch<T>(url, {
    method: 'POST',
    body,
    ...opts,
    headers: buildHeaders(opts?.headers as Record<string, string>)
  })
}
