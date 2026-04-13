import { createRequire } from 'node:module'

// jiti a veces no resuelve imports ESM de subpaths/exports; require alinea con Node.
const require = createRequire(import.meta.url)
const Aura = require('@primeuix/themes/aura').default
const tailwindcss = require('@tailwindcss/vite').default

// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  vite: {
    plugins: [tailwindcss()]
  },
  modules: ['@pinia/nuxt', '@primevue/nuxt-module'],
  primevue: {
    options: {
      theme: {
        preset: Aura,
        options: {
          darkModeSelector: '.dark',
          cssLayer: false
        }
      }
    }
  },
  /* SproutKit: tema @theme + utilidades; ver assets/css/main.css */
  css: ['~/assets/css/main.css'],
  build: {
    transpile: ['@tithely/sproutkit-vue']
  },
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://127.0.0.1:8000',
      reverbKey: process.env.NUXT_PUBLIC_REVERB_APP_KEY || 'challenge-local-key',
      reverbHost: process.env.NUXT_PUBLIC_REVERB_HOST || '127.0.0.1',
      reverbPort: Number(process.env.NUXT_PUBLIC_REVERB_PORT || 8081),
      reverbScheme: process.env.NUXT_PUBLIC_REVERB_SCHEME || 'http'
    }
  }
})
