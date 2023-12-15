import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export default () => {
  const page = usePage()
  return computed(() => page.props.user || {})
}
