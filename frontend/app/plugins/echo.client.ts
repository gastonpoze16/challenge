import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

declare global {
  interface Window {
    Pusher: typeof Pusher
  }
}

export default defineNuxtPlugin(() => {
  if (typeof window === 'undefined' || import.meta.env.VITEST) {
    const noop = () => ({ listen: noop, leave: noop, channel: noop })
    return { provide: { echo: { channel: noop, leave: noop } as any } }
  }

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
    disableStats: true
  })

  return {
    provide: {
      echo
    }
  }
})
