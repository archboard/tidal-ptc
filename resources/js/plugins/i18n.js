import { trans } from 'laravel-vue-i18n'

export default {
  install: app => {
    app.config.globalProperties.__ = trans
    app.provide('$translate', trans)
  }
}

