import store from '@/stores/notifications.js'

export default {
  install (app, config = { delay: 4000 }) {
    const notify = (notification, delay) => {
      notification.delay = delay

      store.addNotification(notification, delay || config.delay)
    }
    const success = (text, delay) => {
      notify({
        level: 'success',
        text
      }, delay)
    }
    const error = (text, delay) => {
      notify({
        level: 'error',
        text
      }, delay)
    }

    // Generic call
    app.config.globalProperties.$notify = notify
    app.provide('$notify', notify)

    // Success notification
    app.config.globalProperties.$success = success
    app.provide('$success', success)

    // Error notification
    app.config.globalProperties.$error = error
    app.provide('$error', error)
  }
}
