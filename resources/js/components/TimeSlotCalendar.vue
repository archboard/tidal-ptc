<template>
  <div>
    <FullCalendar ref="calendar" :options="calendarOptions" />
  </div>
</template>

<script setup>
import { inject, ref } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import timeGridPlugin from '@fullcalendar/timegrid'
import momentTimezonePlugin from '@fullcalendar/moment-timezone'

const props = defineProps({
  timezone: String,
  timeFormat: Object,
  events: [Array, String, Object],
  optionsOverride: {
    type: Object,
    default: () => ({}),
  },
})
const emit = defineEmits(['select', 'eventClick'])
const __ = inject('$translate')
const calendar = ref({})
const calendarOptions = {
  plugins: [dayGridPlugin, interactionPlugin, timeGridPlugin, momentTimezonePlugin],
  initialView: 'timeGridThreeDay',
  headerToolbar: {
    left: 'prev,next',
    center: 'title',
    right: 'timeGridThreeDay,timeGridDay'
  },
  views: {
    timeGridThreeDay: {
      type: 'timeGrid',
      duration: { days: 3 },
      buttonText: __('3 days'),
    }
  },
  slotLabelFormat: props.timeFormat,
  eventTimeFormat: props.timeFormat,
  timeZone: props.timezone,
  selectable: true,
  selectMirror: true,
  slotEventOverlap: false,
  selectOverlap: false,
  allDaySlot: false,
  slotDuration: '00:05:00',
  scrollTime: '08:00:00',
  nowIndicator: true,
  events: props.events,
  select: (event) => {
    emit('select', event)
  },
  eventClick: (event) => {
    emit('eventClick', event)
  },
  ...props.optionsOverride,
}
defineExpose({
  calendar,
})
</script>
