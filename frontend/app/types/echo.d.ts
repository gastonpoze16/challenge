import type Echo from 'laravel-echo'

declare module '#app' {
  interface NuxtApp {
    $echo: Echo<'reverb'>
  }
}

export {}
