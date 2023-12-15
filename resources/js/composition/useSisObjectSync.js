import { ref, toValue } from 'vue'
import { router } from '@inertiajs/vue3'

export default function useSync (type, object) {
  const syncing = ref(false)
  const sync = (objectType, sourceObject) => {
    syncing.value = true
    objectType = objectType || type
    sourceObject = sourceObject || object

    router.post(`/sync/${objectType}/${toValue(sourceObject)?.id}`, null, {
      preserveScroll: true,
      onFinish: () => {
        syncing.value = false
      },
    })
  }

  return {
    syncing,
    sync,
  }
}
