export type AuthUser = {
  id: number
  name: string
  email: string
}

export function useAuth () {
  const token = useCookie<string | null>('auth_token', {
    default: () => null,
    sameSite: 'lax',
    maxAge: 60 * 60 * 24 * 7,
    secure: false
  })

  const user = useState<AuthUser | null>('auth_user', () => null)

  const authHeaders = (): Record<string, string> => {
    const t = token.value
    return t ? { Authorization: `Bearer ${t}` } : {}
  }

  const fetchUser = async (): Promise<void> => {
    if (!token.value) {
      user.value = null
      return
    }
    try {
      const res = await $fetch<{ user: AuthUser }>('/api/me', {
        headers: authHeaders()
      })
      user.value = res.user
    } catch {
      token.value = null
      user.value = null
    }
  }

  const login = async (email: string, password: string): Promise<void> => {
    const res = await $fetch<{ user: AuthUser; token: string }>('/api/login', {
      method: 'POST',
      body: { email, password }
    })
    token.value = res.token
    user.value = res.user
  }

  const logout = async (): Promise<void> => {
    try {
      if (token.value) {
        await $fetch('/api/logout', {
          method: 'POST',
          headers: authHeaders()
        })
      }
    } finally {
      token.value = null
      user.value = null
    }
  }

  return {
    token,
    user,
    authHeaders,
    fetchUser,
    login,
    logout
  }
}
