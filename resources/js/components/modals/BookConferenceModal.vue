<template>
  <Modal @close="$emit('close')" :headline="`${student.name} · ${staff.name}`" ref="modal" hide-actions size="3xl">
    <p v-if="!booking" class="text-sm text-gray-500">{{ __('Loading…') }}</p>
    <ConferenceBookingForm
      v-else
      :student="student"
      :slots="booking.slots"
      :existing-reservation="booking.existingReservation"
      :conflicts="booking.conflicts"
      :languages="booking.languages"
      :allow-online="booking.allowOnline"
      @success="modal.close()"
    />
  </Modal>
</template>

<script setup>
import { inject, ref } from 'vue'
import Modal from '@/components/modals/Modal.vue'
import ConferenceBookingForm from '@/components/forms/ConferenceBookingForm.vue'

const props = defineProps({
  student: Object,
  staff: Object,
})
defineEmits(['close'])
const modal = ref()
const $http = inject('$http')
const booking = ref(null)
$http.get(`/reservations/create/${props.student.id}/${props.staff.id}`, { headers: { Accept: 'application/json' } })
  .then(({ data }) => { booking.value = data })
  .catch(() => modal.value.close())
</script>
