import { createRequire } from 'node:module'
import { fileURLToPath } from 'node:url'

// jiti a veces no resuelve imports ESM de subpaths/exports; require alinea con Node.
const require = createRequire(import.meta.url)
const Aura = require('@primeuix/themes/aura').default
const tailwindcss = require('@tailwindcss/vite').default

const sproutkitStubDir = fileURLToPath(new URL('./ci-stubs/sproutkit-vue', import.meta.url))
const useSproutkitStub =
  process.env.NUXT_SPROUTKIT_STUB === '1' ||
  process.env.GITHUB_ACTIONS === 'true'

// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  vite: {
    plugins: [tailwindcss()],
    resolve: {
      alias: useSproutkitStub
        ? { '@tithely/sproutkit-vue': sproutkitStubDir }
        : {},
    },
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
