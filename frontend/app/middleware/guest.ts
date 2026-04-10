export default defineNuxtRouteMiddleware(async () => {
  const { token, user, fetchUser } = useAuth()

  if (!token.value) {
    return
  }

  if (!user.value) {
    await fetchUser()
  }

  if (user.value) {
    return navigateTo('/')
  }
})
