<template>
  <Authenticated>
    <template #actions>
      <AppButton v-if="canOwnTimeSlots" size="sm" component="InertiaLink" :href="`/users/${subjectUser.id}/edit`">
        {{ __('Manage time slots') }}
      </AppButton>
    </template>

    <SimpleAlert class="mb-5">
      {{ __('All times are based on your timezone, :timezone.', { timezone: user.timezone }) }}
    </SimpleAlert>

    <TimeSlotCalendar
      :options-override="calendarOptions"
      :time-format="user.fc_time_format"
      :timezone="user.timezone"
      :events="subjectUser.event_source"
    />
  </Authenticated>
</template>

<script setup>
import Authenticated from '@/layouts/Authenticated.vue'
import TimeSlotCalendar from '@/components/TimeSlotCalendar.vue'
import SimpleAlert from '@/components/alerts/SimpleAlert.vue'
import AppButton from '@/components/AppButton.vue'

const props = defineProps({
  user: Object,
  subjectUser: Object,
  canOwnTimeSlots: Boolean,
})
const calendarOptions = {
  selectable: false,
}
</script>