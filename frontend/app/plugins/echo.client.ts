import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

declare global {
  interface Window {
    Pusher: typeof Pusher
  }
}

export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig()
  window.Pusher = Pusher
  const echo = new Echo<'reverb'>({
    broadcaster: 'reverb',
    key: config.public.reverbKey,
    wsHost: config.public.reverbHost,
    wsPort: config.public.reverbPort,
    wssPort: config.public.reverbPort,
    forceTLS: config.public.reverbScheme === 'https',
    enabledTransports: ['ws', 'wss'],
    // Reverb (Pusher protocol): sin stats remotos; Echo ya fuerza cluster "" para broadcaster "reverb"
    disableStats: true
  })

  return {
    provide: {
      echo
    }
  }
})
