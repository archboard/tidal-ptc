import useProp from '@/composition/useProp.js'
import { computed, ref, toValue } from 'vue'
import clone from 'just-clone'
import isEmpty from 'just-is-empty'
import { router } from '@inertiajs/vue3'
import get from 'just-safe-get'
import { watchDebounced } from '@vueuse/core'
import omit from 'just-omit'

export default function useFilters (itemsKey = null) {
  const filterKey = toValue(useProp('filterKey'))
  const allFilters = useProp('currentFilters')
  const currentFilters = computed(() => {
    return omit(toValue(allFilters), searchKey)
  })
  const searchKey = 's'
  const search = ref(get(toValue(allFilters), `${searchKey}.value`))
  const filters = ref(clone(isEmpty(toValue(currentFilters)) ? {} : toValue(currentFilters)))
  const updatingResults = ref(false)
  const prepFilters = () => {
    const filterObj = clone(toValue(filters))
    const searchObj = {
      _id: searchKey,
      value: toValue(search),
      key: 'search',
    }

    return {
      ...filterObj,
      ...(search.value ? { [searchKey]: searchObj } : {}),
    }
  }
  const updateResults = () => {
    updatingResults.value = true

    router.visit(window.location.pathname, {
      preserveScroll: true,
      preserveState: true,
      only: itemsKey ? [itemsKey] : [],
      data: {
        [filterKey]: prepFilters(),
      },
      onFinish: () => {
        updatingResults.value = false
      },
    })
  }

  watchDebounced(search, updateResults, { debounce: 500 })

  return {
    filters,
    updateResults,
    updatingResults,
    search,
  }
}
