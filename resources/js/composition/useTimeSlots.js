import useProp from '@/composition/useProp.js'
import { computed, inject, ref, toValue } from 'vue'
import reduce from 'just-reduce-object'

export default function useTimeSlots() {
  const $http = inject('$http')
  const school = useProp('school')
  const uiState = ref()
  const allowTranslator = computed(() => {
    return toValue(school).allow_translator_requests &&
      (toValue(school)?.languages?.length || 0) > 0
  })
  const timeSlotBase = {
    batch_id: null,
    teacher_notes: null,
    starts_at: null,
    ends_at: null,
    location: null,
    meeting_url: null,
    is_online: false,
    contact_can_book: true,
    allow_translator_requests: allowTranslator.value,
    allow_online_meetings: toValue(school).allow_online_meetings,
  }
  const mergeTimeSlot = (defaults, timeSlot) => {
    const slot = toValue(timeSlot)

    return reduce(toValue(defaults), (acc, key, val) => {
      acc[key] = typeof slot[key] === 'undefined'
        ? val
        : slot[key]

      return acc
    }, {})
  }
  const batch = ref({})
  const setBatch = (value) => {
    batch.value = value
  }
  const createTimeSlot = async (fcEvent, defaults) => {
    const slot = {
      ...toValue(defaults),
      starts_at: fcEvent.startStr,
      ends_at: fcEvent.endStr,
      batch_id: batch.value?.id,
    }

    try {
      const { data } = await $http.post('/time-slots', slot)
      return data?.data || {}
    } catch (err) { }
  }
  const updateTimeSlot = async (endpoint, timeSlot) => {
    uiState.value = 'saving'

    try {
      await $http.put(endpoint, timeSlot)
    } catch (err) { }

    uiState.value = null
  }
  const deleteTimeSlot = async (timeSlot, close) => {
    uiState.value = 'deleting'

    try {
      await $http.delete(`/time-slots/${toValue(timeSlot).id}`)
      close()
    } catch (e) {}

    uiState.value = null
  }

  return {
    uiState,
    timeSlotBase,
    allowTranslator,
    createTimeSlot,
    updateTimeSlot,
    mergeTimeSlot,
    setBatch,
    deleteTimeSlot,
  }
}
