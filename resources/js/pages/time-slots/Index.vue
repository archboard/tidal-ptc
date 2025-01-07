<template>
  <Authenticated>
    <template #actions>
      <DropDownButton :menu-items="menuItems" />
    </template>

    <TimeSlotCalendar
      :options-override="calendarOptions"
      :time-format="user.fc_time_format"
      :timezone="user.timezone"
    />
  </Authenticated>
</template>

<script setup>
import Authenticated from '@/layouts/Authenticated.vue'
import TimeSlotCalendar from '@/components/TimeSlotCalendar.vue'
import DropDownButton from '@/components/forms/buttons/DropDownButton.vue'
import { computed } from 'vue'

const props = defineProps({
  user: Object,
  eventSources: Array,
  canCreateTimeSlots: Boolean,
})
const calendarOptions = {
  eventSources: props.eventSources,
  selectable: false,
}
const menuItems = computed(() => {
  const items = []

  if (props.canCreateTimeSlots) {
    items.push({
      route: '/time-slots/create',
      label: 'Manage my time slots',
    })
  }

  return items
})
</script>
