<script setup lang="ts">
const { user, logout } = useAuth()
const router = useRouter()

const handleLogout = async () => {
  await logout()
  await router.push('/login')
}
</script>

<template>
  <div class="layout">
    <header v-if="user" class="topbar">
      <span class="user-label">{{ user.email }}</span>
      <SpButton
        type="button"
        intent="secondary"
        variant="outline"
        size="sm"
        @click="handleLogout"
      >
        Logout
      </SpButton>
    </header>
    <slot />
  </div>
</template>

<style scoped>
.layout { min-height: 100vh; }
.topbar {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 0.75rem;
  padding: 0.65rem 1.25rem;
  border-bottom: 1px solid #e5e7eb;
  background: #fafafa;
  font-family: Inter, system-ui, sans-serif;
}
.user-label { font-size: 0.88rem; color: #374151; }
</style>
