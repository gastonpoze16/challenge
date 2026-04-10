<script setup lang="ts">
definePageMeta({
  layout: false,
  middleware: 'guest'
})

const email = ref('')
const password = ref('')
const errorMsg = ref('')
const pending = ref(false)

const { login } = useAuth()
const router = useRouter()

const submit = async () => {
  errorMsg.value = ''
  pending.value = true
  try {
    await login(email.value, password.value)
    await router.push('/')
  } catch (e: unknown) {
    errorMsg.value = getApiErrorMessage(
      e,
      'Invalid credentials or connection error. Please check your email and password.'
    )
  } finally {
    pending.value = false
  }
}
</script>

<template>
  <main class="login">
    <div class="card">
      <h1>Sign in</h1>
      <p class="hint">Demo: <code>admin@example.com</code> / <code>password</code></p>

      <form class="login-form" @submit.prevent="submit">
        <div class="field">
          <SpLabel>Email</SpLabel>
          <SpInput
            v-model="email"
            type="email"
            name="email"
            autocomplete="username"
            intent="default"
            size="md"
            class="w-full"
            required
          />
        </div>
        <div class="field">
          <SpLabel>Password</SpLabel>
          <SpInput
            v-model="password"
            type="password"
            name="password"
            autocomplete="current-password"
            intent="default"
            size="md"
            class="w-full"
            required
          />
        </div>
        <p v-if="errorMsg" class="error" role="alert">{{ errorMsg }}</p>
        <SpButton
          type="submit"
          intent="primary"
          size="md"
          class="submit-btn"
          :loading="pending"
          :disabled="pending"
        >
          {{ pending ? 'Signing in…' : 'Login' }}
        </SpButton>
      </form>
    </div>
  </main>
</template>

<style scoped>
.login {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  font-family: Inter, system-ui, sans-serif;
  background: #f9fafb;
}
.card {
  width: 100%;
  max-width: 380px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgb(0 0 0 / 0.06);
}
h1 { margin: 0 0 0.5rem; font-size: 1.35rem; }
.hint { margin: 0 0 1rem; font-size: 0.82rem; color: #6b7280; }
.hint code { background: #f3f4f6; padding: 0.1rem 0.3rem; border-radius: 4px; font-size: 0.8rem; }
.login-form { display: flex; flex-direction: column; gap: 0.9rem; }
.field { display: flex; flex-direction: column; gap: 0.35rem; }
.w-full { width: 100%; }
.submit-btn { margin-top: 0.25rem; align-self: stretch; }
.error { color: #dc2626; font-size: 0.88rem; margin: 0; }
</style>
