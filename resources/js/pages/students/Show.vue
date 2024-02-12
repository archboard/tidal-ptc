<template>
  <Authenticated>
    <template #actions>
      <DropDownButton v-if="can('student.update')">
        {{ __('Actions') }}
        <template #dropdown>
          <StudentActionMenu :student="student" ignore-view />
        </template>
      </DropDownButton>
    </template>
    <template #after-title>
      <Pill v-if="!student.can_book" color="yellow">{{ __('Booking disabled') }}</Pill>
    </template>

    <pre>{{ student }}</pre>
  </Authenticated>
</template>

<script setup>
import { ref } from 'vue'
import Authenticated from '@/layouts/Authenticated.vue'
import DropDownButton from '@/components/forms/buttons/DropDownButton.vue'
import StudentActionMenu from '@/components/actions/StudentActionMenu.vue'
import Pill from '@/components/Pill.vue'

const props = defineProps({
  student: Object,
})
</script>
