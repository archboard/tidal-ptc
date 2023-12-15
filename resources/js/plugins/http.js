import axios from 'axios'
import get from 'just-safe-get'
import store from '@/stores/notifications'
import flashesNotifications from '@/plugins/flashesNotifications'
import { router } from '@inertiajs/vue3'

const flashMessage = response => {
  const flash = get(response, 'data.props.flash')
  const level = get(response, 'data.level')
  const message = get(response, 'data.message')

  flashesNotifications(flash)

  if (level && message) {
    store.addNotification({
      level,
      text: message,
    })
  }
}

axios.interceptors.response.use(response => {
  flashMessage(response)

  return response
}, async err => {
  const status = get(err, 'response.status')

  if (status === 419) {
    const config = err.response.config
    const { data } = await axios.get('/csrf-token')
    config.headers['X-XSRF-TOKEN'] = data.token

    return axios(config)
  }

  if (err.response) {
    store.addNotification({
      level: 'error',
      text: get(err, 'response.data.message', err.message),
    })
  }

  if (status === 401) {
    router.get('/login')
    return
  }

  return Promise.reject(err)
})

export default {
  install (app) {
    app.config.globalProperties.$http = axios
    app.provide('$http', axios)
  }
}
