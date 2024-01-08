import get from 'just-safe-get'
import useProp from '@/composition/useProp.js'
import { toValue } from 'vue'

export default {
  install: app => {
    const permissions = useProp('permissions')
    const can = permission => get(toValue(permissions), permission, false)

    app.config.globalProperties.can = can
    app.provide('$can', can)
  }
}

