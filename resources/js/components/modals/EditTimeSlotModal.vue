<template>
  <Modal
    @close="$emit('close')"
    :headline="__('Edit time slot')"
    ref="modal"
    :action-loading="uiState === 'saving'"
  >
    <AdminTimeSlotForm v-model="form" :school="school" />

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
import { ref } from 'vue'

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
const save = () => {
  const slot = {
    ...form.data(),
    id: props.timeSlot.id,
  }
  emit('save', slot, modal.value.close)
}
</script>
