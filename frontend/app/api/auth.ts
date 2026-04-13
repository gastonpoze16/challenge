import type { AuthUser } from '~/composables/useAuth'
import { apiGet, apiPost } from './client'

type LoginResponse = { token: string; user: AuthUser }
type MeResponse = { user: AuthUser }

export const authApi = {
  login: (email: string, password: string) =>
    apiPost<LoginResponse>('/api/login', { email, password }),

  me: () =>
    apiGet<MeResponse>('/api/me'),

  logout: () =>
    apiPost<void>('/api/logout')
}
