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
          {{ __('All times are based on your timezone, :timezone.', { timezone }) }}
        </SimpleAlert>

        <TimeSlotCalendar
          ref="calendarRef"
          :time-format="userSubject.fc_time_format"
          :timezone="timezone"
          :events="events"
          @select="onSelect"
          @event-click="onClick"
          @event-drop="onEventChange"
          @event-resize="onEventChange"
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
    @delete="deleteTimeSlot"
    @save="onSave"
  />
</template>

<script setup>
import { ref } from 'vue'
import Authenticated from '@/layouts/Authenticated.vue'
import useTimeSlots from '@/composition/useTimeSlots.js'
import CardHeader from '@/components/CardHeader.vue'
import HelpText from '@/components/forms/HelpText.vue'
import CardWrapper from '@/components/CardWrapper.vue'
import CardPadding from '@/components/CardPadding.vue'
import TimeSlotCalendar from '@/components/TimeSlotCalendar.vue'
import CardAction from '@/components/CardAction.vue'
import AppButton from '@/components/AppButton.vue'
import FadeIn from '@/components/transitions/FadeIn.vue'
import SimpleAlert from '@/components/alerts/SimpleAlert.vue'
import { useForm } from '@inertiajs/vue3'
import AdminTimeSlotForm from '@/components/forms/AdminTimeSlotForm.vue'
import EditTimeSlotModal from '@/components/modals/EditTimeSlotModal.vue'
import useDates from '@/composition/useDates.js'

const props = defineProps({
  school: Object,
  userSubject: Object,
  events: {
    type: [Array, String],
    default: () => [],
  },
  useBatch: {
    type: Boolean,
    default: false,
  }
})
const {
  timeSlotBase,
  createTimeSlot,
  updateTimeSlot,
  deleteTimeSlot,
  uiState,
} = useTimeSlots()
const { timezone } = useDates()
const form = useForm({
  ...timeSlotBase,
  user_id: props.userSubject.id,
})
const showForm = ref(true)
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
const onSave = async (timeSlot, callback) => {
  await updateTimeSlot(`/time-slots/${timeSlot.id}`, timeSlot)

  if (typeof callback === 'function') {
    callback()
  }
}
const onEventChange = async (fcEvent) => {
  await updateTimeSlot(`/time-slots/${fcEvent.event.extendedProps.id}`, {
    ...fcEvent.event.extendedProps,
    starts_at: fcEvent.event.startStr,
    ends_at: fcEvent.event.endStr,
  })
}
</script>
