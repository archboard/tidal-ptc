import { inject, ref, watch, toValue } from 'vue'
import NProgress from 'nprogress'

export default function useModelSelection (model) {
  const $http = inject('$http')
  const selection = ref([])
  const selectedAll = ref(false)
  const toggleSelection = async (id) => {
    try {
      await $http.post(`/selection/${model}`, { selectable_id: id, silent: true })
    } catch (e) { }
  }
  const selectAll = async (ids = []) => {
    NProgress.start()

    try {
      await $http.post(`/selection/${model}`, { ids })
      await fetchSelection()
    } catch (e) { }

    NProgress.done()
  }
  const selectNone = async () => {
    selection.value = []
    selectedAll.value = false

    try {
      await $http.delete(`/selection/${model}`)
    } catch (e) { }
  }
  const fetchSelection = async () => {
    try {
      const { data } = await $http.get(`/selection/${model}`)
      selection.value = data
    } catch (e) { }
  }

  watch(selectedAll, value => {
    if (value) {
      selectAll()
    } else {
      selectNone()
    }
  })

  fetchSelection()

  return {
    selection,
    toggleSelection,
    fetchSelection,
    selectAll,
    selectNone,
    selectedAll,
  }
}
