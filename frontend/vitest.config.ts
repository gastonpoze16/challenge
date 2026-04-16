import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { defineVitestConfig } from '@nuxt/test-utils/config'

const rootDir = dirname(fileURLToPath(import.meta.url))
const sproutkitStubRoot = resolve(rootDir, 'ci-stubs/sproutkit-vue')

export default defineVitestConfig({
  test: {
    environment: 'nuxt',
    environmentOptions: {
      nuxt: {
        domEnvironment: 'happy-dom',
      },
    },
  },
  vite: {
    resolve: {
      alias:
        process.env.GITHUB_ACTIONS === 'true'
          ? { '@tithely/sproutkit-vue': sproutkitStubRoot }
          : {},
    },
  },
})
