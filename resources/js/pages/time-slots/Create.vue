<template>
  <Authenticated>
    <CardWrapper class="mb-6">
      <CardPadding>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-1 lg:grid-cols-2 gap-6">
          <div>
            <CardHeader>{{ __('Default settings') }}</CardHeader>
            <HelpText>{{ __(`These settings will apply to all the time slots created.`) }}</HelpText>
          </div>
          <FadeIn>
            <AdminTimeSlotForm v-show="showForm" v-model="form" :school="school" />
          </FadeIn>
        </div>
      </CardPadding>
      <CardAction>
        <AppButton type="button" @click.prevent="showForm = !showForm">
          {{ showForm ? __('Hide time slot details') : __('Show time slot details') }}
        </AppButton>
      </CardAction>
    </CardWrapper>

    <CardWrapper>
      <CardPadding>
        <SimpleAlert class="mb-5">
          {{ __('All times are based on the school timezone, :timezone.', { timezone: school.timezone }) }}
        </SimpleAlert>

        <CreateTimeSlotCalendar
          ref="calendarRef"
          :time-format="user.fc_time_format"
          :timezone="school.timezone"
          :events="events"
          @select="onSelect"
          @event-click="onClick"
        />
      </CardPadding>
    </CardWrapper>
  </Authenticated>

  <EditTimeSlotModal
    v-if="selectedTimeSlot?.id"
    @close="onClose"
    :school="school"
    :time-slot="selectedTimeSlot"
    :ui-state="uiState"
    @delete="slotDeleted"
  />
</template>

<script setup>
import { inject, reactive, ref } from 'vue'
import Authenticated from '@/layouts/Authenticated.vue'
import useTimeSlots from '@/composition/useTimeSlots.js'
import clone from 'just-clone'
import AppFieldset from '@/components/forms/AppFieldset.vue'
import FormField from '@/components/forms/FormField.vue'
import AppTextarea from '@/components/forms/AppTextarea.vue'
import CardHeader from '@/components/CardHeader.vue'
import HelpText from '@/components/forms/HelpText.vue'
import CardWrapper from '@/components/CardWrapper.vue'
import CardPadding from '@/components/CardPadding.vue'
import AppCheckbox from '@/components/forms/AppCheckbox.vue'
import useModelSelection from '@/composition/useModelSelection.js'
import CreateTimeSlotCalendar from '@/components/CreateTimeSlotCalendar.vue'
import CardAction from '@/components/CardAction.vue'
import AppButton from '@/components/AppButton.vue'
import FadeIn from '@/components/transitions/FadeIn.vue'
import SimpleAlert from '@/components/alerts/SimpleAlert.vue'
import { useForm } from '@inertiajs/vue3'
import AdminTimeSlotForm from '@/components/forms/AdminTimeSlotForm.vue'
import EditTimeSlotModal from '@/components/modals/EditTimeSlotModal.vue'

const props = defineProps({
  school: Object,
  user: Object,
  events: {
    type: [Array, String],
    default: () => [],
  },
  useBatch: {
    type: Boolean,
    default: false,
  }
})
const $http = inject('$http')
const { timeSlotBase, createTimeSlot } = useTimeSlots(props.useBatch)
const form = useForm(clone(timeSlotBase))
const showForm = ref(true)
const uiState = ref()
const calendarRef = ref()
const selectedTimeSlot = ref({})
const onSelect = fcEvent => {
  createTimeSlot(fcEvent, form).then(ev => {
    calendarRef.value.calendar.getApi().refetchEvents()
  })
}
const onClick = fcEvent => {
  selectedTimeSlot.value = fcEvent.event.extendedProps
}
const onClose = () => {
  calendarRef.value.calendar.getApi().refetchEvents()
  selectedTimeSlot.value = {}
}
const slotDeleted = async (close) => {
  uiState.value = 'deleting'

  try {
    await $http.delete(`/time-slots/${selectedTimeSlot.value.id}`)
    close()
  } catch (e) {}

  uiState.value = null
}
</script>
