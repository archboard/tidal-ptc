import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { router } from '@inertiajs/vue3'
import store from '@/stores/notifications.js'

// Index pages whose data a completed sync item affects; reload them in place
const INDEX_PATHS = {
  students: ['/students'],
  courses: ['/courses'],
  sections: ['/sections'],
  staff: ['/teachers', '/users'],
  school: ['/settings/school', '/students', '/courses', '/sections', '/teachers', '/users'],
}

const onSyncCompleted = (notification) => {
  store.addNotification({ level: notification.level, text: notification.text }, 6000)

  const onIndex = (INDEX_PATHS[notification.item] || [])
    .some(path => window.location.pathname.startsWith(path))

  if (notification.level === 'success' && onIndex) {
    router.reload({ preserveScroll: true })
  }
}

export default {
  install (app, { userId } = {}) {
    if (!userId || !import.meta.env.VITE_REVERB_APP_KEY) {
      return
    }

    window.Pusher = Pusher
    window.Echo = new Echo({
      broadcaster: 'reverb',
      key: import.meta.env.VITE_REVERB_APP_KEY,
      wsHost: import.meta.env.VITE_REVERB_HOST,
      wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
      wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
      forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
      enabledTransports: ['ws', 'wss'],
    })

    window.Echo.private(`App.Models.User.${userId}`)
      .notification(notification => {
        if (notification.type === 'App\\Notifications\\SyncCompleted') {
          onSyncCompleted(notification)
        }
      })
  },
}
