import useProp from '@/composition/useProp.js'
import { computed, inject, ref, toValue } from 'vue'

export default function useTimeSlots() {
  const $http = inject('$http')
  const school = useProp('school')
  const allowTranslator = computed(() => {
    return toValue(school).allow_translator_requests &&
      toValue(school).languages.length > 0
  })
  const timeSlotBase = {
    teacher_notes: null,
    location: null,
    meeting_url: null,
    is_online: false,
    contact_can_book: true,
    allow_translator_requests: allowTranslator.value,
    allow_online_meetings: toValue(school).allow_online_meetings,
  }
  const batch = ref({})
  const createBatch = async () => {
    try {
      const { data } = await $http.post('/batches')
      batch.value = data
    } catch (err) {}
  }
  const createTimeSlot = async (fcEvent, defaults) => {
    if (!batch.value.id) {
      await createBatch()
    }

    const data = {
      ...toValue(defaults),
      starts_at: fcEvent.startStr,
      ends_at: fcEvent.endStr,
      batch_id: batch.value.id,
    }

    console.log(data)
  }

  return {
    timeSlotBase,
    allowTranslator,
    createTimeSlot,
  }
}
