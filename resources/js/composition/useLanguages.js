import { inject, ref } from 'vue'

export default function useLanguages() {
  const $http = inject('$http')
  const languages = ref([])

  $http.get('/languages')
    .then(({ data }) => {
      languages.value = data
    })

  return languages
}
