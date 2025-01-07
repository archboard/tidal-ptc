<template>
  <Authenticated>
    <SimpleAlert class="mb-5">
      {{ __('All times are based on your timezone, :timezone.', { timezone: user.timezone }) }}
    </SimpleAlert>

    <TimeSlotCalendar
      :options-override="calendarOptions"
      :time-format="user.fc_time_format"
      :timezone="user.timezone"
      :events="user.event_source"
    />
  </Authenticated>
</template>

<script setup>
import Authenticated from '@/layouts/Authenticated.vue'
import TimeSlotCalendar from '@/components/TimeSlotCalendar.vue'
import SimpleAlert from '@/components/alerts/SimpleAlert.vue'

const props = defineProps({
  user: Object,
})
const calendarOptions = {
  selectable: false,
}
</script>