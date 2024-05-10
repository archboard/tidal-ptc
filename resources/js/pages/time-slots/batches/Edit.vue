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

        <AlertWithButton class="mb-5" :action-text="__('View list')" @action="uiState = 'users'">
          {{ __('You are managing time slots for :count people.', { count: batch.users.length }) }}
        </AlertWithButton>

        <CreateTimeSlotCalendar
          ref="calendarRef"
          :time-format="user.fc_time_format"
          :timezone="school.timezone"
          :events="batch.event_source"
          @select="onSelect"
          @event-click="onClick"
        />
      </CardPadding>
    </CardWrapper>
  </Authenticated>

  <EditTimeSlotModal
    v-if="selectedTimeSlot?.starts_at"
    @close="onClose"
    :school="school"
    :time-slot="selectedTimeSlot"
    :ui-state="uiState"
    @save="onUpdate"
    @delete="onDelete"
  />
  <UserListModal
    v-if="uiState === 'users'"
    @close="uiState = null"
    :users="batch.users"
  />
</template>

<script setup>
import { inject, ref } from 'vue'
import Authenticated from '@/layouts/Authenticated.vue'
import useTimeSlots from '@/composition/useTimeSlots.js'
import clone from 'just-clone'
import CardHeader from '@/components/CardHeader.vue'
import HelpText from '@/components/forms/HelpText.vue'
import CardWrapper from '@/components/CardWrapper.vue'
import CardPadding from '@/components/CardPadding.vue'
import useModelSelection from '@/composition/useModelSelection.js'
import CreateTimeSlotCalendar from '@/components/CreateTimeSlotCalendar.vue'
import CardAction from '@/components/CardAction.vue'
import AppButton from '@/components/AppButton.vue'
import FadeIn from '@/components/transitions/FadeIn.vue'
import SimpleAlert from '@/components/alerts/SimpleAlert.vue'
import { useForm } from '@inertiajs/vue3'
import AdminTimeSlotForm from '@/components/forms/AdminTimeSlotForm.vue'
import EditTimeSlotModal from '@/components/modals/EditTimeSlotModal.vue'
import AlertWithButton from '@/components/alerts/AlertWithButton.vue'
import UserListModal from '@/components/modals/UserListModal.vue'

const props = defineProps({
  school: Object,
  user: Object,
  events: {
    type: Array,
    default: () => [],
  },
  batch: {
    type: Object,
    default: () => ({})
  },
})
const emit = defineEmits([])
const { timeSlotBase, createTimeSlot, setBatch, updateTimeSlot } = useTimeSlots()
const form = useForm(clone(timeSlotBase))
setBatch(props.batch)
const showForm = ref(true)
const calendarRef = ref()
const selectedTimeSlot = ref({})
const uiState = ref()
const $http = inject('$http')
const onSelect = fcEvent => {
  createTimeSlot(fcEvent, form).then(ev => {
    calendarRef.value.calendar.getApi().addEvent(ev, props.batch.id.toString())
  })
}
const onClick = fcEvent => {
  selectedTimeSlot.value = fcEvent.event.extendedProps
}
const doAction = async (state, action, close) => {
  uiState.value = state

  await action()

  if (typeof close === 'function') {
    close()
  }

  uiState.value = null
}
const onUpdate = (data, close) => {
  doAction('saving', () => updateTimeSlot(`/batches/${props.batch.id}`, data), close)
}
const onDelete = (close) => {
  doAction('deleting', () => $http.post(`/batches/${props.batch.id}/delete`, {
    starts_at: selectedTimeSlot.value.starts_at,
    ends_at: selectedTimeSlot.value.ends_at,
  }), close)
}
const onClose = () => {
  calendarRef.value.calendar.getApi().refetchEvents()
  selectedTimeSlot.value = {}
}
</script>
