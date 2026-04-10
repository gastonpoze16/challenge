import type { Component } from 'vue'
import * as SproutKit from '@tithely/sproutkit-vue'

/**
 * Registra los componentes Sp* de SproutKit (instalación local file:, sin publicar en npm).
 */
export default defineNuxtPlugin((nuxtApp) => {
  for (const [name, component] of Object.entries(SproutKit)) {
    if (name.startsWith('Sp') && component != null) {
      nuxtApp.vueApp.component(name, component as Component)
    }
  }
})
