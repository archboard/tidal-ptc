import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export default (prop, defaultValue = null) => {
  return computed(() => {
    const property = usePage().props[prop]

    return typeof property === 'undefined'
      ? defaultValue
      : property
  })
}
