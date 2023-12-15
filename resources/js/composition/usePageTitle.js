import { computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'

export default () => {
  const title = computed(() => usePage().props.title)

  onMounted(() => {
    document.title = title.value
      ? `${title.value} | ${import.meta.env.VITE_APP_NAME}`
      : import.meta.env.VITE_APP_NAME
  })

  return title
}
