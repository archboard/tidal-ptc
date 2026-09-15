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
  school: ['/students', '/courses', '/sections', '/teachers', '/users'],
}

const onSyncCompleted = (notification) => {
  store.addNotification({ level: notification.level, text: notification.text }, 6000)

  // The settings page shows counts for every item, so it always reloads
  const onIndex = ['/settings/school', ...(INDEX_PATHS[notification.item] || [])]
    .some(path => window.location.pathname.startsWith(path))

  if (notification.level === 'success' && onIndex) {
    router.reload({ preserveScroll: true })
  }
}

export default {
  install (app, { userId, reverb } = {}) {
    if (!userId || !reverb?.key) {
      return
    }

    window.Pusher = Pusher
    window.Echo = new Echo({
      broadcaster: 'reverb',
      key: reverb.key,
      wsHost: reverb.host,
      wsPort: reverb.port ?? 80,
      wssPort: reverb.port ?? 443,
      forceTLS: (reverb.scheme ?? 'https') === 'https',
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
