export function useAuth () {
  const store = useAuthStore()

  return {
    token: computed(() => store.token),
    user: computed(() => store.user),
    authHeaders: store.authHeaders,
    fetchUser: store.fetchUser,
    login: store.login,
    logout: store.logout
  }
}
