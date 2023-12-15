// import { ref } from 'vue'
//
// export default {
//   install (app, config) {
//     const locale = ref('en')
//     const translate = (key, items) => {
//       if (!items) {
//         return key
//       }
//
//       return Object.keys(items).reduce((string, key) => {
//         return string.replace(`:${key}`, items[key])
//       }, key)
//     }
//
//     app.config.globalProperties.$locale = locale
//     app.config.globalProperties.__ = translate
//     app.provide('$locale', locale)
//     app.provide('$translate', translate)
//   }
// }

import { trans } from 'laravel-vue-i18n'

export default {
  install: app => {
    app.config.globalProperties.__ = trans
    app.provide('$translate', trans)
  }
}

