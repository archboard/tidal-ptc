<template>
  <div>
    <FullCalendar :options="calendarOptions" />
  </div>
</template>

<script setup>
import { inject } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import timeGridPlugin from '@fullcalendar/timegrid'
import useDates from '@/composition/useDates.js'

const props = defineProps({
  timezone: String,
  timeFormat: Object,
})
const emit = defineEmits(['select'])
const __ = inject('$translate')
const { dayjs } = useDates()
const calendarOptions = {
  plugins: [dayGridPlugin, interactionPlugin, timeGridPlugin],
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
  allDaySlot: false,
  slotDuration: '00:05:00',
  scrollTime: '08:00:00',
  nowIndicator: true,
  select: (event) => {
    emit('select', event)
  },
}
</script>
