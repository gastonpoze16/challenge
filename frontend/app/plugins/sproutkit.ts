import type { Component } from 'vue'
import * as SproutKit from '@tithely/sproutkit-vue'

/**
 * Registers Sp* components from SproutKit (local file: install, not published to npm).
 */
export default defineNuxtPlugin((nuxtApp) => {
  for (const [name, component] of Object.entries(SproutKit)) {
    if (name.startsWith('Sp') && component != null) {
      nuxtApp.vueApp.component(name, component as Component)
    }
  }
})
