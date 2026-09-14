<template>
  <Modal
    @close="$emit('close')"
    :headline="__('Edit time slot')"
    ref="modal"
    :action-loading="uiState === 'saving'"
  >
    <div v-if="timeSlot.student" class="mb-6 rounded-xl bg-gray-50 dark:bg-gray-700 p-4 space-y-3">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h4 class="font-semibold">{{ __('Reservation') }}</h4>
          <p class="text-sm">{{ timeSlot.student.name }}</p>
          <p v-if="timeSlot.reserved_by" class="text-sm text-gray-500 dark:text-gray-300">
            {{ timeSlot.reserved_by.name }} · {{ timeSlot.reserved_by.email }}
          </p>
          <p v-if="timeSlot.reserved_at" class="text-sm text-gray-500 dark:text-gray-300">{{ __('Booked :when', { when: displayDate(timeSlot.reserved_at, 'full', true) }) }}</p>
        </div>
        <ConfirmButton color="red" class="text-sm" :loading="cancelling" @confirmed="cancelReservation">
          {{ __('Cancel reservation') }}
          <template #actionText>{{ __('Cancel reservation') }}</template>
        </ConfirmButton>
      </div>
      <dl class="text-sm grid grid-cols-3 gap-y-1">
        <template v-if="timeSlot.contact_notes">
          <dt class="text-gray-500 dark:text-gray-300">{{ __('Notes') }}</dt>
          <dd class="col-span-2 whitespace-pre-line">{{ timeSlot.contact_notes }}</dd>
        </template>
        <dt class="text-gray-500 dark:text-gray-300">{{ __('Online requested') }}</dt>
        <dd class="col-span-2">{{ timeSlot.requested_online ? __('Yes') : __('No') }}</dd>
        <template v-if="timeSlot.language">
          <dt class="text-gray-500 dark:text-gray-300">{{ __('Translator') }}</dt>
          <dd class="col-span-2">
            <span>{{ timeSlot.language }}</span>
            <TranslatorSelect v-if="can('time_slot.update') && translators" :slot="timeSlot" :translators="translators" class="mt-1" />
            <span v-else-if="timeSlot.translator"> · {{ timeSlot.translator.name }}</span>
          </dd>
        </template>
      </dl>
      <FormField v-if="timeSlot.language" v-model="form.translator_notes" component="AppTextarea" :help="__('Visible to staff only, e.g. the assigned translator.')">
        {{ __('Translator notes') }}
      </FormField>
    </div>

    <AdminTimeSlotForm v-model="form" :school="school" />

    <details class="mt-6" @toggle="loadHistory">
      <summary class="cursor-pointer text-sm font-medium">{{ __('History') }}</summary>
      <p v-if="history === null" class="mt-2 text-sm text-gray-500">{{ __('Loading…') }}</p>
      <p v-else-if="history.length === 0" class="mt-2 text-sm text-gray-500">{{ __('No history yet.') }}</p>
      <ul v-else class="mt-2 divide-y divide-gray-200 dark:divide-gray-600">
        <li v-for="activity in history" :key="activity.id" class="py-2 text-sm">
          <div class="flex flex-wrap gap-x-2 text-gray-500 dark:text-gray-300">
            <span>{{ activity.created_at }}</span>
            <span>{{ activity.causer ?? __('System') }}</span>
            <span class="text-gray-900 dark:text-gray-100">{{ activity.description }}</span>
          </div>
          <ActivityDetails :activity="activity" class="mt-1" />
        </li>
      </ul>
    </details>

    <template #actions>
      <div class="flex items-center justify-between w-full">
        <div>
          <AppButton @click.prevent="$emit('delete', timeSlot, modal.close)" type="button" color="red" class="text-sm" :loading="uiState === 'deleting'">
            <TrashIcon class="h-4 w-4" />
            <span>{{ __('Delete') }}</span>
          </AppButton>
        </div>
        <div class="flex flex-col space-y-2 sm:space-y-0 sm:flex-row-reverse">
          <AppButton @click.prevent="save" :loading="uiState === 'saving'" class="sm:ml-2">{{ __('Save') }}</AppButton>
          <AppButton @click.prevent="modal.close()" type="button" color="white" class="text-sm">
            {{ __('Close') }}
          </AppButton>
        </div>
      </div>
    </template>
  </Modal>
</template>

<script setup>
import Modal from '@/components/modals/Modal.vue'
import { useForm } from '@inertiajs/vue3'
import useTimeSlots from '@/composition/useTimeSlots.js'
import AdminTimeSlotForm from '@/components/forms/AdminTimeSlotForm.vue'
import AppButton from '@/components/AppButton.vue'
import { TrashIcon } from '@heroicons/vue/24/solid'
import { inject, ref } from 'vue'
import ActivityDetails from '@/components/ActivityDetails.vue'
import ConfirmButton from '@/components/ConfirmButton.vue'
import TranslatorSelect from '@/components/TranslatorSelect.vue'
import FormField from '@/components/forms/FormField.vue'
import useDates from '@/composition/useDates.js'

const props = defineProps({
  school: Object,
  timeSlot: Object,
  saveEndpoint: String,
  uiState: String,
})
const emit = defineEmits(['close', 'save', 'delete'])
const { mergeTimeSlot, timeSlotBase } = useTimeSlots()
const form = useForm(mergeTimeSlot(timeSlotBase, props.timeSlot))
const modal = ref()
const $http = inject('$http')
const can = inject('$can')
const { displayDate } = useDates()
const cancelling = ref(false)
const cancelReservation = async (close) => {
  cancelling.value = true
  try {
    await $http.delete(`/reservations/${props.timeSlot.id}`)
    close()
    modal.value.close()
  } catch (e) {}
  cancelling.value = false
}
const translators = ref(null)
const loadTranslators = async () => {
  if (!props.timeSlot.language || !can('time_slot.update')) return
  try {
    const { data } = await $http.get('/translator-profiles', { headers: { Accept: 'application/json' } })
    translators.value = data
  } catch (e) {}
}
loadTranslators()
const history = ref(null)
const loadHistory = async (e) => {
  if (!e.target.open || history.value !== null) return
  const { data } = await $http.get(`/time-slots/${props.timeSlot.id}/activity`)
  history.value = data.data ?? data
}
const save = () => {
  const slot = {
    ...form.data(),
    id: props.timeSlot.id,
  }
  emit('save', slot, modal.value.close)
}
</script>
