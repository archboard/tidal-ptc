import { inject, ref } from 'vue'

// Shared across mounts so layouts that remount on every visit don't refetch and flash an empty select
const languages = ref([])
let request = null

export default function useLanguages() {
  const $http = inject('$http')

  request ??= $http.get('/languages')
    .then(({ data }) => {
      languages.value = data
    })
    .catch(() => {
      request = null
    })

  return languages
}
