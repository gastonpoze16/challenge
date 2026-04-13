import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mockNuxtImport } from '@nuxt/test-utils/runtime'
import { createFetchMock } from '../helpers'

const tokenRef = ref<string | null>(null)

mockNuxtImport('useCookie', () => () => tokenRef)

const fetchMock = createFetchMock()

describe('useAuth', () => {
  beforeEach(() => {
    tokenRef.value = null
    fetchMock.mockClear()
  })

  it('authHeaders returns empty object without token', async () => {
    const { useAuth } = await import('~/composables/useAuth')
    const { authHeaders } = useAuth()
    expect(authHeaders()).toEqual({})
  })

  it('authHeaders returns Bearer token when set', async () => {
    tokenRef.value = 'my-token'
    const { useAuth } = await import('~/composables/useAuth')
    const { authHeaders } = useAuth()
    expect(authHeaders()).toEqual({ Authorization: 'Bearer my-token' })
  })

  it('login sets token and user', async () => {
    fetchMock.mockResolvedValueOnce({
      user: { id: 1, name: 'Test', email: 'test@example.com' },
      token: 'new-token',
    })

    const { useAuth } = await import('~/composables/useAuth')
    const { login, token, user } = useAuth()

    await login('test@example.com', 'password123')

    expect(fetchMock).toHaveBeenCalledWith('/api/login', expect.objectContaining({
      method: 'POST',
      body: { email: 'test@example.com', password: 'password123' },
    }))
    expect(token.value).toBe('new-token')
    expect(user.value).toEqual({ id: 1, name: 'Test', email: 'test@example.com' })
  })

  it('logout clears token and user', async () => {
    tokenRef.value = 'existing-token'
    fetchMock.mockResolvedValueOnce({})

    const { useAuth } = await import('~/composables/useAuth')
    const { logout, token, user } = useAuth()

    await logout()

    expect(token.value).toBeNull()
    expect(user.value).toBeNull()
  })

  it('logout clears state even if API call fails', async () => {
    tokenRef.value = 'existing-token'
    fetchMock.mockRejectedValueOnce(new Error('Network error'))

    const { useAuth } = await import('~/composables/useAuth')
    const { logout, token, user } = useAuth()

    try { await logout() } catch {}

    expect(token.value).toBeNull()
    expect(user.value).toBeNull()
  })

  it('fetchUser sets user from API', async () => {
    tokenRef.value = 'valid-token'
    fetchMock.mockResolvedValueOnce({
      user: { id: 2, name: 'Jane', email: 'jane@example.com' },
    })

    const { useAuth } = await import('~/composables/useAuth')
    const { fetchUser, user } = useAuth()

    await fetchUser()

    expect(user.value).toEqual({ id: 2, name: 'Jane', email: 'jane@example.com' })
  })

  it('fetchUser clears token and user on failure', async () => {
    tokenRef.value = 'expired-token'
    fetchMock.mockRejectedValueOnce(new Error('401'))

    const { useAuth } = await import('~/composables/useAuth')
    const { fetchUser, token, user } = useAuth()

    await fetchUser()

    expect(token.value).toBeNull()
    expect(user.value).toBeNull()
  })

  it('fetchUser skips API call without token', async () => {
    fetchMock.mockClear()

    const { useAuth } = await import('~/composables/useAuth')
    const { fetchUser } = useAuth()

    await fetchUser()

    expect(fetchMock).not.toHaveBeenCalled()
  })
})
