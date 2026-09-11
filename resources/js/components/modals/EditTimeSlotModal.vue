<template>
  <Modal
    @close="$emit('close')"
    :headline="__('Edit time slot')"
    ref="modal"
    :action-loading="uiState === 'saving'"
  >
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
