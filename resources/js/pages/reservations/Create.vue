<template>
  <Authenticated>
    <CardWrapper>
      <CardPadding>
        <CardHeader>{{ student.name }} · {{ staff.name }}</CardHeader>
        <ConferenceBookingForm
          :student="student"
          :slots="slots"
          :existing-reservation="existingReservation"
          :conflicts="conflicts"
          :languages="languages"
          :allow-online="allowOnline"
        />
      </CardPadding>
    </CardWrapper>
  </Authenticated>
</template>

<script setup>
import Authenticated from '@/layouts/Authenticated.vue'
import CardWrapper from '@/components/CardWrapper.vue'
import CardPadding from '@/components/CardPadding.vue'
import CardHeader from '@/components/CardHeader.vue'
import ConferenceBookingForm from '@/components/forms/ConferenceBookingForm.vue'

defineProps({
  student: Object,
  staff: Object,
  slots: Array,
  existingReservation: Object,
  conflicts: Array,
  languages: Array,
  allowOnline: Boolean,
})
</script>
