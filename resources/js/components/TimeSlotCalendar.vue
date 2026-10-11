<template>
  <div class="time-slot-calendar">
    <FullCalendar ref="calendar" :options="calendarOptions" />
  </div>
</template>

<script setup>
import { inject, ref } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import classicThemePlugin from '@fullcalendar/vue3/themes/classic'
import dayGridPlugin from '@fullcalendar/vue3/daygrid'
import interactionPlugin from '@fullcalendar/vue3/interaction'
import timeGridPlugin from '@fullcalendar/vue3/timegrid'
import arLocale from '@fullcalendar/vue3/locales/ar'
import esLocale from '@fullcalendar/vue3/locales/es'
import jaLocale from '@fullcalendar/vue3/locales/ja'
import koLocale from '@fullcalendar/vue3/locales/ko'
import zhCnLocale from '@fullcalendar/vue3/locales/zh-cn'
import '@fullcalendar/vue3/skeleton.css'
import '@fullcalendar/vue3/themes/classic/theme.css'
import '@fullcalendar/vue3/themes/classic/palette.css'
import { currentLocale } from 'laravel-vue-i18n'

const props = defineProps({
  timezone: String,
  timeFormat: Object,
  events: [Array, String, Object],
  optionsOverride: {
    type: Object,
    default: () => ({}),
  },
})
const emit = defineEmits(['select', 'eventClick', 'eventDrop', 'eventResize'])
const __ = inject('$translate')
const calendar = ref({})
const calendarOptions = {
  plugins: [classicThemePlugin, dayGridPlugin, interactionPlugin, timeGridPlugin],
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
    }
  },
  buttons: {
    timeGridThreeDay: { text: __('3 days') },
  },
  slotHeaderFormat: props.timeFormat,
  eventTimeFormat: props.timeFormat,
  timeZone: props.timezone,
  locales: [arLocale, esLocale, jaLocale, koLocale, zhCnLocale],
  locale: currentLocale.value.toLowerCase(),
  selectable: true,
  selectMirror: true,
  slotEventOverlap: false,
  selectOverlap: false,
  editable: true,
  allDaySlot: false,
  slotDuration: '00:05:00',
  scrollTime: '08:00:00',
  nowIndicator: true,
  // ponytail: v7 dropped the fc-event class; re-add it as a stable hook for styles and browser tests
  eventClass: 'fc-event cursor-pointer',
  events: props.events,
  select: (event) => {
    emit('select', event)
  },
  eventClick: (event) => {
    emit('eventClick', event)
  },
  eventDrop: (event) => {
    emit('eventDrop', event)
  },
  eventResize: (event) => {
    emit('eventResize', event)
  },
  ...props.optionsOverride,
}
defineExpose({
  calendar,
})
</script>
