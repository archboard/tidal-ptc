import { inject, ref, watch, toValue } from 'vue'
import NProgress from 'nprogress'
import useProp from '@/composition/useProp.js'

export default function useModelSelection (model, filters = {}) {
  if (!model) {
    model = toValue(useProp('model_alias'))
  }

  const $http = inject('$http')
  const selection = ref([])
  const selectedAll = ref(false)
  const toggleSelection = async (id) => {
    try {
      await $http.post(`/selection/${model}`, { selectable_id: id, silent: true })
    } catch (e) { }
  }
  const selectAll = async (filters = {}) => {
    NProgress.start()

    try {
      await $http.post(`/selection/${model}`, filters)
      await fetchSelection()
    } catch (e) { }

    NProgress.done()
  }
  const selectNone = async () => {
    selection.value = []

    if (selectedAll.value) {
      selectedAll.value = false
      return
    }

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
      selectAll(toValue(filters))
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
