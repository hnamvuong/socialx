import Echo from 'laravel-echo'
import Pusher, {
  type Channel,
  type ChannelAuthorizationCallback,
} from 'pusher-js'

import api from '@/services/api'



const browserWindow = window as typeof window & {
  Pusher: typeof Pusher
}

browserWindow.Pusher = Pusher

const echo = new Echo({
  broadcaster: 'reverb',

  key: import.meta.env.VITE_REVERB_APP_KEY,

  wsHost: import.meta.env.VITE_REVERB_HOST,

  wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 80),

  wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),

  forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',

  enabledTransports: ['ws', 'wss'],

  authorizer: (channel: Channel) => ({
    authorize: (socketId: string, callback: ChannelAuthorizationCallback): void => {
      api
        .post('/broadcasting/auth', {
          socket_id: socketId,

          channel_name: channel.name,
        })
        .then((response) => {
          callback(null, response.data)
        })
        .catch((error: unknown) => {
          const authorizationError = error instanceof Error
            ? error
            : new Error('Broadcast channel authorization failed')

          callback(authorizationError, null)
        })
    },
  }),
})

export default echo
