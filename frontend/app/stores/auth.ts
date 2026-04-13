import { defineStore } from 'pinia'
import { authApi } from '~/api/auth'

export type AuthUser = {
  id: number
  name: string
  email: string
}

export const useAuthStore = defineStore('auth', () => {
  const token = useCookie<string | null>('auth_token', {
    default: () => null,
    sameSite: 'lax',
    maxAge: 60 * 60 * 24 * 7,
    secure: false
  })

  const user = ref<AuthUser | null>(null)

  const isAuthenticated = computed(() => !!token.value)

  const authHeaders = (): Record<string, string> => {
    return token.value ? { Authorization: `Bearer ${token.value}` } : {}
  }

  async function fetchUser () {
    if (!token.value) {
      user.value = null
      return
    }
    try {
      const res = await authApi.me()
      user.value = res.user
    } catch {
      token.value = null
      user.value = null
    }
  }

  async function login (email: string, password: string) {
    const res = await authApi.login(email, password)
    token.value = res.token
    user.value = res.user
  }

  async function logout () {
    try {
      if (token.value) {
        await authApi.logout()
      }
    } finally {
      token.value = null
      user.value = null
    }
  }

  return {
    token,
    user,
    isAuthenticated,
    authHeaders,
    fetchUser,
    login,
    logout
  }
})
